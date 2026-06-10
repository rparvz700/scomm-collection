@extends('layouts.app')

@section('title', 'Dashboard | SCOMM Collection')

@push('styles')
    <style>
        .dashboard-slideshow {
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .slides-track {
            display: flex;
            transition: transform .45s ease;
            width: 100%;
        }

        .dashboard-slide {
            min-width: 100%;
            padding: 6px;
            box-sizing: border-box;
        }

        .slide-nav {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;

            width: 40px;
            height: 40px;

            border: none;
            border-radius: 999px;

            background: rgba(2, 68, 224, 0.45);
            color: #fff;

            font-size: 34px;
            cursor: pointer;

            box-shadow: 0 10px 10px rgba(0, 0, 0, 0.44);
        }

        .slide-nav:hover {
            transform: translateY(-50%) scale(1.3);
        }

        .slide-nav.prev {
            left: 16px;
        }

        .slide-nav.next {
            right: 16px;
        }

        .dashboard-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            gap: 22px;
            align-items: stretch;
            margin-bottom: 24px;
        }

        .dashboard-hero-main {
            border-radius: 8px;
            padding: clamp(26px, 4vw, 42px);
            color: #ffffff;
            background:
                linear-gradient(135deg, rgba(3, 105, 161, .94), rgba(15, 118, 110, .9)),
                url("https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1400&q=80") center/cover;
            background-blend-mode: multiply;
            box-shadow: var(--shadow);
        }

        .dashboard-hero-main h1 {
            margin: 0 0 12px;
            font-size: clamp(34px, 5vw, 62px);
            line-height: 1.02;
        }

        .dashboard-hero-main p {
            margin: 0;
            color: rgba(255, 255, 255, .82);
            font-size: 17px;
            line-height: 1.65;
        }

        .dashboard-snapshot {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 24px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .dashboard-snapshot-label {
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .dashboard-snapshot-value {
            margin: 0 0 18px;
            font-size: 34px;
            font-weight: 900;
        }

        .insight-statements {
            display: grid;
            gap: 14px;
        }

        .insight-statement {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .insight-change {
            font-weight: 800;
        }

        .insight-change.up {
            color: #166534;
        }

        .insight-change.down {
            color: #b42318;
        }

        .metric-advanced {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px 16px;
        }

        .metric-advanced span {
            font-size: 11px;
        }

        .metric-advanced strong {
            font-size: clamp(20px, 2.5vw, 26px);
            margin-top: 2px;
        }

        .metric-insights {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            border-top: 1px solid var(--line);
            padding-top: 8px;
            margin-top: 4px;
        }

        .metric-month {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .metric-change {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .metric-change strong {
            font-size: 13px;
            font-weight: 800;
        }

        .metric-change span {
            font-size: 10px;
            color: var(--muted);
        }

        .metric-change.good strong {
            color: #166534;
        }

        .metric-change.bad strong {
            color: #b42318;
        }

        .metric-change.neutral strong {
            color: var(--muted);
        }

        .trend-card {
            margin-top: 18px;
        }

        .trend-canvas-wrap {
            height: 360px;
            padding: 20px;
        }

        .segment-table {
            min-width: 1680px;
        }

        .segment-table th,
        .segment-table td {
            white-space: nowrap;
        }

        .team-performance-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .team-performance-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .team-performance-heading h2 {
            margin: 0;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.08;
        }

        .team-performance-heading span {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .team-performance-summary {
            margin-bottom: 18px;
        }

        .team-performance-table th,
        .team-performance-table td {
            white-space: nowrap;
        }

        .team-performance-table td:first-child {
            white-space: normal;
            min-width: 180px;
        }

        .panel {
            overflow-x: auto;
        }

        @media (max-width: 1100px) {
            .dashboard-hero {
                grid-template-columns: 1fr;
            }

            .team-performance-grid {
                grid-template-columns: 1fr;
            }

            .team-performance-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')

@php
    $currentMonthLabel = optional($latestSummary?->summary_month)->format('M Y') ?? 'No data';
@endphp

<div class="dashboard-slideshow">

    <button class="slide-nav prev" id="prevSlide">‹</button>
    <button class="slide-nav next" id="nextSlide">›</button>

    <div class="slides-track" id="slidesTrack">

        {{-- SLIDE 1 --}}
        <section class="dashboard-slide">

            <section class="dashboard-hero">

                <div class="dashboard-hero-main">
                    <h1>Collection and Risk Dashboard</h1>

                    <p>
                        Monitor monthly CR exposure, live collection movement,
                        and customer risk signals across your recovery portfolio.
                    </p>
                </div>

                <aside class="dashboard-snapshot">

                    <p class="dashboard-snapshot-label">Latest snapshot</p>

                    <p class="dashboard-snapshot-value">
                        {{ $currentMonthLabel }}
                    </p>

                    <div class="insight-statements">

                        <div class="insight-statement">
                            <strong>Collection efficiency:</strong>

                            <span class="insight-change up">
                                {{ number_format($collectionEfficiency, 2) }}%
                            </span>
                        </div>

                        <div class="insight-statement">
                            <strong>AI Insight:</strong>
                            Potential risk spike detected in high backlog ISP customers.
                        </div>

                        <div class="insight-statement">
                            <strong>AI Insight:</strong>
                            Collection momentum improved during first 10 days.
                        </div>

                    </div>

                </aside>

            </section>

        </section>

        {{-- SLIDE 2 --}}
        <section class="dashboard-slide">

        
            <h3>{{ $currentMonthLabel }}</h3>

            <section class="grid four" aria-label="Collection metrics">

                @php
                    $cards = [
                        [
                            'title' => 'Total clients',
                            'value' => number_format($clientCount),
                            'comparison' => $metricComparisons['clients'],
                            'positive' => 'green',
                        ],
                        [
                            'title' => 'Total Billed MRC',
                            'value' => number_format((float) $billedMrcTotal, 2),
                            'comparison' => $metricComparisons['mrc'],
                            'positive' => 'green',
                        ],
                        [
                            'title' => 'Total collection',
                            'value' => number_format((float) $collectionTotal, 2),
                            'comparison' => $metricComparisons['collection'],
                            'positive' => 'green',
                        ],
                        [
                            'title' => 'This month OS',
                            'value' => number_format((float) $currentMonthOs, 2),
                            'comparison' => $metricComparisons['current_month_os'],
                            'positive' => 'red',
                        ],
                        [
                            'title' => 'Latest total OS',
                            'value' => number_format((float) $latestOutstanding, 2),
                            'comparison' => $metricComparisons['os'],
                            'positive' => 'red',
                        ],
                        [
                            'title' => 'High risk clients',
                            'value' => number_format($highRiskCount),
                            'comparison' => $metricComparisons['risk'],
                            'positive' => 'red',
                        ],
                    ];
                @endphp

                @foreach ($cards as $card)

                    <article class="metric metric-advanced">

                        <span>{{ $card['title'] }}</span>

                        <strong>{{ $card['value'] }}</strong>

                        @if ($card['comparison'])

                            <div class="metric-insights">

                                @foreach ([
                                    'MoM' => $card['comparison']['mom'],
                                    'QoQ' => $card['comparison']['qoq'],
                                ] as $label => $change)

                                    @php
                                        $hasChange = $change !== null;
                                        $isIncrease = $hasChange && $change >= 0;

                                        $class =
                                            ! $hasChange
                                                ? 'neutral'
                                                : (
                                                    $card['positive'] === 'green'
                                                        ? ($isIncrease ? 'good' : 'bad')
                                                        : ($isIncrease ? 'bad' : 'good')
                                                );
                                    @endphp

                                    <div class="metric-change {{ $class }}">
                                        <strong>
                                            @if ($hasChange)
                                                {{ number_format(abs($change), 2) }}%
                                                {{ $isIncrease ? '↑' : '↓' }}
                                            @else
                                                N/A
                                            @endif
                                        </strong>

                                        <span>{{ $label }}</span>
                                    </div>

                                @endforeach

                            </div>

                        @endif

                    </article>

                @endforeach

            </section>

            <section class="panel trend-card">

                <div class="panel-header">
                    <h2>Backlog and collection trend</h2>
                    <span>{{ $currentMonthLabel }} · Month on month</span>
                </div>

                <div class="trend-canvas-wrap">
                    <canvas id="backlogCollectionTrend"></canvas>
                </div>

            </section>

        </section>

        {{-- SLIDE 3 --}}
        <section class="dashboard-slide">

            @if(isset($segmentAnalysis[0]))

                <article class="panel">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[0]['name'] }}</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>

                    <table class="segment-table">

                        <thead>
                            <tr>
                                <th>CR Range</th>
                                <th>Risk Category</th>
                                <th>Clients</th>
                                <th>Opening Avg CR</th>
                                <th>Opening OS</th>
                                <th>Latest MRC</th>
                                <th>Net Backlog</th>
                                <th>Collection This Month</th>
                                <th>Closing OS</th>
                                <th>Closing Avg CR</th>
                                <th>Total Net Backlog</th>
                                <th>% Total Net Backlog</th>
                                <th>% Total MRC</th>
                                <th>CR >= 2.51</th>
                                <th>Proposed Barring</th>
                                <th>Already Barred</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($segmentAnalysis[0]['rows'] as $row)

                                <tr>

                                    <td>
                                        <span class="pill {{ in_array($row['risk_category'], ['High', 'Critical', 'Severe'], true) ? 'high' : '' }}">
                                            {{ $row['cr_range'] }}
                                        </span>
                                    </td>

                                    <td>{{ $row['risk_category'] }}</td>

                                    <td class="amount">
                                        <a href="{{ route('dashboard.clients.index', [
                                            'segment' => $segmentAnalysis[0]['name'],
                                            'range' => $row['cr_range'],
                                            'month' => optional($latestSummary?->summary_month)->format('Y-m')
                                        ]) }}"
                                        target="_blank">
                                            {{ number_format($row['client_count']) }}
                                        </a>
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['opening_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['opening_os_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['latest_mrc_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['net_backlog_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['collection_this_month'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_os_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['total_net_backlog'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['percentage_total_net_backlog'], 2) }}%
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['percentage_total_mrc'], 2) }}%
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['cr_above_251']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['proposed_for_barring']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['already_barred']) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </article>

            @endif

        </section>

        {{-- SLIDE 4 --}}
        <section class="dashboard-slide">

            @if(isset($segmentAnalysis[1]))

                <article class="panel">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[1]['name'] }}</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>

                    <table class="segment-table">

                        <thead>
                            <tr>
                                <th>CR Range</th>
                                <th>Risk Category</th>
                                <th>Clients</th>
                                <th>Opening Avg CR</th>
                                <th>Opening OS</th>
                                <th>Latest MRC</th>
                                <th>Net Backlog</th>
                                <th>Collection This Month</th>
                                <th>Closing OS</th>
                                <th>Closing Avg CR</th>
                                <th>Total Net Backlog</th>
                                <th>% Total Net Backlog</th>
                                <th>% Total MRC</th>
                                <th>CR >= 2.51</th>
                                <th>Proposed Barring</th>
                                <th>Already Barred</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($segmentAnalysis[1]['rows'] as $row)

                                <tr>

                                    <td>
                                        <span class="pill {{ in_array($row['risk_category'], ['High', 'Critical', 'Severe'], true) ? 'high' : '' }}">
                                            {{ $row['cr_range'] }}
                                        </span>
                                    </td>

                                    <td>{{ $row['risk_category'] }}</td>

                                    <td class="amount">
                                        <a href="{{ route('dashboard.clients.index', [
                                            'segment' => $segmentAnalysis[1]['name'],
                                            'range' => $row['cr_range'],
                                            'month' => optional($latestSummary?->summary_month)->format('Y-m')
                                        ]) }}"
                                        target="_blank">
                                            {{ number_format($row['client_count']) }}
                                        </a>
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['opening_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['opening_os_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['latest_mrc_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['net_backlog_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['collection_this_month'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_os_sum'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['total_net_backlog'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['percentage_total_net_backlog'], 2) }}%
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['percentage_total_mrc'], 2) }}%
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['cr_above_251']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['proposed_for_barring']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['already_barred']) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </article>

            @endif

        </section>

        {{-- SLIDE 5 --}}
        <section class="dashboard-slide">

            <section class="grid two">

                <article class="panel">

                    <div class="panel-header">
                        <h2>Recent collections</h2>
                        <span>{{ $currentMonthLabel }} · Latest 5</span>
                    </div>

                    @if ($recentCollections->isNotEmpty())

                        <table>
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($recentCollections as $collection)

                                    <tr>
                                        <td>{{ $collection->client->client_name ?? 'Unknown client' }}</td>

                                        <td>
                                            <span class="pill">
                                                {{ str_replace('_', ' ', $collection->collection_type) }}
                                            </span>
                                        </td>

                                        <td class="amount">
                                            {{ number_format((float) $collection->collection_amount, 2) }}
                                        </td>
                                    </tr>

                                @endforeach

                            </tbody>
                        </table>

                    @else

                        <div class="empty">
                            No collection records found.
                        </div>

                    @endif

                </article>

                <article class="panel">

                    <div class="panel-header">
                        <h2>Recent risk events</h2>
                        <span>{{ $currentMonthLabel }} · Latest 5</span>
                    </div>

                    @if ($recentRisks->isNotEmpty())

                        <table>

                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Category</th>
                                    <th>CR</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($recentRisks as $risk)

                                    <tr>

                                        <td>{{ $risk->client->client_name ?? 'Unknown client' }}</td>

                                        <td>
                                            <span class="pill {{ $risk->rating_category === 'High' ? 'high' : '' }}">
                                                {{ $risk->rating_category }}
                                            </span>
                                        </td>

                                        <td class="amount">
                                            {{ number_format((float) $risk->cr_value, 2) }}
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    @else

                        <div class="empty">
                            No risk events found.
                        </div>

                    @endif

                </article>

            </section>

        </section>

        {{-- SLIDE 6 --}}
        <section class="dashboard-slide">

            <section>

                <div class="team-performance-heading">
                    <h2>Team performance</h2>
                    <span>{{ $currentMonthLabel }}</span>
                </div>

                <div class="team-performance-summary insight-statements">

                    @if ($kamPerformance['best'])
                        <div class="insight-statement">
                            <strong>Best performing KAM:</strong>
                            {{ $kamPerformance['best']['kam'] }}

                            <span class="insight-change up">
                                {{ number_format($kamPerformance['best']['efficiency'], 2) }}%
                            </span>
                        </div>
                    @endif

                    @if ($kamPerformance['worst'])
                        <div class="insight-statement">
                            <strong>Worst performing KAM:</strong>
                            {{ $kamPerformance['worst']['kam'] }}

                            <span class="insight-change down">
                                {{ number_format($kamPerformance['worst']['efficiency'], 2) }}%
                            </span>
                        </div>
                    @endif

                </div>

                @php
                    $performanceTables = [
                        'Teams' => $teams,
                        'Collection KAMs' => $collectionKams,
                        'Supervisors' => $supervisors,
                        'SM KAMs' => $smKams,
                    ];
                @endphp

                <div class="team-performance-grid">

                    @foreach ($performanceTables as $title => $rows)

                        <article class="panel">

                            <div class="panel-header">
                                <h2>{{ $title }}</h2>
                                <span>{{ $currentMonthLabel }} · By efficiency</span>
                            </div>

                            @if (! empty($rows))

                                <table class="team-performance-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Clients</th>
                                            <th>Collection</th>
                                            <th>Maturity</th>
                                            <th>Efficiency</th>
                                            <th>Latest OS</th>
                                            <th>High risk</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        @foreach ($rows as $row)

                                            <tr>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="amount">{{ number_format($row['clients']) }}</td>
                                                <td class="amount">{{ number_format($row['collection'], 2) }}</td>
                                                <td class="amount">{{ number_format($row['maturity'], 2) }}</td>
                                                <td class="amount">{{ number_format($row['efficiency'], 2) }}%</td>
                                                <td class="amount">{{ number_format($row['latest_os'], 2) }}</td>
                                                <td class="amount">{{ number_format($row['high_risk']) }}</td>
                                            </tr>

                                        @endforeach

                                    </tbody>
                                </table>

                            @else

                                <div class="empty">
                                    No performance data found.
                                </div>

                            @endif

                        </article>

                    @endforeach

                </div>

            </section>

        </section>

    </div>

