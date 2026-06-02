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
    public function __invoke(): View
    {
        $latestSummary = MonthlySummary::query()
            ->orderByDesc('summary_month')
            ->first();
        $latestMonth = $latestSummary?->summary_month;

        return view('dashboard.dashboard', [
            'clientCount' => Client::query()->count(),
            'collectionTotal' => $this->currentMonthCollectionTotal($latestMonth),
            'latestOutstanding' => MonthlySummary::where('summary_month', $latestMonth)->sum('total_latest_os'),
            'highRiskCount' => $this->currentMonthHighRiskCount($latestMonth),
            'latestSummary' => $latestSummary,
            'snapshotAnalytics' => $this->snapshotAnalytics(),
            'trendChart' => $this->trendChart(),
            'segmentAnalysis' => $this->segmentAnalysis(),
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
                'collection' => $this->metricComparison('total_collection'),
                'os' => $this->metricComparison('latest_os'),
                'risk' => $this->metricComparison('high_risk'),
            ],
            'collectionEfficiency' => $this->collectionEfficiency($latestMonth),
            'kamPerformance' => $this->kamPerformance($latestMonth),
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
            ->whereIn('latest_rating_category', ['High', 'Critical', 'Severe'])
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
            ->selectRaw('summary_month, SUM(net_backlog_total) as backlog, SUM(total_collection) as collection')
            ->groupBy('summary_month')
            ->orderBy('summary_month')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $row->summary_month->format('M Y'))->values(),
            'backlog' => $rows->map(fn ($row) => (float) $row->backlog)->values(),
            'collection' => $rows->map(fn ($row) => (float) $row->collection)->values(),
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

        $summaries = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get();

        $totalNetBacklog = (float) $summaries->sum('net_backlog_total');
        $totalMrc = (float) $summaries->sum('total_mrc');

        return [
            [
                'name' => 'IIG',
                'latestMonth' => $latestMonth,
                'rows' => $this->segmentRows(
                    $summaries->filter(fn (MonthlySummary $summary) => $this->isIigSegment($summary)),
                    $totalNetBacklog,
                    $totalMrc
                ),
            ],
            [
                'name' => 'ISP and Other Operators',
                'latestMonth' => $latestMonth,
                'rows' => $this->segmentRows(
                    $summaries->reject(fn (MonthlySummary $summary) => $this->isIigSegment($summary)),
                    $totalNetBacklog,
                    $totalMrc
                ),
            ],
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
        return [
            ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50],
            ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00],
            ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50],
            ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99],
            ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49],
            ['label' => '>= 3.50', 'min' => 3.50, 'max' => null],
        ];
    }

    private function inCrRange(float $cr, array $range): bool
    {
        if ($cr < $range['min']) {
            return false;
        }

        return $range['max'] === null || $cr <= $range['max'];
    }

    private function riskCategoryForRange(array $range): string
    {
        return match ($range['label']) {
            '0.00 - 1.50' => 'Best',
            '1.51 - 2.00' => 'Good',
            '2.01 - 2.50' => 'Moderate',
            '2.51 - 2.99' => 'Risky',
            '3.00 - 3.49' => 'High Risky',
            default => 'Most Risky',
        };
    }

    private function isIigSegment(MonthlySummary $summary): bool
    {
        return str_contains(strtolower((string) $summary->client?->service_type_billing), 'iig');
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
                COUNT(DISTINCT client_id) as total_clients,
                SUM(total_collection) as total_collection,
                SUM(total_latest_os) as latest_os,
                SUM(
                    CASE
                        WHEN latest_rating_category IN ("High", "Critical", "Severe")
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

        $rows = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $latestMonth)
            ->get()
            ->groupBy(fn ($summary) => $summary->client?->collection_kam);

        $performance = $rows->map(function ($items, $kam) {

            $collection = (float) $items->sum('total_collection');
            $maturity = (float) $items->sum('total_maturity');

            $efficiency = $maturity > 0
                ? ($collection / $maturity) * 100
                : 0;

            return [
                'kam' => $kam ?: 'Unassigned',
                'efficiency' => $efficiency,
            ];
        })->sortByDesc('efficiency')->values();

        return [
            'best' => $performance->first(),
            'worst' => $performance->last(),
        ];
    }     
    
    public function clientDrilldown(Request $request)
    {
        $segment = $request->segment;
        $range = $request->range;

        $query = MonthlySummary::query()
            ->with('client');

        if ($segment === 'IIG') {
            $query->whereHas('client', fn ($q) =>
                $q->where('service_type_billing', 'like', '%iig%')
            );
        } else {
            $query->whereHas('client', fn ($q) =>
                $q->where('service_type_billing', 'not like', '%iig%')
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

            $query->where('latest_cr', '>=', $selectedRange['min']);

            if ($selectedRange['max'] !== null) {
                $query->where('latest_cr', '<=', $selectedRange['max']);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SEGMENT FILTER
        |--------------------------------------------------------------------------
        */

        if ($segment === 'IIG') {

            $query->whereHas('client', function ($q) {
                $q->whereRaw('LOWER(service_type_billing) = ?', ['iig']);
            });

        } elseif ($segment === 'ISP and Other Operators') {

            $query->whereHas('client', function ($q) {
                $q->whereRaw('LOWER(service_type_billing) != ?', ['iig']);
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
            'month' => $latestMonth,
        ]);
    }

    public function clientTrend(Client $client)
    {
        $rows = MonthlySummary::query()
            ->where('client_id', $client->client_id)
            ->orderBy('summary_month')
            ->take(12)
            ->get();

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

        ]);
    }
}
