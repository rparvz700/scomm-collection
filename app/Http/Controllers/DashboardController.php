<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Collection;
use App\Models\MonthlySummary;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {


        $requestedMonth = $request->query('month');
        if ($requestedMonth) {
            $latestMonth = \Carbon\Carbon::parse($requestedMonth)->endOfMonth()->format('Y-m-d');
            $latestSummary = MonthlySummary::query()
                ->whereDate('summary_month', $latestMonth)
                ->first();
        } else {
            $latestSummary = MonthlySummary::query()
                ->orderByDesc('summary_month')
                ->first();
            $latestMonth = $latestSummary?->summary_month;
        }

        $availableMonths = MonthlySummary::query()
            ->select('summary_month')
            ->distinct()
            ->orderBy('summary_month', 'asc')
            ->pluck('summary_month')
            ->map(fn($m) => \Carbon\Carbon::parse($m)->format('Y-m-d'))
            ->toArray();
        $billedMrcTotal = (float) MonthlySummary::where('summary_month', $latestMonth)->sum('total_mrc');
        $collectionMrcTotal = (float) MonthlySummary::where('summary_month', $latestMonth)->sum('collection_mrc');
        $collectionTotal = $this->currentMonthCollectionTotal($latestMonth);

        $currentMonthLabel = $latestMonth ? \Carbon\Carbon::parse($latestMonth)->format('F Y') : 'Current Month';

        $discontinuedCollection = (float) \App\Models\MonthlySummaryDiscontinued::where('summary_month', $latestMonth)->sum('collection_amount');
        $discontinuedOpeningOs = (float) \App\Models\MonthlySummaryDiscontinued::where('summary_month', $latestMonth)->sum('opening_os');

        $barredClientsList = MonthlySummary::whereDate('summary_month', $latestMonth)
            ->where('client_status', 'Barred')
            ->whereNotNull('client_barred_at')
            ->get()
            ->merge(
                \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)
                    ->where('client_status', 'Barred')
                    ->whereNotNull('client_barred_at')
                    ->get()
            );

        $barredClients = $barredClientsList
            ->map(function ($item) {
                $days = $item->client_barred_at ? abs(now()->diffInDays($item->client_barred_at, false)) : 0;
                $months = round($days / 30.4, 1);
                
                return [
                    'client_id' => $item->client_id,
                    'client_name' => $item->client_name,
                    'barred_at_formatted' => $item->client_barred_at ? $item->client_barred_at->format('d M Y') : 'N/A',
                    'barred_at_raw' => $item->client_barred_at ? $item->client_barred_at->format('Y-m-d') : null,
                    'aging_days' => $days,
                    'aging_months' => $months,
                    'barring_percentage' => (float) $item->client_barring_percentage,
                ];
            })
            ->filter(function ($client) {
                return $client['aging_months'] >= 2.0;
            })
            ->sortByDesc('aging_days')
            ->values();

        $ranges = [
            ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50],
            ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00],
            ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50],
            ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99],
            ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49],
            ['label' => '>= 3.50', 'min' => 3.50, 'max' => null],
        ];
        $monthParam = $latestMonth ? \Carbon\Carbon::parse($latestMonth)->format('Y-m') : now()->format('Y-m');

        $combinedShortfalls = MonthlySummary::whereDate('summary_month', $latestMonth)
            ->where(function ($q) {
                $q->where('mrc_shortfall', '>', 0)
                  ->orWhere('backlog_shortfall', '>', 0);
            })
            ->with('client')
            ->orderByDesc('total_latest_os')
            ->get()
            ->map(function ($item) use ($ranges, $monthParam) {
                $cr = (float)$item->latest_cr;
                $rangeLabel = '0.00 - 1.50';
                foreach ($ranges as $r) {
                    if ($r['max'] === null) {
                        if ($cr >= $r['min']) {
                            $rangeLabel = $r['label'];
                            break;
                        }
                    } else {
                        if ($cr >= $r['min'] && $cr <= $r['max']) {
                            $rangeLabel = $r['label'];
                            break;
                        }
                    }
                }

                $billing = $item->client->license_billing ?? '';
                $segment = (stripos($billing, 'iig') !== false) ? 'IIG' : 'ISP & Other Operators';

                return [
                    'client_id' => $item->client_id,
                    'client_name' => $item->client->client_name ?? $item->client_name,
                    'status' => 'Active',
                    'os' => (float)$item->total_latest_os,
                    'mrc' => (float)$item->total_mrc,
                    'mrc_shortfall' => (float)$item->mrc_shortfall,
                    'backlog_shortfall' => (float)$item->backlog_shortfall,
                    'cr' => $cr,
                    'rating' => $item->latest_rating_category,
                    'segment' => $segment,
                    'range' => $rangeLabel,
                    'month_param' => $monthParam,
                ];
            });

        $pendingBarringRequests = MonthlySummary::whereDate('summary_month', $latestMonth)
            ->where('client_barring_workflow_status', 'pending_approval')
            ->get()
            ->merge(
                \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)
                    ->where('client_barring_workflow_status', 'pending_approval')
                    ->get()
            );

        $approvedBarringRequests = MonthlySummary::whereDate('summary_month', $latestMonth)
            ->where('client_barring_workflow_status', 'approved')
            ->get()
            ->merge(
                \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)
                    ->where('client_barring_workflow_status', 'approved')
                    ->get()
            );

        $guidanceLogs = \App\Models\ManagementGuidanceLog::with(['client', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $activeClientCount = MonthlySummary::whereDate('summary_month', $latestMonth)
            ->where('client_status', 'Active')
            ->count();

        $discontinuedSubCount = \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)
            ->where(function($q) {
                $q->where('client_status', 'Discontinued')
                  ->orWhere('client_status', 'like', '%discontinued%');
            })
            ->count();

        $barredSubCount = \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)
            ->where(function($q) {
                $q->where('client_status', 'Barred')
                  ->orWhere('client_status', 'like', '%barred%')
                  ->orWhere('client_status', 'like', '%barring%');
            })
            ->count();

        $discontinuedClientCount = \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)->count();

        return view('dashboard.dashboard', [
            'clientCount' => $activeClientCount + $discontinuedClientCount,
            'activeClientCount' => $activeClientCount,
            'discontinuedClientCount' => $discontinuedClientCount,
            'discontinuedSubCount' => $discontinuedSubCount,
            'barredSubCount' => $barredSubCount,
            'billedMrcTotal' => $billedMrcTotal,
            'collectionTotal' => $collectionTotal,
            'currentMonthOs' => max($billedMrcTotal - $collectionMrcTotal, 0),
            'latestOutstanding' => MonthlySummary::where('summary_month', $latestMonth)->sum('total_latest_os'),
            'highRiskCount' => $this->currentMonthHighRiskCount($latestMonth),
            'latestSummary' => $latestSummary,
            'snapshotAnalytics' => $this->snapshotAnalytics(),
            'trendChart' => $this->trendChart(),
            'segmentAnalysis' => $this->segmentAnalysis(),
            'combinedMoMSummary' => $this->combinedMoMSummary($latestMonth),
            'combinedRiskBreakdown' => $this->combinedRiskBreakdown($latestMonth),
            'recentCollections' => Collection::query()
                ->with('client')
                ->latest('collection_datetime')
                ->take(5)
                ->get(),
            'recentRisks' => Risk::query()
                ->with('client')
                ->latest('risk_event_datetime')
                ->take(5)
                ->get(),
            'metricComparisons' => [
                'clients' => $this->metricComparison('total_clients'),
                'active_clients' => $this->metricComparison('active_clients'),
                'discontinued_clients' => $this->metricComparison('discontinued_clients'),
                'mrc' => $this->metricComparison('total_mrc'),
                'collection' => $this->metricComparison('total_collection'),
                'current_month_os' => $this->metricComparison('current_month_os'),
                'os' => $this->metricComparison('latest_os'),
                'risk' => $this->metricComparison('high_risk'),
            ],
            'collectionEfficiency' => $this->collectionEfficiency($latestMonth),
            'kamPerformance' => $this->kamPerformance($latestMonth),
            'teams' => $this->teamPerformance($latestMonth, 'team_name'),
            'collectionKams' => $this->teamPerformance($latestMonth, 'collection_kam'),
            'supervisors' => $this->teamPerformance($latestMonth, 'collection_supervisor'),
            'smKams' => $this->teamPerformance($latestMonth, 'sm_kam'),
            'dynamicInsights' => $this->dynamicInsights($latestMonth),
            'discontinuedCollection' => $discontinuedCollection,
            'discontinuedOpeningOs' => $discontinuedOpeningOs,
            'discontinuedComparison' => $this->discontinuedMetricComparison(),
            'discontinuedAnalysis' => $this->discontinuedAnalysis(),
            
            // New slide 4 and workflow variables
            'barredClients' => $barredClients,
            'combinedShortfalls' => $combinedShortfalls,
            'pendingBarringRequests' => $pendingBarringRequests,
            'approvedBarringRequests' => $approvedBarringRequests,
            'guidanceLogs' => $guidanceLogs,
            'currentMonthLabel' => $currentMonthLabel,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $latestMonth,
        ]);
    }

    private function currentMonthCollectionTotal($latestMonth): float
    {
        if (! $latestMonth) {
            return 0;
        }

        return (float) Collection::query()
            ->whereDate('collection_month', $latestMonth)
            ->sum('collection_amount');
    }

    private function currentMonthHighRiskCount($latestMonth): int
    {
        if (! $latestMonth) {
            return 0;
        }

        return MonthlySummary::query()
            ->whereDate('summary_month', $latestMonth)
            ->whereIn('latest_rating_category', ['Risky', 'High Risky', 'Most Risky'])
            ->count();
    }

    private function snapshotAnalytics(): array
    {
        $monthly = MonthlySummary::query()
            ->selectRaw('summary_month, SUM(net_backlog_total) as backlog, SUM(total_collection) as collection, SUM(total_mrc) as mrc, SUM(total_latest_os) as closing_os')
            ->groupBy('summary_month')
            ->orderByDesc('summary_month')
            ->take(6)
            ->get();

        $latest = $monthly->get(0);
        $previous = $monthly->get(1);

        $latestQuarter = $monthly->take(3);
        $previousQuarter = $monthly->slice(3, 3);

        return [
            'mom_backlog' => $this->percentageChange((float) ($latest?->backlog ?? 0), (float) ($previous?->backlog ?? 0)),
            'mom_collection' => $this->percentageChange((float) ($latest?->collection ?? 0), (float) ($previous?->collection ?? 0)),
            'qoq_backlog' => $this->percentageChange((float) $latestQuarter->sum('backlog'), (float) $previousQuarter->sum('backlog')),
            'qoq_collection' => $this->percentageChange((float) $latestQuarter->sum('collection'), (float) $previousQuarter->sum('collection')),
        ];
    }

    private function trendChart(): array
    {
        $rows = MonthlySummary::query()
            ->selectRaw('
                summary_month, 
                SUM(total_opening_os) as total_opening_os,
                SUM(total_collection) as total_collection,
                SUM(total_mrc) as total_mrc,
                SUM(collection_mrc) as collection_mrc,
                SUM(net_backlog_total) as net_backlog_total,
                SUM(collection_backlog) as collection_backlog,
                SUM(total_latest_os) as total_latest_os
            ')
            ->groupBy('summary_month')
            ->orderBy('summary_month')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $row->summary_month->format('M Y'))->values(),
            'total_opening_os' => $rows->map(fn ($row) => (float) $row->total_opening_os / 1000000)->values(),
            'total_collection' => $rows->map(fn ($row) => (float) $row->total_collection / 1000000)->values(),
            'total_mrc' => $rows->map(fn ($row) => (float) $row->total_mrc / 1000000)->values(),
            'collection_mrc' => $rows->map(fn ($row) => (float) $row->collection_mrc / 1000000)->values(),
            'net_backlog_total' => $rows->map(fn ($row) => (float) $row->net_backlog_total / 1000000)->values(),
            'collection_backlog' => $rows->map(fn ($row) => (float) $row->collection_backlog / 1000000)->values(),
            'total_latest_os' => $rows->map(fn ($row) => (float) $row->total_latest_os / 1000000)->values(),
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    private function segmentAnalysis(): array
    {
        $latestMonth = MonthlySummary::query()->max('summary_month');

        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();

        $currentSummaries = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get();

        $previousSummaries = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $previousMonthDate)
            ->get();

        $currentIig = $currentSummaries->filter(fn (MonthlySummary $summary) => $this->isIigSegment($summary));
        $currentNonIig = $currentSummaries->reject(fn (MonthlySummary $summary) => $this->isIigSegment($summary));

        $prevIig = $previousSummaries->filter(fn (MonthlySummary $summary) => $this->isIigSegment($summary));
        $prevNonIig = $previousSummaries->reject(fn (MonthlySummary $summary) => $this->isIigSegment($summary));

        $totalNetBacklog = (float) $currentSummaries->sum('net_backlog_total');
        $totalMrc = (float) $currentSummaries->sum('total_mrc');

        $currentMonthLabel = $latestMonthDate->format('F Y');
        $prevMonthLabel = $previousMonthDate->format('F Y');

        return [
            [
                'name' => 'IIG Operators',
                'latestMonth' => $latestMonth,
                'rows' => $this->segmentRows($currentIig, $totalNetBacklog, $totalMrc),
                'comparisons' => [
                    $this->segmentComparisonData($currentIig, $currentMonthLabel),
                    $this->segmentComparisonData($prevIig, $prevMonthLabel),
                ],
            ],
            [
                'name' => 'ISP & Other Operators',
                'latestMonth' => $latestMonth,
                'rows' => $this->segmentRows($currentNonIig, $totalNetBacklog, $totalMrc),
                'comparisons' => [
                    $this->segmentComparisonData($currentNonIig, $currentMonthLabel),
                    $this->segmentComparisonData($prevNonIig, $prevMonthLabel),
                ],
            ],
        ];
    }

    private function combinedMoMSummary($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();

        $comparisons = [];
        foreach ([$latestMonthDate, $previousMonthDate] as $date) {
            $comp = MonthlySummary::query()
                ->whereDate('summary_month', $date)
                ->selectRaw('
                    COUNT(*) as clients_count,
                    SUM(total_opening_os) as opening_os_sum,
                    SUM(total_mrc) as mrc_sum,
                    SUM(net_backlog_total) as backlog_sum,
                    AVG(opening_cr) as opening_cr_avg,
                    SUM(total_latest_os) as latest_os_sum,
                    AVG(latest_cr) as latest_cr_avg
                ')
                ->first();

            $comparisons[] = [
                'month' => $date->format('F Y'),
                'clients_count' => (int) ($comp?->clients_count ?? 0),
                'opening_os_sum' => (float) ($comp?->opening_os_sum ?? 0),
                'mrc_sum' => (float) ($comp?->mrc_sum ?? 0),
                'backlog_sum' => (float) ($comp?->backlog_sum ?? 0),
                'opening_cr_avg' => (float) ($comp?->opening_cr_avg ?? 0),
                'latest_os_sum' => (float) ($comp?->latest_os_sum ?? 0),
                'latest_cr_avg' => (float) ($comp?->latest_cr_avg ?? 0),
            ];
        }

        return $comparisons;
    }

    private function combinedRiskBreakdown($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        
        $ranges = $this->crRanges();
        $rows = [];

        // Fetch totals first
        $totals = MonthlySummary::query()
            ->whereDate('summary_month', $latestMonthDate)
            ->selectRaw('
                COUNT(*) as client_count,
                SUM(total_latest_os) as latest_os_sum,
                SUM(total_mrc) as mrc_sum,
                SUM(net_backlog_total) as backlog_sum
            ')
            ->first();

        $totalClients = (int) ($totals?->client_count ?? 0);
        $totalLatestOs = (float) ($totals?->latest_os_sum ?? 0);
        $totalMrc = (float) ($totals?->mrc_sum ?? 0);
        $totalBacklog = (float) ($totals?->backlog_sum ?? 0);

        foreach ($ranges as $range) {
            $min = $range['min'];
            $max = $range['max'];

            $rangeQuery = MonthlySummary::query()
                ->whereDate('summary_month', $latestMonthDate);

            if ($min === 0) {
                $rangeQuery->where('latest_cr', '<=', $max);
            } else {
                $rangeQuery->where('latest_cr', '>=', $min);
                if ($max !== null) {
                    $rangeQuery->where('latest_cr', '<=', $max);
                }
            }

            $stats = $rangeQuery->selectRaw('
                COUNT(*) as client_count,
                SUM(total_latest_os) as latest_os_sum,
                SUM(total_mrc) as mrc_sum,
                SUM(net_backlog_total) as backlog_sum
            ')->first();

            $clientCount = (int) ($stats?->client_count ?? 0);
            $latestOsSum = (float) ($stats?->latest_os_sum ?? 0);
            $mrcSum = (float) ($stats?->mrc_sum ?? 0);
            $backlogSum = (float) ($stats?->backlog_sum ?? 0);

            $rows[] = [
                'category' => $range['category'],
                'cr_segment' => $range['label'],
                'client_count' => $clientCount,
                'latest_os_sum' => $latestOsSum,
                'mrc_sum' => $mrcSum,
                'backlog_sum' => $backlogSum,
                'mrc_percentage' => $totalMrc > 0 ? ($mrcSum / $totalMrc) * 100 : 0,
                'backlog_percentage' => $totalBacklog > 0 ? ($backlogSum / $totalBacklog) * 100 : 0,
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'client_count' => $totalClients,
                'latest_os_sum' => $totalLatestOs,
                'mrc_sum' => $totalMrc,
                'backlog_sum' => $totalBacklog,
            ]
        ];
    }

    private function segmentComparisonData(EloquentCollection $summaries, string $monthLabel): array
    {
        return [
            'month' => $monthLabel,
            'clients_count' => $summaries->count(),
            'opening_os_sum' => (float) $summaries->sum('total_opening_os'),
            'mrc_sum' => (float) $summaries->sum('total_mrc'),
            'backlog_sum' => (float) $summaries->sum('net_backlog_total'),
            'opening_cr_avg' => $summaries->count() ? (float) $summaries->avg('opening_cr') : 0,
            'latest_os_sum' => (float) $summaries->sum('total_latest_os'),
            'latest_cr_avg' => $summaries->count() ? (float) $summaries->avg('latest_cr') : 0,
        ];
    }

    private function segmentRows(EloquentCollection $summaries, float $totalNetBacklog, float $totalMrc): array
    {
        return collect($this->crRanges())
            ->map(function (array $range) use ($summaries, $totalNetBacklog, $totalMrc) {
                $items = $summaries->filter(fn (MonthlySummary $summary) => $this->inCrRange((float) $summary->latest_cr, $range));
                $netBacklog = (float) $items->sum('net_backlog_total');
                $mrc = (float) $items->sum('total_mrc');

                return [
                    'cr_range' => $range['label'],
                    'risk_category' => $this->riskCategoryForRange($range),
                    'client_count' => $items->count(),
                    'opening_avg_cr' => $items->count() ? (float) $items->avg('opening_cr') : 0,
                    'opening_os_sum' => (float) $items->sum('total_opening_os'),
                    'latest_mrc_sum' => $mrc,
                    'net_backlog_sum' => $netBacklog,
                    'collection_this_month' => (float) $items->sum('total_collection'),
                    'closing_os_sum' => (float) $items->sum('total_latest_os'),
                    'closing_avg_cr' => $items->count() ? (float) $items->avg('latest_cr') : 0,
                    'total_net_backlog' => $totalNetBacklog,
                    'percentage_total_net_backlog' => $totalNetBacklog > 0 ? ($netBacklog / $totalNetBacklog) * 100 : 0,
                    'percentage_total_mrc' => $totalMrc > 0 ? ($mrc / $totalMrc) * 100 : 0,
                    'cr_above_251' => $items->filter(fn (MonthlySummary $summary) => (float) $summary->latest_cr >= 2.51)->count(),
                    'proposed_for_barring' => $items->filter(fn (MonthlySummary $summary) => (float) $summary->latest_cr >= 2.51)->count(),
                    'already_barred' => $items->filter(fn (MonthlySummary $summary) => $this->isAlreadyBarred($summary))->count(),
                ];
            })
            ->all();
    }

    private function crRanges(): array
    {
        return config('risk.ranges') ?: [
            ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50, 'category' => 'Best'],
            ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00, 'category' => 'Good'],
            ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50, 'category' => 'Moderate'],
            ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99, 'category' => 'Risky'],
            ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49, 'category' => 'High Risky'],
            ['label' => '>= 3.50', 'min' => 3.50, 'max' => null, 'category' => 'Most Risky'],
        ];
    }

    private function inCrRange(float $cr, array $range): bool
    {
        if ((float)$range['min'] === 0.0) {
            return $cr <= $range['max'];
        }

        if ($cr < $range['min']) {
            return false;
        }

        return $range['max'] === null || $cr <= $range['max'];
    }

    private function riskCategoryForRange(array $range): string
    {
        return $range['category'] ?? 'Unknown';
    }

    private function isIigSegment(MonthlySummary $summary): bool
    {
        return str_contains(strtolower((string) $summary->client?->license_billing), 'iig');
    }

    private function isAlreadyBarred(MonthlySummary $summary): bool
    {
        $status = strtolower((string) $summary->client?->client_status);

        return str_contains($status, 'barred') || str_contains($status, 'barring');
    }

    private function metricComparison(string $metric): array
    {
        $monthly = MonthlySummary::query()
            ->selectRaw('
                summary_month,
                COUNT(DISTINCT monthly_summary.client_id) as total_clients,
                SUM(CASE WHEN COALESCE(monthly_summary.client_status, "") = "Active" THEN 1 ELSE 0 END) as active_clients,
                SUM(CASE WHEN COALESCE(monthly_summary.client_status, "") != "Active" THEN 1 ELSE 0 END) as discontinued_clients,
                SUM(total_mrc) as total_mrc,
                SUM(total_collection) as total_collection,
                CASE
                    WHEN SUM(total_mrc) > SUM(collection_mrc)
                    THEN SUM(total_mrc) - SUM(collection_mrc)
                    ELSE 0
                END as current_month_os,
                SUM(total_latest_os) as latest_os,
                SUM(
                    CASE
                        WHEN latest_rating_category IN ("Risky", "High Risky", "Most Risky")
                        THEN 1
                        ELSE 0
                    END
                ) as high_risk
            ')
            ->groupBy('summary_month')
            ->orderByDesc('summary_month')
            ->take(6)
            ->get();

        $latest = $monthly->get(0);
        $previous = $monthly->get(1);

        $latestQuarter = $monthly->take(3);
        $previousQuarter = $monthly->slice(3, 3);

        $latestValue = (float) ($latest?->{$metric} ?? 0);
        $previousValue = (float) ($previous?->{$metric} ?? 0);

        $latestQuarterValue = (float) $latestQuarter->sum($metric);
        $previousQuarterValue = (float) $previousQuarter->sum($metric);

        return [
            'mom' => $this->percentageChange($latestValue, $previousValue),
            'qoq' => $this->percentageChange($latestQuarterValue, $previousQuarterValue),
        ];
    }

    private function collectionEfficiency($latestMonth): float
    {
        if (! $latestMonth) {
            return 0;
        }

        $summary = MonthlySummary::query()
            ->whereDate('summary_month', $latestMonth)
            ->selectRaw('SUM(total_collection) as collection, SUM(total_maturity) as maturity')
            ->first();

        $collection = (float) ($summary?->collection ?? 0);
        $maturity = (float) ($summary?->maturity ?? 0);

        if ($maturity <= 0) {
            return 0;
        }

        return ($collection / $maturity) * 100;
    } 
    private function kamPerformance($latestMonth): array
    {
        if (! $latestMonth) {
            return [
                'best' => null,
                'worst' => null,
            ];
        }

        $date = \Carbon\Carbon::parse($latestMonth);

        // Fetch late entries for KAMs
        $lateEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->whereRaw("DATEDIFF(collection.created_at, collection.collection_datetime) > 2")
            ->selectRaw("COALESCE(client.collection_kam, 'Unassigned') as name, COUNT(*) as late_count")
            ->groupBy('name')
            ->pluck('late_count', 'name')
            ->toArray();

        // Fetch total entries for KAMs
        $totalEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->selectRaw("COALESCE(client.collection_kam, 'Unassigned') as name, COUNT(*) as total_count")
            ->groupBy('name')
            ->pluck('total_count', 'name')
            ->toArray();

        $rows = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get()
            ->groupBy(fn ($summary) => $summary->client?->collection_kam);

        $performance = $rows->map(function ($items, $kam) use ($lateEntries, $totalEntries) {
            $kamName = $kam ?: 'Unassigned';
            $collection = (float) $items->sum('total_collection');
            $maturity = (float) $items->sum('total_maturity');

            $efficiency = $maturity > 0
                ? ($collection / $maturity) * 100
                : 0;

            $lateCount = $lateEntries[$kamName] ?? 0;
            $totalCount = $totalEntries[$kamName] ?? 0;
            $lateRate = $totalCount > 0 ? ($lateCount / $totalCount) : 0.0;

            // Combined score: 50% efficiency + 50% lowest late entry rate (100 - late_rate_pct)
            $score = ($efficiency * 0.5) + ((1.0 - $lateRate) * 50);

            return [
                'kam' => $kamName,
                'efficiency' => $efficiency,
                'late_entry' => $lateCount,
                'total_entry' => $totalCount,
                'score' => $score,
            ];
        })->sortByDesc('score')->values();

        return [
            'best' => $performance->first(),
            'worst' => $performance->last(),
        ];
    }     

    private function teamPerformance($latestMonth, string $clientField): array
    {
        if (! $latestMonth) {
            return [];
        }

        // Map clientField to database column on client table
        $dbCol = match($clientField) {
            'team_name' => 'team_name',
            'collection_kam' => 'collection_kam',
            'collection_supervisor' => 'collection_supervisor',
            'sm_kam' => 'sm_kam',
            default => $clientField
        };

        // Query late entries count
        $date = \Carbon\Carbon::parse($latestMonth);
        $lateEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->whereRaw("DATEDIFF(collection.created_at, collection.collection_datetime) > 2")
            ->selectRaw("COALESCE(client.{$dbCol}, 'Unassigned') as name, COUNT(*) as late_count")
            ->groupBy('name')
            ->pluck('late_count', 'name')
            ->toArray();

        // Query total entries count
        $totalEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->selectRaw("COALESCE(client.{$dbCol}, 'Unassigned') as name, COUNT(*) as total_count")
            ->groupBy('name')
            ->pluck('total_count', 'name')
            ->toArray();

        return MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get()
            ->groupBy(fn ($summary) => data_get($summary->client, $clientField) ?: 'Unassigned')
            ->map(function ($items, $name) use ($lateEntries, $totalEntries) {
                $collection = (float) $items->sum('total_collection');
                $maturity = (float) $items->sum('total_maturity');

                return [
                    'name' => $name,
                    'clients' => $items->count(),
                    'collection' => $collection,
                    'maturity' => $maturity,
                    'efficiency' => $maturity > 0 ? ($collection / $maturity) * 100 : 0,
                    'latest_os' => (float) $items->sum('total_latest_os'),
                    'high_risk' => $items
                        ->filter(fn ($summary) => in_array($summary->latest_rating_category, ['Risky', 'High Risky', 'Most Risky'], true))
                        ->count(),
                    'late_entry' => $lateEntries[$name] ?? 0,
                    'total_entry' => $totalEntries[$name] ?? 0,
                ];
            })
            ->sortByDesc('efficiency')
            ->values()
            ->all();
    }
    
    public function clientDrilldown(Request $request)
    {
        $segment = $request->segment;
        $range = $request->range;

        $query = MonthlySummary::query()
            ->with('client');

        if ($segment === 'IIG' || $segment === 'IIG Operators') {
            $query->whereHas('client', fn ($q) =>
                $q->where('license_billing', 'like', '%iig%')
            );
        } else {
            $query->whereHas('client', fn ($q) =>
                $q->where('license_billing', 'not like', '%iig%')
            );
        }

        $data = $query->get()->map(function ($row) {
            return [
                'client_name' => $row->client->client_name,
                'opening_cr' => $row->opening_cr,
                'opening_os' => $row->total_opening_os,
                'closing_cr' => $row->latest_cr,
                'closing_os' => $row->total_latest_os,
                'mrc' => $row->total_mrc,
                'backlog' => $row->net_backlog_total,
                'collection' => $row->total_collection,
                'payment_plan' => $row->total_payment_plan,
                'shortfall_payment_plan' => $row->shortfall_from_payment_plan ?? 0,
            ];
        });

        return response()->json($data);
    }

    public function clientsIndex(Request $request)
    {
        $segment = $request->segment ?? 'ALL';
        $rangeLabel = $request->range ?? null;

        $latestMonth = $request->month ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->endOfMonth() : MonthlySummary::query()->max('summary_month');

        /*
        |--------------------------------------------------------------------------
        | FIND RANGE
        |--------------------------------------------------------------------------
        */

        $selectedRange = collect($this->crRanges())
            ->firstWhere('label', $rangeLabel);

        /*
        |--------------------------------------------------------------------------
        | RISK CATEGORY
        |--------------------------------------------------------------------------
        */

        $riskCategory = $selectedRange
            ? $this->riskCategoryForRange($selectedRange)
            : null;

        /*
        |--------------------------------------------------------------------------
        | QUERY
        |--------------------------------------------------------------------------
        */

        $query = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth);

        /*
        |--------------------------------------------------------------------------
        | CR RANGE FILTER
        |--------------------------------------------------------------------------
        */
        if ($selectedRange) {
            if ((float)$selectedRange['min'] === 0.0) {
                $query->where(function ($q) use ($selectedRange) {
                    $q->where('latest_cr', '<=', $selectedRange['max'])
                      ->orWhereNull('latest_cr');
                });
            } else {
                $query->where('latest_cr', '>=', $selectedRange['min']);
                if ($selectedRange['max'] !== null) {
                    $query->where('latest_cr', '<=', $selectedRange['max']);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SEGMENT FILTER
        |--------------------------------------------------------------------------
        */

        if ($segment === 'IIG' || $segment === 'IIG Operators') {

            $query->whereHas('client', function ($q) {
                $q->where('license_billing', 'like', '%iig%');
            });

        } elseif ($segment === 'ISP and Other Operators' || $segment === 'ISP & Other Operators' || $segment === 'ISP and Other Operators (High Risk)' || $segment === 'High Risk') {

            $query->whereHas('client', function ($q) {
                $q->where('license_billing', 'not like', '%iig%');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | GET DATA
        |--------------------------------------------------------------------------
        */

        $clients = $query
            ->orderByDesc('latest_cr')
            ->get();

        $latestMonthDate = $latestMonth instanceof \Carbon\Carbon ? $latestMonth : \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();
        $clientIds = $clients->pluck('client_id')->all();
        $previousSummaries = MonthlySummary::whereIn('client_id', $clientIds)
            ->whereDate('summary_month', $previousMonthDate)
            ->get()
            ->keyBy('client_id');

        // Query all logs for these clients created after the previous month date
        $allLogs = \App\Models\ClientLog::whereIn('client_id', $clientIds)
            ->where('created_at', '>', $previousMonthDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('client_id');

        $previousClientStates = [];
        foreach ($clients as $c) {
            $client = $c->client;
            if (!$client) continue;

            $state = $client->toArray();
            
            // Format dates and booleans in current state to match what we put in data-summary
            $state['legal'] = $client->legal ? 'Yes' : 'No';
            $state['other_upstream'] = $client->other_upstream ? 'Yes' : 'No';
            $state['btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date?->format('Y-m-d');
            $state['service_discontinuation_date'] = $client->service_discontinuation_date?->format('Y-m-d');
            $state['nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date?->format('Y-m-d');
            $state['iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date?->format('Y-m-d');

            $clientLogs = $allLogs->get($client->client_id) ?? collect();
            $oldestLogs = $clientLogs->groupBy('field_name')->map(fn($group) => $group->last());

            foreach ($oldestLogs as $fieldName => $log) {
                if (array_key_exists($fieldName, $state)) {
                    $state[$fieldName] = $log->old_value;
                }
            }

            $previousClientStates[$client->client_id] = $state;
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('dashboard.clients.index', [
            'segment' => $segment,
            'range' => $rangeLabel,
            'riskCategory' => $riskCategory,
            'clients' => $clients,
            'month' => $latestMonthDate,
            'previousSummaries' => $previousSummaries,
            'previousClientStates' => $previousClientStates,
        ]);
    }

    public function clientTrend(Client $client)
    {
        $rows = MonthlySummary::query()
            ->where('client_id', $client->client_id)
            ->orderBy('summary_month')
            ->take(12)
            ->get();

        $logs = \App\Models\ClientLog::query()
            ->where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                $formattedDate = 'N/A';
                if ($log->created_at) {
                    try {
                        $formattedDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                    } catch (\Exception $e) {
                        $formattedDate = 'N/A';
                    }
                }

                return [
                    'date' => $formattedDate,
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });

        $snapshots = $rows->map(function ($row) {
            return [
                'month' => $row->summary_month->format('M Y'),
                'opening_rating' => $row->opening_rating_category ?? 'N/A',
                'latest_rating' => $row->latest_rating_category ?? 'N/A',
                'opening_cr' => number_format($row->opening_cr, 2),
                'closing_cr' => number_format($row->latest_cr, 2),
            ];
        })->values();

        return response()->json([
            'labels' => $rows->map(
                fn ($row) => $row->summary_month->format('M y')
            )->values(),
            'opening_cr' => $rows->pluck('opening_cr')->map(fn ($v) => (float) $v)->values(),
            'opening_os' => $rows->pluck('total_opening_os')->map(fn ($v) => (float) $v)->values(),
            'closing_cr' => $rows->pluck('latest_cr')->map(fn ($v) => (float) $v)->values(),
            'closing_os' => $rows->pluck('total_latest_os')->map(fn ($v) => (float) $v)->values(),
            'mrc' => $rows->pluck('total_mrc')->map(fn ($v) => (float) $v)->values(),
            'backlog' => $rows->pluck('net_backlog_total')->map(fn ($v) => (float) $v)->values(),
            'collection' => $rows->pluck('total_collection')->map(fn ($v) => (float) $v)->values(),
            'logs' => $logs,
            'snapshots' => $snapshots,
            'client_status' => $client->client_status ?? 'N/A',
            'service_discontinuation_date' => $client->service_discontinuation_date?->format('Y-m-d') ?? 'N/A',
            'opening_rating_category' => $rows->first()?->opening_rating_category ?? 'N/A',
            'latest_rating_category' => $rows->last()?->latest_rating_category ?? 'N/A',
        ]);
    }

    public function discontinuedClientTrend(Client $client)
    {
        $rows = \App\Models\MonthlySummaryDiscontinued::query()
            ->where('client_id', $client->client_id)
            ->orderBy('summary_month')
            ->take(12)
            ->get();

        $logs = \App\Models\ClientLog::query()
            ->where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                $formattedDate = 'N/A';
                if ($log->created_at) {
                    try {
                        $formattedDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                    } catch (\Exception $e) {
                        $formattedDate = 'N/A';
                    }
                }

                return [
                    'date' => $formattedDate,
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });
        $snapshots = $rows->map(function ($row) {
            return [
                'month' => $row->summary_month->format('M Y'),
                'opening_os' => number_format($row->opening_os, 2),
                'collection' => number_format($row->collection_amount, 2),
                'latest_os' => number_format($row->latest_os, 2),
                'unbilled_total' => number_format($row->unbilled_total, 2),
            ];
        })->values();

        return response()->json([
            'labels' => $rows->map(
                fn ($row) => $row->summary_month->format('M y')
            )->values(),
            'opening_os' => $rows->pluck('opening_os')->map(fn ($v) => (float) $v)->values(),
            'closing_os' => $rows->pluck('latest_os')->map(fn ($v) => (float) $v)->values(),
            'collection' => $rows->pluck('collection_amount')->map(fn ($v) => (float) $v)->values(),
            'unbilled_total' => $rows->pluck('unbilled_total')->map(fn ($v) => (float) $v)->values(),
            'logs' => $logs,
            'snapshots' => $snapshots,
            'client_status' => $client->client_status ?? 'N/A',
            'service_discontinuation_date' => $client->service_discontinuation_date?->format('Y-m-d') ?? 'N/A',
            'opening_rating_category' => 'N/A',
            'latest_rating_category' => 'N/A',
        ]);
    }
    private function dynamicInsights($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $insights = [];
        $formatMil = fn($val) => number_format((float) $val / 1000000, 1) . 'M';

        // Load all monthly summaries for the latest month
        $summaries = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get();

        // 1. High Risk ISP Backlog Concentration
        $ispSummaries = $summaries->reject(fn ($s) => $this->isIigSegment($s));
        $totalIspBacklog = (float) $ispSummaries->sum('net_backlog_total');
        
        $highRiskIspSummaries = $ispSummaries->filter(function ($s) {
            return in_array($s->latest_rating_category, ['Risky', 'High Risky', 'Most Risky'], true);
        });
        $highRiskIspBacklog = (float) $highRiskIspSummaries->sum('net_backlog_total');

        if ($totalIspBacklog > 0) {
            $percentage = ($highRiskIspBacklog / $totalIspBacklog) * 100;
            if ($percentage > 20.0 || $highRiskIspBacklog > 50000000) {
                $insights[] = [
                    'type' => 'risk',
                    'message' => 'Potential risk spike: High-risk ISP customers hold ' . number_format($percentage, 1) . '% (' . $formatMil($highRiskIspBacklog) . ') of the total ISP backlog.',
                    'status' => 'down'
                ];
            }
        }

        // 2. First-10-Days Collection Momentum
        $latestCarbon = \Carbon\Carbon::parse($latestMonth);
        $prevMonth = $latestCarbon->copy()->startOfMonth()->subMonth()->endOfMonth()->toDateString();

        $current10DaysStart = $latestCarbon->copy()->startOfMonth();
        $current10DaysEnd = $latestCarbon->copy()->startOfMonth()->addDays(9)->endOfDay();
        $current10DaysCollection = (float) Collection::query()
            ->whereBetween('collection_datetime', [$current10DaysStart, $current10DaysEnd])
            ->sum('collection_amount');

        $prevCarbon = \Carbon\Carbon::parse($prevMonth);
        $prev10DaysStart = $prevCarbon->copy()->startOfMonth();
        $prev10DaysEnd = $prevCarbon->copy()->startOfMonth()->addDays(9)->endOfDay();
        $prev10DaysCollection = (float) Collection::query()
            ->whereBetween('collection_datetime', [$prev10DaysStart, $prev10DaysEnd])
            ->sum('collection_amount');

        if ($prev10DaysCollection > 0) {
            $momentum = (($current10DaysCollection - $prev10DaysCollection) / $prev10DaysCollection) * 100;
            if ($momentum >= 5.0) {
                $insights[] = [
                    'type' => 'momentum',
                    'message' => 'Collection momentum improved by ' . number_format($momentum, 1) . '% during the first 10 days compared to last month.',
                    'status' => 'up'
                ];
            } elseif ($momentum <= -5.0) {
                $insights[] = [
                    'type' => 'momentum',
                    'message' => 'Collection momentum warning: first 10 days collections dropped by ' . number_format(abs($momentum), 1) . '% compared to last month.',
                    'status' => 'down'
                ];
            }
        }

        // 3. Liquidity Coverage (Collection vs Maturity)
        $totalCollection = (float) $summaries->sum('total_collection');
        $totalMaturity = (float) $summaries->sum('total_maturity');

        if ($totalMaturity > 0) {
            $coverage = ($totalCollection / $totalMaturity) * 100;
            if ($coverage < 85.0) {
                $insights[] = [
                    'type' => 'liquidity',
                    'message' => 'Liquidity Alert: Collection-to-billing maturity coverage is low at ' . number_format($coverage, 1) . '% this month.',
                    'status' => 'down'
                ];
            }
        }

        // 4. Expired Cheques Alert
        $expiredChqTotal = (float) $summaries->sum('expired_chq');
        if ($expiredChqTotal > 0) {
            $insights[] = [
                'type' => 'cheque',
                'message' => 'Cheque Warning: Expired post/undated cheques totaling ' . $formatMil($expiredChqTotal) . ' BDT detected in the portfolio.',
                'status' => 'down'
            ];
        }

        // 5. High-Risk Legal Disputes
        $legalHighRiskSummaries = $summaries->filter(function ($s) {
            return (bool) ($s->client?->legal) && in_array($s->latest_rating_category, ['Risky', 'High Risky', 'Most Risky'], true);
        });
        $legalHighRiskOs = (float) $legalHighRiskSummaries->sum('total_latest_os');
        if ($legalHighRiskOs > 5000000) { // 5M BDT threshold
            $insights[] = [
                'type' => 'legal',
                'message' => 'Legal Risk Alert: High-risk legal dispute accounts hold ' . $formatMil($legalHighRiskOs) . ' BDT outstanding balance.',
                'status' => 'down'
            ];
        }

        // 6. BTRC License Discontinuation Risk
        $nowDate = \Carbon\Carbon::now();
        $targetDate = $nowDate->copy()->addDays(30);

        $btrcRiskSummaries = $summaries->filter(function ($s) use ($nowDate, $targetDate) {
            if (!$s->client?->btrc_license_discontinuation_date) {
                return false;
            }
            $disconDate = \Carbon\Carbon::parse($s->client->btrc_license_discontinuation_date);
            $isPastOrNearExpiring = $disconDate->lte($targetDate);
            return $isPastOrNearExpiring && (float) $s->total_latest_os > 0;
        });

        $btrcRiskCount = $btrcRiskSummaries->count();
        $btrcRiskOs = (float) $btrcRiskSummaries->sum('total_latest_os');

        if ($btrcRiskCount > 0) {
            $insights[] = [
                'type' => 'regulatory',
                'message' => 'Regulatory Risk: ' . $btrcRiskCount . ' client(s) with discontinued/expiring BTRC licenses have outstanding balances totaling ' . $formatMil($btrcRiskOs) . ' BDT.',
                'status' => 'down'
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'stable',
                'message' => 'Portfolio health remains stable. No critical risk or collection thresholds were breached.',
                'status' => 'up'
            ];
        }

        return $insights;
    }

    private function discontinuedMetricComparison(): array
    {
        $monthly = \App\Models\MonthlySummaryDiscontinued::query()
            ->selectRaw('
                summary_month,
                SUM(collection_amount) as total_collection,
                SUM(opening_os) as total_opening_os
            ')
            ->groupBy('summary_month')
            ->orderByDesc('summary_month')
            ->take(6)
            ->get();

        if ($monthly->isEmpty()) {
            return ['mom' => 0.0, 'qoq' => 0.0];
        }

        $ratios = $monthly->map(function ($row) {
            $col = (float) $row->total_collection;
            $os = (float) $row->total_opening_os;
            return [
                'summary_month' => $row->summary_month,
                'ratio' => $os > 0 ? ($col / $os) * 100 : 0.0,
                'collection' => $col,
                'opening_os' => $os,
            ];
        });

        $latest = $ratios->get(0);
        $previous = $ratios->get(1);

        $latestQuarterCollection = $ratios->take(3)->sum('collection');
        $latestQuarterOs = $ratios->take(3)->sum('opening_os');
        $latestQuarterRatio = $latestQuarterOs > 0 ? ($latestQuarterCollection / $latestQuarterOs) * 100 : 0.0;

        $previousQuarterCollection = $ratios->slice(3, 3)->sum('collection');
        $previousQuarterOs = $ratios->slice(3, 3)->sum('opening_os');
        $previousQuarterRatio = $previousQuarterOs > 0 ? ($previousQuarterCollection / $previousQuarterOs) * 100 : 0.0;

        $latestValue = $latest ? $latest['ratio'] : 0.0;
        $previousValue = $previous ? $previous['ratio'] : 0.0;

        return [
            'mom' => $this->percentageChange($latestValue, $previousValue),
            'qoq' => $this->percentageChange($latestQuarterRatio, $previousQuarterRatio),
        ];
    }

    private function discontinuedAnalysis(): array
    {
        $latestSummary = \App\Models\MonthlySummaryDiscontinued::query()
            ->orderByDesc('summary_month')
            ->first();
        $latestMonth = $latestSummary?->summary_month;

        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();

        $currentSummaries = \App\Models\MonthlySummaryDiscontinued::query()
            ->whereDate('summary_month', $latestMonth)
            ->get();

        $previousSummaries = \App\Models\MonthlySummaryDiscontinued::query()
            ->whereDate('summary_month', $previousMonthDate)
            ->get();

        $currentMonthLabel = $latestMonthDate->format('F Y');
        $prevMonthLabel = $previousMonthDate->format('F Y');

        $comparisons = [
            [
                'month' => $currentMonthLabel,
                'clients_count' => $currentSummaries->count(),
                'opening_os_sum' => (float) $currentSummaries->sum('opening_os'),
                'target_sum' => (float) $currentSummaries->sum('target'),
                'collection_sum' => (float) $currentSummaries->sum('collection_amount'),
                'latest_os_sum' => (float) $currentSummaries->sum('latest_os'),
                'shortfall_sum' => (float) $currentSummaries->sum('shortfall_target'),
            ],
            [
                'month' => $prevMonthLabel,
                'clients_count' => $previousSummaries->count(),
                'opening_os_sum' => (float) $previousSummaries->sum('opening_os'),
                'target_sum' => (float) $previousSummaries->sum('target'),
                'collection_sum' => (float) $previousSummaries->sum('collection_amount'),
                'latest_os_sum' => (float) $previousSummaries->sum('latest_os'),
                'shortfall_sum' => (float) $previousSummaries->sum('shortfall_target'),
            ],
        ];

        $categories = [
            'NTTN' => ['os_col' => 'opening_os_nttn', 'latest_col' => 'latest_os_nttn', 'col_col' => 'collection_postpaid_nttn', 'unbilled_col' => 'unbilled_nttn_os'],
            'IIG' => ['os_col' => 'opening_os_iig', 'latest_col' => 'latest_os_iig', 'col_col' => 'collection_postpaid_iig', 'unbilled_col' => 'unbilled_iig_os'],
            'ITC' => ['os_col' => 'opening_os_itc', 'latest_col' => 'latest_os_itc', 'col_col' => 'collection_postpaid_itc', 'unbilled_col' => 'unbilled_itc_os'],
            'NIX' => ['os_col' => 'opening_os_nix', 'latest_col' => 'latest_os_nix', 'col_col' => 'collection_postpaid_nix', 'unbilled_col' => null],
        ];

        $breakdownRows = [];
        foreach ($categories as $catName => $cols) {
            $catClients = $currentSummaries->filter(function ($s) use ($cols) {
                return (float)$s->{$cols['os_col']} > 0 || (float)$s->{$cols['latest_col']} > 0 || (float)$s->{$cols['col_col']} > 0;
            });

            $breakdownRows[] = [
                'category' => $catName,
                'client_count' => $catClients->count(),
                'opening_os' => (float) $currentSummaries->sum($cols['os_col']),
                'collection' => (float) $currentSummaries->sum($cols['col_col']),
                'latest_os' => (float) $currentSummaries->sum($cols['latest_col']),
                'unbilled_os' => $cols['unbilled_col'] ? (float) $currentSummaries->sum($cols['unbilled_col']) : 0.0,
            ];
        }

        return [
            'comparisons' => $comparisons,
            'rows' => $breakdownRows,
            'latestMonth' => $latestMonth,
        ];
    }

    public function discontinuedClientsIndex(\Illuminate\Http\Request $request)
    {
        $category = $request->category ?? 'ALL';
        $latestMonth = $request->month ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->endOfMonth() : \App\Models\MonthlySummaryDiscontinued::query()->max('summary_month');

        if (!$latestMonth) {
            return view('dashboard.clients.discontinued', [
                'category' => $category,
                'clients' => collect(),
                'month' => null,
                'previousSummaries' => collect(),
                'previousClientStates' => [],
            ]);
        }

        $latestMonthDate = $latestMonth instanceof \Carbon\Carbon ? $latestMonth : \Carbon\Carbon::parse($latestMonth);

        $query = \App\Models\MonthlySummaryDiscontinued::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonthDate);

        if ($category !== 'ALL') {
            $cat = strtoupper($category);
            $categories = [
                'NTTN' => ['os_col' => 'opening_os_nttn', 'latest_col' => 'latest_os_nttn', 'col_col' => 'collection_postpaid_nttn'],
                'IIG' => ['os_col' => 'opening_os_iig', 'latest_col' => 'latest_os_iig', 'col_col' => 'collection_postpaid_iig'],
                'ITC' => ['os_col' => 'opening_os_itc', 'latest_col' => 'latest_os_itc', 'col_col' => 'collection_postpaid_itc'],
                'NIX' => ['os_col' => 'opening_os_nix', 'latest_col' => 'latest_os_nix', 'col_col' => 'collection_postpaid_nix'],
            ];
            if (isset($categories[$cat])) {
                $cols = $categories[$cat];
                $query->where(function ($q) use ($cols) {
                    $q->where($cols['os_col'], '>', 0)
                      ->orWhere($cols['latest_col'], '>', 0)
                      ->orWhere($cols['col_col'], '>', 0);
                });
            }
        }

        $clients = $query->get();

        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();
        $clientIds = $clients->pluck('client_id')->all();
        $previousSummaries = \App\Models\MonthlySummaryDiscontinued::whereIn('client_id', $clientIds)
            ->whereDate('summary_month', $previousMonthDate)
            ->get()
            ->keyBy('client_id');

        $allLogs = \App\Models\ClientLog::whereIn('client_id', $clientIds)
            ->where('created_at', '>', $previousMonthDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('client_id');

        $previousClientStates = [];
        foreach ($clients as $c) {
            $client = $c->client;
            if (!$client) continue;

            $state = $client->toArray();
            $state['legal'] = $client->legal ? 'Yes' : 'No';
            $state['other_upstream'] = $client->other_upstream ? 'Yes' : 'No';
            $state['btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date?->format('Y-m-d');
            $state['service_discontinuation_date'] = $client->service_discontinuation_date?->format('Y-m-d');
            $state['nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date?->format('Y-m-d');
            $state['iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date?->format('Y-m-d');

            $clientLogs = $allLogs->get($client->client_id) ?? collect();
            $oldestLogs = $clientLogs->groupBy('field_name')->map(fn($group) => $group->last());

            foreach ($oldestLogs as $fieldName => $log) {
                if (array_key_exists($fieldName, $state)) {
                    $state[$fieldName] = $log->old_value;
                }
            }

            $previousClientStates[$client->client_id] = $state;
        }

        return view('dashboard.clients.discontinued', [
            'category' => $category,
            'clients' => $clients,
            'month' => $latestMonthDate,
            'previousSummaries' => $previousSummaries,
            'previousClientStates' => $previousClientStates,
        ]);
    }

    public function updateWorkflowStatus(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:client,client_id'],
            'status' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
        ]);

        $client = Client::findOrFail($data['client_id']);

        $client->barring_workflow_status = $data['status'];

        if ($data['status'] === 'actioned') {
            $client->client_status = 'Barred';
            $client->barred_at = now();
        } elseif ($data['status'] === 'discontinued_actioned') {
            $client->client_status = 'Discontinued';
            $client->barring_workflow_status = 'discontinued';
        }

        $client->save();

        \App\Models\ManagementGuidanceLog::create([
            'client_id' => $client->client_id,
            'user_id' => auth()->id(),
            'guidance_text' => "Workflow status updated to: " . ucwords(str_replace('_', ' ', $data['status'])),
            'action_taken' => $data['action_taken'] ?? 'Status Update',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client workflow status updated successfully.',
            'client' => $client,
        ]);
    }

    public function logGuidance(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:client,client_id'],
            'guidance_text' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
        ]);

        $log = \App\Models\ManagementGuidanceLog::create([
            'client_id' => $data['client_id'],
            'user_id' => auth()->id(),
            'guidance_text' => $data['guidance_text'],
            'action_taken' => $data['action_taken'] ?? 'Guidance Logged',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guidance logged successfully.',
            'log' => $log->load(['client', 'user']),
        ]);
    }

    public function getGuidanceLogs(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min(100, (int) $request->get('per_page', 10)));

        $query = \App\Models\ManagementGuidanceLog::with(['client', 'user'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('guidance_text', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                        ->orWhere('opus_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($logs->items())->map(function ($log) {
                return [
                    'id' => $log->id,
                    'client_name' => $log->client->client_name ?? 'Unknown Client',
                    'opus_id' => $log->client->opus_id ?? 'N/A',
                    'user_name' => $log->user->name ?? 'System',
                    'user_role' => $log->user ? ($log->user->roles->first()?->name ?? 'User') : 'System',
                    'guidance_text' => $log->guidance_text,
                    'action_taken' => $log->action_taken,
                    'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : '',
                    'created_at_formatted' => $log->created_at ? $log->created_at->format('d M Y, h:i A') : '',
                ];
            }),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
            'per_page' => $logs->perPage(),
        ]);
    }
}