</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<script>

    const trendChart = @json($trendChart);

    const trendCanvas = document.getElementById('backlogCollectionTrend');

    if (trendCanvas) {

        new Chart(trendCanvas, {
            type: 'line',

            data: {
                labels: trendChart.labels,

                datasets: [
                    {
                        label: 'Net Backlog',
                        data: trendChart.backlog,
                        borderColor: '#b42318',
                        backgroundColor: 'rgba(180, 35, 24, .12)',
                        tension: .35,
                        fill: true,
                        pointRadius: 4,
                    },

                    {
                        label: 'Collection',
                        data: trendChart.collection,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, .12)',
                        tension: .35,
                        fill: true,
                        pointRadius: 4,
                    },
                ],
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
            },
        });
    }

    const slidesTrack = document.getElementById('slidesTrack');

    const slides = document.querySelectorAll('.dashboard-slide');

    const nextSlideBtn = document.getElementById('nextSlide');

    const prevSlideBtn = document.getElementById('prevSlide');

    let currentSlide = 0;

    function renderSlide() {
        slidesTrack.style.transform =
            `translateX(-${currentSlide * 100}%)`;
    }

    nextSlideBtn?.addEventListener('click', () => {

        if (currentSlide < slides.length - 1) {
            currentSlide++;
            renderSlide();
        }
    });

    prevSlideBtn?.addEventListener('click', () => {

        if (currentSlide > 0) {
            currentSlide--;
            renderSlide();
        }
    });

    document.addEventListener('keydown', (e) => {

        if (e.key === 'ArrowRight') {
            nextSlideBtn.click();
        }

        if (e.key === 'ArrowLeft') {
            prevSlideBtn.click();
        }
    });

</script>



@endpush
