<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Collection;
use App\Models\MonthlySummary;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardOptimizedController extends Controller
{
    public function __invoke(): View
    {
        $startTime = microtime(true);

        $latestSummary = MonthlySummary::query()
            ->orderByDesc('summary_month')
            ->first();
        $latestMonth = $latestSummary?->summary_month;

        $billedMrcTotal = 0.0;
        $collectionTotal = 0.0;
        $discontinuedCollection = 0.0;
        $discontinuedOpeningOs = 0.0;

        if ($latestMonth) {
            $billedMrcTotal = (float) MonthlySummary::whereDate('summary_month', $latestMonth)->sum('total_mrc');
            $collectionTotal = $this->currentMonthCollectionTotal($latestMonth);
            $discontinuedCollection = (float) \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)->sum('collection_amount');
            $discontinuedOpeningOs = (float) \App\Models\MonthlySummaryDiscontinued::whereDate('summary_month', $latestMonth)->sum('opening_os');
        }

        // Run the metrics comparison query ONCE to retrieve the last 6 months of historical metrics.
        // This avoids executing 8 redundant heavy queries.
        $comparisonData = $this->getHistoricalMetricsData();

        return view('dashboard.dashboard_optimized', [
            'clientCount' => Client::query()->count(),
            'activeClientCount' => Client::where('client_status', 'Active')
                ->whereIn('client_id', function ($query) use ($latestMonth) {
                    $query->select('client_id')
                        ->from('monthly_summary')
                        ->whereDate('summary_month', $latestMonth);
                })->count(),
            'discontinuedClientCount' => Client::where('client_status', '!=', 'Active')->count(),
            'discontinuedSubCount' => Client::where(function($q) {
                $q->where('client_status', 'Discontinued')
                  ->orWhere('client_status', 'like', '%discontinued%');
            })->whereIn('client_id', function ($query) use ($latestMonth) {
                $query->select('client_id')
                    ->from('monthly_summary_discontinued')
                    ->whereDate('summary_month', $latestMonth);
            })->count(),
            'barredSubCount' => Client::where(function($q) {
                $q->where('client_status', 'Barred')
                  ->orWhere('client_status', 'like', '%barred%')
                  ->orWhere('client_status', 'like', '%barring%');
            })->whereIn('client_id', function ($query) use ($latestMonth) {
                $query->select('client_id')
                    ->from('monthly_summary_discontinued')
                    ->whereDate('summary_month', $latestMonth);
            })->count(),
            'billedMrcTotal' => $billedMrcTotal,
            'collectionTotal' => $collectionTotal,
            'currentMonthOs' => max($billedMrcTotal - $collectionTotal, 0),
            'latestOutstanding' => $latestMonth ? MonthlySummary::whereDate('summary_month', $latestMonth)->sum('total_latest_os') : 0,
            'highRiskCount' => $this->currentMonthHighRiskCount($latestMonth),
            'latestSummary' => $latestSummary,
            'snapshotAnalytics' => $this->snapshotAnalytics($comparisonData),
            'trendChart' => $this->trendChart(),
            'segmentAnalysis' => $this->segmentAnalysisOptimized($latestMonth),
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
                'clients' => $this->extractMetricComparison($comparisonData, 'total_clients'),
                'active_clients' => $this->extractMetricComparison($comparisonData, 'active_clients'),
                'discontinued_clients' => $this->extractMetricComparison($comparisonData, 'discontinued_clients'),
                'mrc' => $this->extractMetricComparison($comparisonData, 'total_mrc'),
                'collection' => $this->extractMetricComparison($comparisonData, 'total_collection'),
                'current_month_os' => $this->extractMetricComparison($comparisonData, 'current_month_os'),
                'os' => $this->extractMetricComparison($comparisonData, 'latest_os'),
                'risk' => $this->extractMetricComparison($comparisonData, 'high_risk'),
            ],
            'collectionEfficiency' => $this->collectionEfficiency($latestMonth),
            'kamPerformance' => $this->kamPerformanceOptimized($latestMonth),
            'teams' => $this->teamPerformanceOptimized($latestMonth, 'client_team_name'),
            'collectionKams' => $this->teamPerformanceOptimized($latestMonth, 'client_collection_kam'),
            'supervisors' => $this->teamPerformanceOptimized($latestMonth, 'client_collection_supervisor'),
            'smKams' => $this->teamPerformanceOptimized($latestMonth, 'client_sm_kam'),
            'dynamicInsights' => $this->dynamicInsights($latestMonth),
            'discontinuedCollection' => $discontinuedCollection,
            'discontinuedOpeningOs' => $discontinuedOpeningOs,
            'discontinuedComparison' => $this->discontinuedMetricComparison(),
            'discontinuedAnalysis' => $this->discontinuedAnalysisOptimized($latestMonth),
            'elapsedTimeMs' => round((microtime(true) - $startTime) * 1000, 2),
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

    /**
     * Optimizes snapshotAnalytics to use the single historical metrics collection in memory.
     */
    private function snapshotAnalytics(EloquentCollection $comparisonData): array
    {
        $latest = $comparisonData->get(0);
        $previous = $comparisonData->get(1);

        $latestQuarter = $comparisonData->take(3);
        $previousQuarter = $comparisonData->slice(3, 3);

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
            ->selectRaw('summary_month, SUM(net_backlog_total) as backlog, SUM(total_collection) as collection')
            ->groupBy('summary_month')
            ->orderBy('summary_month')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $row->summary_month->format('M Y'))->values(),
            'backlog' => $rows->map(fn ($row) => (float) $row->backlog / 1000000)->values(),
            'collection' => $rows->map(fn ($row) => (float) $row->collection / 1000000)->values(),
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    /**
     * Optimized segment analysis using database GROUP BY rather than loading Eloquent collections into memory.
     */
    private function segmentAnalysisOptimized($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();

        // 1. Fetch Segment Summaries aggregated in SQL
        $segments = ['IIG Operators', 'ISP & Other Operators'];
        $result = [];

        foreach ($segments as $segment) {
            $isIig = ($segment === 'IIG Operators');

            $applySegmentFilter = function($q) use ($isIig) {
                if ($isIig) {
                    $q->where('client_service_type_billing', 'like', '%iig%');
                } else {
                    $q->where(function($sub) {
                        $sub->where('client_service_type_billing', 'not like', '%iig%')
                            ->orWhereNull('client_service_type_billing');
                    });
                }
            };

            // Get segment comparisons
            $comparisons = [];
            foreach ([$latestMonthDate, $previousMonthDate] as $date) {
                $compQuery = MonthlySummary::query()
                    ->whereDate('summary_month', $date);
                $applySegmentFilter($compQuery);

                $comp = $compQuery->selectRaw('
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

            // Get breakdown rows by CR range
            $rows = [];
            $totalBacklogQuery = MonthlySummary::query()->whereDate('summary_month', $latestMonthDate);
            $applySegmentFilter($totalBacklogQuery);
            $totalNetBacklog = (float) $totalBacklogQuery->sum('net_backlog_total');

            $totalMrcQuery = MonthlySummary::query()->whereDate('summary_month', $latestMonthDate);
            $applySegmentFilter($totalMrcQuery);
            $totalMrc = (float) $totalMrcQuery->sum('total_mrc');

            foreach ($this->crRanges() as $range) {
                $min = $range['min'];
                $max = $range['max'];

                $rangeQuery = MonthlySummary::query()
                    ->whereDate('summary_month', $latestMonthDate);
                $applySegmentFilter($rangeQuery);

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
                    AVG(opening_cr) as opening_avg_cr,
                    SUM(total_opening_os) as opening_os_sum,
                    SUM(total_mrc) as latest_mrc_sum,
                    SUM(net_backlog_total) as net_backlog_sum,
                    SUM(total_collection) as collection_this_month,
                    SUM(total_latest_os) as closing_os_sum,
                    AVG(latest_cr) as closing_avg_cr,
                    SUM(CASE WHEN latest_cr >= 2.51 THEN 1 ELSE 0 END) as cr_above_251,
                    SUM(CASE WHEN client_status LIKE "%barred%" OR client_status LIKE "%barring%" THEN 1 ELSE 0 END) as already_barred
                ')->first();

                $netBacklogSum = (float) ($stats?->net_backlog_sum ?? 0);
                $mrcSum = (float) ($stats?->latest_mrc_sum ?? 0);

                $rows[] = [
                    'cr_range' => $range['label'],
                    'risk_category' => $range['category'],
                    'client_count' => (int) ($stats?->client_count ?? 0),
                    'opening_avg_cr' => (float) ($stats?->opening_avg_cr ?? 0),
                    'opening_os_sum' => (float) ($stats?->opening_os_sum ?? 0),
                    'latest_mrc_sum' => $mrcSum,
                    'net_backlog_sum' => $netBacklogSum,
                    'collection_this_month' => (float) ($stats?->collection_this_month ?? 0),
                    'closing_os_sum' => (float) ($stats?->closing_os_sum ?? 0),
                    'closing_avg_cr' => (float) ($stats?->closing_avg_cr ?? 0),
                    'total_net_backlog' => $totalNetBacklog,
                    'percentage_total_net_backlog' => $totalNetBacklog > 0 ? ($netBacklogSum / $totalNetBacklog) * 100 : 0,
                    'percentage_total_mrc' => $totalMrc > 0 ? ($mrcSum / $totalMrc) * 100 : 0,
                    'cr_above_251' => (int) ($stats?->cr_above_251 ?? 0),
                    'proposed_for_barring' => (int) ($stats?->cr_above_251 ?? 0),
                    'already_barred' => (int) ($stats?->already_barred ?? 0),
                ];
            }

            $result[] = [
                'name' => $segment,
                'latestMonth' => $latestMonth,
                'rows' => $rows,
                'comparisons' => $comparisons,
            ];
        }

        return $result;
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

    /**
     * Executes the heavy aggregate comparisons query ONCE.
     */
    private function getHistoricalMetricsData(): EloquentCollection
    {
        return MonthlySummary::query()
            ->selectRaw('
                summary_month,
                COUNT(DISTINCT client_id) as total_clients,
                SUM(CASE WHEN COALESCE(client_status, "") = "Active" THEN 1 ELSE 0 END) as active_clients,
                SUM(CASE WHEN COALESCE(client_status, "") != "Active" THEN 1 ELSE 0 END) as discontinued_clients,
                SUM(total_mrc) as total_mrc,
                SUM(total_collection) as total_collection,
                SUM(net_backlog_total) as backlog,
                SUM(total_collection) as collection,
                CASE
                    WHEN SUM(total_mrc) > SUM(total_collection)
                    THEN SUM(total_mrc) - SUM(total_collection)
                    ELSE 0
                END as current_month_os,
                SUM(total_latest_os) as latest_os,
                SUM(CASE WHEN latest_rating_category IN ("Risky", "High Risky", "Most Risky") THEN 1 ELSE 0 END) as high_risk
            ')
            ->groupBy('summary_month')
            ->orderByDesc('summary_month')
            ->take(6)
            ->get();
    }

    /**
     * Extracts values from the loaded historical metrics collection in memory.
     */
    private function extractMetricComparison(EloquentCollection $monthly, string $metric): array
    {
        if ($monthly->isEmpty()) {
            return ['mom' => 0.0, 'qoq' => 0.0];
        }

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

    /**
     * Optimized KAM performance query. Grouping and aggregating directly in SQL.
     */
    private function kamPerformanceOptimized($latestMonth): array
    {
        if (! $latestMonth) {
            return ['best' => null, 'worst' => null];
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

        $performance = MonthlySummary::query()
            ->whereDate('summary_month', $latestMonth)
            ->selectRaw('
                COALESCE(client_collection_kam, "Unassigned") as kam,
                SUM(total_collection) as collection,
                SUM(total_maturity) as maturity
            ')
            ->groupBy('client_collection_kam')
            ->get()
            ->map(function ($row) use ($lateEntries, $totalEntries) {
                $collection = (float) $row->collection;
                $maturity = (float) $row->maturity;
                $efficiency = $maturity > 0 ? ($collection / $maturity) * 100 : 0.0;

                $lateCount = $lateEntries[$row->kam] ?? 0;
                $totalCount = $totalEntries[$row->kam] ?? 0;
                $lateRate = $totalCount > 0 ? ($lateCount / $totalCount) : 0.0;

                // Combined score: 50% efficiency + 50% lowest late entry rate (100 - late_rate_pct)
                $score = ($efficiency * 0.5) + ((1.0 - $lateRate) * 50);

                return [
                    'kam' => $row->kam,
                    'efficiency' => $efficiency,
                    'late_entry' => $lateCount,
                    'total_entry' => $totalCount,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        return [
            'best' => $performance->first(),
            'worst' => $performance->last(),
        ];
    }

    /**
     * Optimized team performance query. Grouping and aggregating directly in SQL.
     */
    private function teamPerformanceOptimized($latestMonth, string $dbField): array
    {
        if (! $latestMonth) {
            return [];
        }

        // Map monthly_summary field to client table field
        $clientField = match($dbField) {
            'client_team_name' => 'team_name',
            'client_collection_kam' => 'collection_kam',
            'client_collection_supervisor' => 'collection_supervisor',
            'client_sm_kam' => 'sm_kam',
            default => $dbField
        };

        $date = \Carbon\Carbon::parse($latestMonth);
        $lateEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->whereRaw("DATEDIFF(collection.created_at, collection.collection_datetime) > 2")
            ->selectRaw("COALESCE(client.{$clientField}, 'Unassigned') as name, COUNT(*) as late_count")
            ->groupBy('name')
            ->pluck('late_count', 'name')
            ->toArray();

        // Query total entries count
        $totalEntries = \Illuminate\Support\Facades\DB::table('collection')
            ->join('client', 'collection.client_id', '=', 'client.client_id')
            ->whereMonth('collection.collection_datetime', $date->month)
            ->whereYear('collection.collection_datetime', $date->year)
            ->selectRaw("COALESCE(client.{$clientField}, 'Unassigned') as name, COUNT(*) as total_count")
            ->groupBy('name')
            ->pluck('total_count', 'name')
            ->toArray();

        return MonthlySummary::query()
            ->whereDate('summary_month', $latestMonth)
            ->selectRaw("
                COALESCE({$dbField}, 'Unassigned') as name,
                COUNT(*) as clients,
                SUM(total_collection) as collection,
                SUM(total_maturity) as maturity,
                SUM(total_latest_os) as latest_os,
                SUM(CASE WHEN latest_rating_category IN ('Risky', 'High Risky', 'Most Risky') THEN 1 ELSE 0 END) as high_risk
            ")
            ->groupBy($dbField)
            ->get()
            ->map(function ($row) use ($lateEntries, $totalEntries) {
                $collection = (float) $row->collection;
                $maturity = (float) $row->maturity;
                return [
                    'name' => $row->name,
                    'clients' => (int) $row->clients,
                    'collection' => $collection,
                    'maturity' => $maturity,
                    'efficiency' => $maturity > 0 ? ($collection / $maturity) * 100 : 0.0,
                    'latest_os' => (float) $row->latest_os,
                    'high_risk' => (int) $row->high_risk,
                    'late_entry' => $lateEntries[$row->name] ?? 0,
                    'total_entry' => $totalEntries[$row->name] ?? 0,
                ];
            })
            ->sortByDesc('efficiency')
            ->values()
            ->all();
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

    /**
     * Optimized discontinued analysis. Grouping and aggregating directly in SQL.
     */
    private function discontinuedAnalysisOptimized($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $latestMonthDate = \Carbon\Carbon::parse($latestMonth);
        $previousMonthDate = $latestMonthDate->copy()->startOfMonth()->subMonth()->endOfMonth();

        // SQL comparisons aggregation
        $currentComp = \App\Models\MonthlySummaryDiscontinued::query()
            ->whereDate('summary_month', $latestMonthDate)
            ->selectRaw('
                COUNT(*) as clients_count,
                SUM(opening_os) as opening_os_sum,
                SUM(target) as target_sum,
                SUM(collection_amount) as collection_sum,
                SUM(latest_os) as latest_os_sum,
                SUM(shortfall_target) as shortfall_sum
            ')
            ->first();

        $prevComp = \App\Models\MonthlySummaryDiscontinued::query()
            ->whereDate('summary_month', $previousMonthDate)
            ->selectRaw('
                COUNT(*) as clients_count,
                SUM(opening_os) as opening_os_sum,
                SUM(target) as target_sum,
                SUM(collection_amount) as collection_sum,
                SUM(latest_os) as latest_os_sum,
                SUM(shortfall_target) as shortfall_sum
            ')
            ->first();

        $comparisons = [
            [
                'month' => $latestMonthDate->format('F Y'),
                'clients_count' => (int) ($currentComp?->clients_count ?? 0),
                'opening_os_sum' => (float) ($currentComp?->opening_os_sum ?? 0),
                'target_sum' => (float) ($currentComp?->target_sum ?? 0),
                'collection_sum' => (float) ($currentComp?->collection_sum ?? 0),
                'latest_os_sum' => (float) ($currentComp?->latest_os_sum ?? 0),
                'shortfall_sum' => (float) ($currentComp?->shortfall_sum ?? 0),
            ],
            [
                'month' => $previousMonthDate->format('F Y'),
                'clients_count' => (int) ($prevComp?->clients_count ?? 0),
                'opening_os_sum' => (float) ($prevComp?->opening_os_sum ?? 0),
                'target_sum' => (float) ($prevComp?->target_sum ?? 0),
                'collection_sum' => (float) ($prevComp?->collection_sum ?? 0),
                'latest_os_sum' => (float) ($prevComp?->latest_os_sum ?? 0),
                'shortfall_sum' => (float) ($prevComp?->shortfall_sum ?? 0),
            ],
        ];

        // SQL breakdowns aggregation
        $categories = [
            'NTTN' => ['os_col' => 'opening_os_nttn', 'latest_col' => 'latest_os_nttn', 'col_col' => 'collection_postpaid_nttn', 'unbilled_col' => 'unbilled_nttn_os'],
            'IIG' => ['os_col' => 'opening_os_iig', 'latest_col' => 'latest_os_iig', 'col_col' => 'collection_postpaid_iig', 'unbilled_col' => 'unbilled_iig_os'],
            'ITC' => ['os_col' => 'opening_os_itc', 'latest_col' => 'latest_os_itc', 'col_col' => 'collection_postpaid_itc', 'unbilled_col' => 'unbilled_itc_os'],
            'NIX' => ['os_col' => 'opening_os_nix', 'latest_col' => 'latest_os_nix', 'col_col' => 'collection_postpaid_nix', 'unbilled_col' => null],
        ];

        $breakdownRows = [];
        foreach ($categories as $catName => $cols) {
            $catStats = \App\Models\MonthlySummaryDiscontinued::query()
                ->whereDate('summary_month', $latestMonthDate)
                ->selectRaw("
                    SUM(CASE WHEN {$cols['os_col']} > 0 OR {$cols['latest_col']} > 0 OR {$cols['col_col']} > 0 THEN 1 ELSE 0 END) as client_count,
                    SUM({$cols['os_col']}) as opening_os,
                    SUM({$cols['col_col']}) as collection,
                    SUM({$cols['latest_col']}) as latest_os
                    " . ($cols['unbilled_col'] ? ", SUM({$cols['unbilled_col']}) as unbilled_os" : "") . "
                ")
                ->first();

            $breakdownRows[] = [
                'category' => $catName,
                'client_count' => (int) ($catStats?->client_count ?? 0),
                'opening_os' => (float) ($catStats?->opening_os ?? 0),
                'collection' => (float) ($catStats?->collection ?? 0),
                'latest_os' => (float) ($catStats?->latest_os ?? 0),
                'unbilled_os' => $cols['unbilled_col'] ? (float) ($catStats?->unbilled_os ?? 0) : 0.0,
            ];
        }

        return [
            'comparisons' => $comparisons,
            'rows' => $breakdownRows,
            'latestMonth' => $latestMonth,
        ];
    }

    private function dynamicInsights($latestMonth): array
    {
        if (! $latestMonth) {
            return [];
        }

        $insights = [];
        $formatMil = fn($val) => number_format((float) $val / 1000000, 1) . 'M';

        // Load all monthly summaries for the latest month (without relation)
        $summaries = MonthlySummary::query()
            ->whereDate('summary_month', $latestMonth)
            ->get();

        // 1. High Risk ISP Backlog Concentration
        $ispSummaries = $summaries->reject(function ($s) {
            return str_contains(strtolower((string) $s->client_service_type_billing), 'iig');
        });
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
            return (bool) ($s->client_legal) && in_array($s->latest_rating_category, ['Risky', 'High Risky', 'Most Risky'], true);
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
            if (!$s->client_btrc_license_discontinuation_date) {
                return false;
            }
            $disconDate = \Carbon\Carbon::parse($s->client_btrc_license_discontinuation_date);
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
}
