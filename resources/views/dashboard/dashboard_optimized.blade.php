@extends('layouts.app')

@section('title', 'Dashboard (Optimized) | SCOMM Collection')

@section('content')

@php
    $currentMonthLabel = optional($latestSummary?->summary_month)->format('M Y') ?? 'No data';

    $formatMil = function($val) {
        if ($val === null || $val === '') return '';
        return number_format((float) $val / 1000000, 1) . 'M';
    };

    $renderMoMArrow = function($currVal, $prevVal, $type) {
        $curr = (float) $currVal;
        $prev = (float) $prevVal;
        if ($curr === $prev) return '';
        
        if ($type === 'good-up') {
            if ($curr > $prev) {
                return '<span style="color: #16a34a; margin-left: 4px; font-weight: bold;">▲</span>';
            } else {
                return '<span style="color: #dc2626; margin-left: 4px; font-weight: bold;">▼</span>';
            }
        } else { // good-down
            if ($curr < $prev) {
                return '<span style="color: #16a34a; margin-left: 4px; font-weight: bold;">▼</span>';
            } else {
                return '<span style="color: #dc2626; margin-left: 4px; font-weight: bold;">▲</span>';
            }
        }
    };
@endphp

@push('styles')
    <style>
        :root {
            --shadow: 0 10px 25px rgba(15, 23, 42, 0.16), 0 2px 10px rgba(15, 23, 42, 0.08);
        }

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
            min-height: 65vh;
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
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            width: 100%;
            min-width: 100%;
        }

        .breakdown-table {
            min-width: 1350px;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
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

        .subtotal-row {
            background-color: #f1f5f9;
        }
        .grand-subtotal-row {
            background-color: #cbd5e1;
        }
        .subtotal-row td,
        .grand-subtotal-row td {
            color: var(--ink) !important;
            border-top: 2px solid var(--line);
            border-bottom: 2px solid var(--line);
        }

        .breakdown-table td,
        .breakdown-table td a {
            font-weight: 500 !important;
        }
        .breakdown-table tr.subtotal-row td,
        .breakdown-table tr.subtotal-row td a,
        .breakdown-table tr.grand-subtotal-row td,
        .breakdown-table tr.grand-subtotal-row td a {
            font-weight: 800 !important;
        }

        @media (max-width: 1100px) {
            .dashboard-hero {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .team-performance-grid {
                grid-template-columns: 1fr;
            }

            .team-performance-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            body {
                background: #ffffff !important;
                color: #162033 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .topbar,
            .slide-nav,
            #exportPdfBtn,
            .no-print {
                display: none !important;
            }

            .page {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: none !important;
                box-shadow: none !important;
            }

            .dashboard-slideshow {
                width: 100% !important;
                overflow: visible !important;
                background: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #slidesTrack {
                display: block !important;
                transform: none !important;
                width: 100% !important;
            }

            .dashboard-slide {
                width: 100% !important;
                min-width: 100% !important;
                page-break-before: always !important;
                break-before: page !important;
                zoom: 0.62;
                padding: 0 !important;
                margin: 0 0 24px 0 !important;
            }

            .panel {
                overflow: visible !important;
                overflow-x: visible !important;
                max-width: none !important;
                width: 100% !important;
                background: #ffffff !important;
                box-shadow: none !important;
                border: 1px solid var(--line) !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin-bottom: 20px !important;
            }

            .segment-table {
                width: 100% !important;
                min-width: 0 !important;
                table-layout: auto !important;
            }

            .segment-table th,
            .segment-table td {
                font-size: 11px !important;
                padding: 10px 12px !important;
                white-space: normal !important;
            }

            .team-performance-grid,
            .grid.two {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
                width: 100% !important;
            }
        }
    </style>
@endpush

<div class="dashboard-slideshow">

    <button class="slide-nav prev" id="prevSlide">‹</button>
    <button class="slide-nav next" id="nextSlide">›</button>

    <div class="slides-track" id="slidesTrack">

        {{-- SLIDE 1 --}}
        <section class="dashboard-slide">

            <section class="dashboard-hero">

                <div class="dashboard-hero-main">
                    <h1>Collection and Risk Dashboard (Optimized)</h1>

                    <p>
                        Monitor monthly CR exposure, live collection movement,
                        and customer risk signals across your recovery portfolio.
                    </p>
                    <div style="margin-top: 20px; background: rgba(255, 255, 255, 0.15); padding: 12px 20px; border-radius: 8px; display: inline-block; font-size: 14px; font-weight: 800;">
                         ⚡ Page Loaded in: <span style="color: #22c55e;">{{ $elapsedTimeMs }} ms</span> (Optimized DB Queries & SQL Aggregation)
                    </div>
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

                        @foreach ($dynamicInsights as $insight)
                            <div class="insight-statement">
                                <strong>Insight:</strong>
                                <span class="insight-change {{ $insight['status'] }}">
                                    {{ $insight['message'] }}
                                </span>
                            </div>
                        @endforeach

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
                            'title' => 'Active clients',
                            'value' => number_format($activeClientCount),
                            'comparison' => $metricComparisons['active_clients'],
                            'positive' => 'green',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'Discont./Barred Clients',
                            'value' => '<div style="display:flex; justify-content:space-between; width:100%;">
                                            <div>
                                                <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                    ' . number_format($barredSubCount) . '
                                                </span>
                                                <span style="font-size:11px; font-weight:500; color:#b91c1c; text-transform:uppercase;">
                                                    Barred
                                                </span>
                                            </div>
                                            <div>
                                                <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                    ' . number_format($discontinuedSubCount) . '
                                                </span>
                                                <span style="font-size:11px; font-weight:500; color:#b91c1c; text-transform:uppercase;">
                                                    Discont.
                                                </span>
                                            </div>
                                        </div>',
                            'is_html' => true,
                            'comparison' => $metricComparisons['discontinued_clients'],
                            'positive' => 'red',
                            'bg_style' => 'background-color: #fef2f2 !important; border-color: #fecaca !important;',
                            'text_style' => 'color: #991b1b !important;',
                            'muted_style' => 'color: #b91c1c !important;',
                        ],
                        [
                            'title' => 'Total Billed MRC',
                            'value' => $formatMil($billedMrcTotal),
                            'comparison' => $metricComparisons['mrc'],
                            'positive' => 'green',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'Total collection',
                            'value' => $formatMil($collectionTotal),
                            'comparison' => $metricComparisons['collection'],
                            'positive' => 'green',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'This month OS',
                            'value' => $formatMil($currentMonthOs),
                            'comparison' => $metricComparisons['current_month_os'],
                            'positive' => 'red',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'Latest total OS',
                            'value' => $formatMil($latestOutstanding),
                            'comparison' => $metricComparisons['os'],
                            'positive' => 'red',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'High risk clients',
                            'value' => number_format($highRiskCount),
                            'comparison' => $metricComparisons['risk'],
                            'positive' => 'red',
                            'bg_style' => 'background-color: #f0fdf4 !important; border-color: #bbf7d0 !important;',
                            'text_style' => 'color: #166534 !important;',
                            'muted_style' => 'color: #15803d !important;',
                        ],
                        [
                            'title' => 'Discontinued Collection vs OS',
                            'value' => '<div style="display:flex; justify-content:space-between; width:100%;">
                                            <div>
                                                <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                    ' . $formatMil($discontinuedCollection) . '
                                                </span>
                                                <span style="font-size:11px; font-weight:500; color:#166534; text-transform:uppercase;">
                                                    Coll.
                                                </span>
                                            </div>
                                            <div>
                                                <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                    ' . $formatMil($discontinuedOpeningOs) . '
                                                </span>
                                                <span style="font-size:11px; font-weight:500; color:#b91c1c; text-transform:uppercase;">
                                                    OS
                                                </span>
                                            </div>
                                        </div>',
                            'is_html' => true,
                            'comparison' => $discontinuedComparison,
                            'positive' => 'green',
                            'bg_style' => 'background-color: #fef2f2 !important; border-color: #fecaca !important;',
                            'text_style' => 'color: #991b1b !important;',
                            'muted_style' => 'color: #b91c1c !important;',
                        ],
                    ];
                @endphp

                @foreach ($cards as $card)

                    <article class="metric metric-advanced" style="{{ $card['bg_style'] ?? '' }}">

                        <span style="{{ $card['muted_style'] ?? '' }}">{{ $card['title'] }}</span>

                        @if ($card['is_html'] ?? false)
                            <strong style="{{ $card['text_style'] ?? '' }}">{!! $card['value'] !!}</strong>
                        @else
                            <strong style="{{ $card['text_style'] ?? '' }}">{{ $card['value'] }}</strong>
                        @endif

                        @if ($card['comparison'])

                            <div class="metric-insights" style="{{ isset($card['bg_style']) ? 'border-top-color: rgba(0,0,0,0.06) !important;' : '' }}">

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

                                    <div class="metric-change {{ $class }}" style="{{ $label === 'QoQ' ? 'align-items: flex-end; text-align: right;' : '' }}">
                                        <strong>
                                            @if ($hasChange)
                                                {{ number_format(abs($change), 2) }}%
                                                {{ $isIncrease ? '↑' : '↓' }}
                                            @else
                                                N/A
                                            @endif
                                        </strong>

                                        <span style="{{ $card['muted_style'] ?? '' }}">{{ $label }}</span>
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

                <article class="panel" style="margin-bottom: 24px;">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[0]['name'] }} - Month-on-Month Summary</h2>
                    </div>

                    <table class="segment-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="amount">Number of Clients</th>
                                <th class="amount">Opening OS</th>
                                <th class="amount">MRC</th>
                                <th class="amount">Net Backlog</th>
                                <th class="amount">Opening Avg. CR</th>
                                <th class="amount">Latest OS</th>
                                <th class="amount">Latest Avg. CR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $current0 = $segmentAnalysis[0]['comparisons'][0];
                                $previous0 = $segmentAnalysis[0]['comparisons'][1];
                            @endphp
                            <tr>
                                <td><strong>{{ $current0['month'] }}</strong></td>
                                <td class="amount">
                                    {{ number_format($current0['clients_count']) }}
                                    {!! $renderMoMArrow($current0['clients_count'], $previous0['clients_count'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current0['opening_os_sum']) }}
                                    {!! $renderMoMArrow($current0['opening_os_sum'], $previous0['opening_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current0['mrc_sum']) }}
                                    {!! $renderMoMArrow($current0['mrc_sum'], $previous0['mrc_sum'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current0['backlog_sum']) }}
                                    {!! $renderMoMArrow($current0['backlog_sum'], $previous0['backlog_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($current0['opening_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($current0['opening_cr_avg'], $previous0['opening_cr_avg'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current0['latest_os_sum']) }}
                                    {!! $renderMoMArrow($current0['latest_os_sum'], $previous0['latest_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($current0['latest_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($current0['latest_cr_avg'], $previous0['latest_cr_avg'], 'good-down') !!}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ $previous0['month'] }}</strong></td>
                                <td class="amount">{{ number_format($previous0['clients_count']) }}</td>
                                <td class="amount">{{ $formatMil($previous0['opening_os_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previous0['mrc_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previous0['backlog_sum']) }}</td>
                                <td class="amount">{{ number_format($previous0['opening_cr_avg'], 2) }}</td>
                                <td class="amount">{{ $formatMil($previous0['latest_os_sum']) }}</td>
                                <td class="amount">{{ number_format($previous0['latest_cr_avg'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </article>

                <article class="panel">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[0]['name'] }} - CR Range Breakdown</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>

                    <table class="segment-table breakdown-table">

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
                            @php
                                $subtotal1_client_count = 0;
                                $subtotal1_opening_os_sum = 0;
                                $subtotal1_latest_mrc_sum = 0;
                                $subtotal1_net_backlog_sum = 0;
                                $subtotal1_collection_this_month = 0;
                                $subtotal1_closing_os_sum = 0;
                                $subtotal1_cr_above_251 = 0;
                                $subtotal1_proposed_for_barring = 0;
                                $subtotal1_already_barred = 0;
                                $subtotal1_opening_cr_weighted_sum = 0;
                                $subtotal1_closing_cr_weighted_sum = 0;
                                $subtotal1_percentage_total_net_backlog = 0;
                                $subtotal1_percentage_total_mrc = 0;

                                $subtotal2_client_count = 0;
                                $subtotal2_opening_os_sum = 0;
                                $subtotal2_latest_mrc_sum = 0;
                                $subtotal2_net_backlog_sum = 0;
                                $subtotal2_collection_this_month = 0;
                                $subtotal2_closing_os_sum = 0;
                                $subtotal2_cr_above_251 = 0;
                                $subtotal2_proposed_for_barring = 0;
                                $subtotal2_already_barred = 0;
                                $subtotal2_opening_cr_weighted_sum = 0;
                                $subtotal2_closing_cr_weighted_sum = 0;
                                $subtotal2_percentage_total_net_backlog = 0;
                                $subtotal2_percentage_total_mrc = 0;
                            @endphp

                            @foreach ($segmentAnalysis[0]['rows'] as $row)
                                @php
                                    if ($loop->iteration <= 3) {
                                        $subtotal1_client_count += $row['client_count'];
                                        $subtotal1_opening_os_sum += $row['opening_os_sum'];
                                        $subtotal1_latest_mrc_sum += $row['latest_mrc_sum'];
                                        $subtotal1_net_backlog_sum += $row['net_backlog_sum'];
                                        $subtotal1_collection_this_month += $row['collection_this_month'];
                                        $subtotal1_closing_os_sum += $row['closing_os_sum'];
                                        $subtotal1_cr_above_251 += $row['cr_above_251'];
                                        $subtotal1_proposed_for_barring += $row['proposed_for_barring'];
                                        $subtotal1_already_barred += $row['already_barred'];
                                        $subtotal1_opening_cr_weighted_sum += $row['opening_avg_cr'] * $row['client_count'];
                                        $subtotal1_closing_cr_weighted_sum += $row['closing_avg_cr'] * $row['client_count'];
                                        $subtotal1_percentage_total_net_backlog += $row['percentage_total_net_backlog'];
                                        $subtotal1_percentage_total_mrc += $row['percentage_total_mrc'];
                                    } else {
                                        $subtotal2_client_count += $row['client_count'];
                                        $subtotal2_opening_os_sum += $row['opening_os_sum'];
                                        $subtotal2_latest_mrc_sum += $row['latest_mrc_sum'];
                                        $subtotal2_net_backlog_sum += $row['net_backlog_sum'];
                                        $subtotal2_collection_this_month += $row['collection_this_month'];
                                        $subtotal2_closing_os_sum += $row['closing_os_sum'];
                                        $subtotal2_cr_above_251 += $row['cr_above_251'];
                                        $subtotal2_proposed_for_barring += $row['proposed_for_barring'];
                                        $subtotal2_already_barred += $row['already_barred'];
                                        $subtotal2_opening_cr_weighted_sum += $row['opening_avg_cr'] * $row['client_count'];
                                        $subtotal2_closing_cr_weighted_sum += $row['closing_avg_cr'] * $row['client_count'];
                                        $subtotal2_percentage_total_net_backlog += $row['percentage_total_net_backlog'];
                                        $subtotal2_percentage_total_mrc += $row['percentage_total_mrc'];
                                    }
                                @endphp

                                <tr>

                                    <td>
                                        <span class="pill {{ in_array($row['risk_category'], ['Risky', 'High Risky', 'Most Risky'], true) ? 'high' : '' }}">
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
                                        {{ $formatMil($row['opening_os_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['latest_mrc_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['net_backlog_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['collection_this_month']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['closing_os_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['total_net_backlog']) }}
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

                                @if ($loop->iteration === 3)
                                    <tr class="subtotal-row">
                                        <td>Subtotal (0.00 - 2.50)</td>
                                        <td>Best - Moderate</td>
                                        <td class="amount">{{ number_format($subtotal1_client_count) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal1_client_count > 0 ? ($subtotal1_opening_cr_weighted_sum / $subtotal1_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal1_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_closing_os_sum) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal1_client_count > 0 ? ($subtotal1_closing_cr_weighted_sum / $subtotal1_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_already_barred) }}</td>
                                    </tr>
                                @endif

                                @if ($loop->iteration === 6)
                                    <tr class="subtotal-row">
                                        <td>Subtotal (>= 2.51)</td>
                                        <td>Risky - Most Risky</td>
                                        <td class="amount">{{ number_format($subtotal2_client_count) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal2_client_count > 0 ? ($subtotal2_opening_cr_weighted_sum / $subtotal2_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal2_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_closing_os_sum) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal2_client_count > 0 ? ($subtotal2_closing_cr_weighted_sum / $subtotal2_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal2_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal2_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_already_barred) }}</td>
                                    </tr>

                                    <tr class="grand-subtotal-row">
                                        <td>Total / Grand Subtotal</td>
                                        <td>All Categories</td>
                                        <td class="amount">{{ number_format($subtotal1_client_count + $subtotal2_client_count) }}</td>
                                        <td class="amount">
                                            @php
                                                $grand_client_count = $subtotal1_client_count + $subtotal2_client_count;
                                                $grand_opening_avg = $grand_client_count > 0 ? (($subtotal1_opening_cr_weighted_sum + $subtotal2_opening_cr_weighted_sum) / $grand_client_count) : 0;
                                            @endphp
                                            {{ number_format($grand_opening_avg, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal1_opening_os_sum + $subtotal2_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_latest_mrc_sum + $subtotal2_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_net_backlog_sum + $subtotal2_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_collection_this_month + $subtotal2_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_closing_os_sum + $subtotal2_closing_os_sum) }}</td>
                                        <td class="amount">
                                            @php
                                                $grand_closing_avg = $grand_client_count > 0 ? (($subtotal1_closing_cr_weighted_sum + $subtotal2_closing_cr_weighted_sum) / $grand_client_count) : 0;
                                            @endphp
                                            {{ number_format($grand_closing_avg, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_net_backlog + $subtotal2_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_mrc + $subtotal2_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_cr_above_251 + $subtotal2_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_proposed_for_barring + $subtotal2_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_already_barred + $subtotal2_already_barred) }}</td>
                                    </tr>
                                @endif

                            @endforeach

                        </tbody>

                    </table>

                </article>

            @endif

        </section>

        {{-- SLIDE 4 --}}
        <section class="dashboard-slide">

            @if(isset($segmentAnalysis[1]))

                <article class="panel" style="margin-bottom: 24px;">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[1]['name'] }} - Month-on-Month Summary</h2>
                    </div>

                    <table class="segment-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="amount">Number of Clients</th>
                                <th class="amount">Opening OS</th>
                                <th class="amount">MRC</th>
                                <th class="amount">Net Backlog</th>
                                <th class="amount">Opening Avg. CR</th>
                                <th class="amount">Latest OS</th>
                                <th class="amount">Latest Avg. CR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $current1 = $segmentAnalysis[1]['comparisons'][0];
                                $previous1 = $segmentAnalysis[1]['comparisons'][1];
                            @endphp
                            <tr>
                                <td><strong>{{ $current1['month'] }}</strong></td>
                                <td class="amount">
                                    {{ number_format($current1['clients_count']) }}
                                    {!! $renderMoMArrow($current1['clients_count'], $previous1['clients_count'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current1['opening_os_sum']) }}
                                    {!! $renderMoMArrow($current1['opening_os_sum'], $previous1['opening_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current1['mrc_sum']) }}
                                    {!! $renderMoMArrow($current1['mrc_sum'], $previous1['mrc_sum'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current1['backlog_sum']) }}
                                    {!! $renderMoMArrow($current1['backlog_sum'], $previous1['backlog_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($current1['opening_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($current1['opening_cr_avg'], $previous1['opening_cr_avg'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($current1['latest_os_sum']) }}
                                    {!! $renderMoMArrow($current1['latest_os_sum'], $previous1['latest_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($current1['latest_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($current1['latest_cr_avg'], $previous1['latest_cr_avg'], 'good-down') !!}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ $previous1['month'] }}</strong></td>
                                <td class="amount">{{ number_format($previous1['clients_count']) }}</td>
                                <td class="amount">{{ $formatMil($previous1['opening_os_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previous1['mrc_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previous1['backlog_sum']) }}</td>
                                <td class="amount">{{ number_format($previous1['opening_cr_avg'], 2) }}</td>
                                <td class="amount">{{ $formatMil($previous1['latest_os_sum']) }}</td>
                                <td class="amount">{{ number_format($previous1['latest_cr_avg'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </article>

                <article class="panel">

                    <div class="panel-header">
                        <h2>{{ $segmentAnalysis[1]['name'] }} - CR Range Breakdown</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>

                    <table class="segment-table breakdown-table">

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
                            @php
                                $subtotal1_client_count = 0;
                                $subtotal1_opening_os_sum = 0;
                                $subtotal1_latest_mrc_sum = 0;
                                $subtotal1_net_backlog_sum = 0;
                                $subtotal1_collection_this_month = 0;
                                $subtotal1_closing_os_sum = 0;
                                $subtotal1_cr_above_251 = 0;
                                $subtotal1_proposed_for_barring = 0;
                                $subtotal1_already_barred = 0;
                                $subtotal1_opening_cr_weighted_sum = 0;
                                $subtotal1_closing_cr_weighted_sum = 0;
                                $subtotal1_percentage_total_net_backlog = 0;
                                $subtotal1_percentage_total_mrc = 0;

                                $subtotal2_client_count = 0;
                                $subtotal2_opening_os_sum = 0;
                                $subtotal2_latest_mrc_sum = 0;
                                $subtotal2_net_backlog_sum = 0;
                                $subtotal2_collection_this_month = 0;
                                $subtotal2_closing_os_sum = 0;
                                $subtotal2_cr_above_251 = 0;
                                $subtotal2_proposed_for_barring = 0;
                                $subtotal2_already_barred = 0;
                                $subtotal2_opening_cr_weighted_sum = 0;
                                $subtotal2_closing_cr_weighted_sum = 0;
                                $subtotal2_percentage_total_net_backlog = 0;
                                $subtotal2_percentage_total_mrc = 0;
                            @endphp

                            @foreach ($segmentAnalysis[1]['rows'] as $row)
                                @php
                                    if ($loop->iteration <= 3) {
                                        $subtotal1_client_count += $row['client_count'];
                                        $subtotal1_opening_os_sum += $row['opening_os_sum'];
                                        $subtotal1_latest_mrc_sum += $row['latest_mrc_sum'];
                                        $subtotal1_net_backlog_sum += $row['net_backlog_sum'];
                                        $subtotal1_collection_this_month += $row['collection_this_month'];
                                        $subtotal1_closing_os_sum += $row['closing_os_sum'];
                                        $subtotal1_cr_above_251 += $row['cr_above_251'];
                                        $subtotal1_proposed_for_barring += $row['proposed_for_barring'];
                                        $subtotal1_already_barred += $row['already_barred'];
                                        $subtotal1_opening_cr_weighted_sum += $row['opening_avg_cr'] * $row['client_count'];
                                        $subtotal1_closing_cr_weighted_sum += $row['closing_avg_cr'] * $row['client_count'];
                                        $subtotal1_percentage_total_net_backlog += $row['percentage_total_net_backlog'];
                                        $subtotal1_percentage_total_mrc += $row['percentage_total_mrc'];
                                    } else {
                                        $subtotal2_client_count += $row['client_count'];
                                        $subtotal2_opening_os_sum += $row['opening_os_sum'];
                                        $subtotal2_latest_mrc_sum += $row['latest_mrc_sum'];
                                        $subtotal2_net_backlog_sum += $row['net_backlog_sum'];
                                        $subtotal2_collection_this_month += $row['collection_this_month'];
                                        $subtotal2_closing_os_sum += $row['closing_os_sum'];
                                        $subtotal2_cr_above_251 += $row['cr_above_251'];
                                        $subtotal2_proposed_for_barring += $row['proposed_for_barring'];
                                        $subtotal2_already_barred += $row['already_barred'];
                                        $subtotal2_opening_cr_weighted_sum += $row['opening_avg_cr'] * $row['client_count'];
                                        $subtotal2_closing_cr_weighted_sum += $row['closing_avg_cr'] * $row['client_count'];
                                        $subtotal2_percentage_total_net_backlog += $row['percentage_total_net_backlog'];
                                        $subtotal2_percentage_total_mrc += $row['percentage_total_mrc'];
                                    }
                                @endphp

                                <tr>

                                    <td>
                                        <span class="pill {{ in_array($row['risk_category'], ['Risky', 'High Risky', 'Most Risky'], true) ? 'high' : '' }}">
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
                                        {{ $formatMil($row['opening_os_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['latest_mrc_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['net_backlog_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['collection_this_month']) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['closing_os_sum']) }}
                                    </td>

                                    <td class="amount">
                                        {{ number_format($row['closing_avg_cr'], 2) }}
                                    </td>

                                    <td class="amount">
                                        {{ $formatMil($row['total_net_backlog']) }}
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

                                @if ($loop->iteration === 3)
                                    <tr class="subtotal-row">
                                        <td>Subtotal (0.00 - 2.50)</td>
                                        <td>Best - Moderate</td>
                                        <td class="amount">{{ number_format($subtotal1_client_count) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal1_client_count > 0 ? ($subtotal1_opening_cr_weighted_sum / $subtotal1_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal1_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_closing_os_sum) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal1_client_count > 0 ? ($subtotal1_closing_cr_weighted_sum / $subtotal1_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_already_barred) }}</td>
                                    </tr>
                                @endif

                                @if ($loop->iteration === 6)
                                    <tr class="subtotal-row">
                                        <td>Subtotal (>= 2.51)</td>
                                        <td>Risky - Most Risky</td>
                                        <td class="amount">{{ number_format($subtotal2_client_count) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal2_client_count > 0 ? ($subtotal2_opening_cr_weighted_sum / $subtotal2_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal2_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal2_closing_os_sum) }}</td>
                                        <td class="amount">
                                            {{ number_format($subtotal2_client_count > 0 ? ($subtotal2_closing_cr_weighted_sum / $subtotal2_client_count) : 0, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal2_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal2_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal2_already_barred) }}</td>
                                    </tr>

                                    <tr class="grand-subtotal-row">
                                        <td>Total / Grand Subtotal</td>
                                        <td>All Categories</td>
                                        <td class="amount">{{ number_format($subtotal1_client_count + $subtotal2_client_count) }}</td>
                                        <td class="amount">
                                            @php
                                                $grand_client_count = $subtotal1_client_count + $subtotal2_client_count;
                                                $grand_opening_avg = $grand_client_count > 0 ? (($subtotal1_opening_cr_weighted_sum + $subtotal2_opening_cr_weighted_sum) / $grand_client_count) : 0;
                                            @endphp
                                            {{ number_format($grand_opening_avg, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($subtotal1_opening_os_sum + $subtotal2_opening_os_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_latest_mrc_sum + $subtotal2_latest_mrc_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_net_backlog_sum + $subtotal2_net_backlog_sum) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_collection_this_month + $subtotal2_collection_this_month) }}</td>
                                        <td class="amount">{{ $formatMil($subtotal1_closing_os_sum + $subtotal2_closing_os_sum) }}</td>
                                        <td class="amount">
                                            @php
                                                $grand_closing_avg = $grand_client_count > 0 ? (($subtotal1_closing_cr_weighted_sum + $subtotal2_closing_cr_weighted_sum) / $grand_client_count) : 0;
                                            @endphp
                                            {{ number_format($grand_closing_avg, 2) }}
                                        </td>
                                        <td class="amount">{{ $formatMil($row['total_net_backlog']) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_net_backlog + $subtotal2_percentage_total_net_backlog, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_percentage_total_mrc + $subtotal2_percentage_total_mrc, 2) }}%</td>
                                        <td class="amount">{{ number_format($subtotal1_cr_above_251 + $subtotal2_cr_above_251) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_proposed_for_barring + $subtotal2_proposed_for_barring) }}</td>
                                        <td class="amount">{{ number_format($subtotal1_already_barred + $subtotal2_already_barred) }}</td>
                                    </tr>
                                @endif

                            @endforeach

                        </tbody>

                    </table>

                </article>

            @endif

        </section>

        {{-- SLIDE 5 --}}
        <section class="dashboard-slide">

            @if(!empty($discontinuedAnalysis) && isset($discontinuedAnalysis['comparisons'][0]))

                <article class="panel" style="margin-bottom: 24px;">

                    <div class="panel-header">
                        <h2>Discontinued Clients - Month-on-Month Summary</h2>
                    </div>

                    <table class="segment-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="amount">Number of Clients</th>
                                <th class="amount">Opening OS</th>
                                <th class="amount">Target</th>
                                <th class="amount">Collection</th>
                                <th class="amount">Latest OS</th>
                                <th class="amount">Shortfall Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $discCurrent = $discontinuedAnalysis['comparisons'][0];
                                $discPrevious = $discontinuedAnalysis['comparisons'][1];
                            @endphp
                            <tr>
                                <td><strong>{{ $discCurrent['month'] }}</strong></td>
                                <td class="amount">
                                    {{ number_format($discCurrent['clients_count']) }}
                                    {!! $renderMoMArrow($discCurrent['clients_count'], $discPrevious['clients_count'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($discCurrent['opening_os_sum']) }}
                                    {!! $renderMoMArrow($discCurrent['opening_os_sum'], $discPrevious['opening_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($discCurrent['target_sum']) }}
                                    {!! $renderMoMArrow($discCurrent['target_sum'], $discPrevious['target_sum'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($discCurrent['collection_sum']) }}
                                    {!! $renderMoMArrow($discCurrent['collection_sum'], $discPrevious['collection_sum'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($discCurrent['latest_os_sum']) }}
                                    {!! $renderMoMArrow($discCurrent['latest_os_sum'], $discPrevious['latest_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($discCurrent['shortfall_sum']) }}
                                    {!! $renderMoMArrow($discCurrent['shortfall_sum'], $discPrevious['shortfall_sum'], 'good-down') !!}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ $discPrevious['month'] }}</strong></td>
                                <td class="amount">{{ number_format($discPrevious['clients_count']) }}</td>
                                <td class="amount">{{ $formatMil($discPrevious['opening_os_sum']) }}</td>
                                <td class="amount">{{ $formatMil($discPrevious['target_sum']) }}</td>
                                <td class="amount">{{ $formatMil($discPrevious['collection_sum']) }}</td>
                                <td class="amount">{{ $formatMil($discPrevious['latest_os_sum']) }}</td>
                                <td class="amount">{{ $formatMil($discPrevious['shortfall_sum']) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </article>

                <article class="panel">

                    <div class="panel-header">
                        <h2>Discontinued Clients - Service Category Breakdown</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>
                    <table class="segment-table">
                        <thead>
                            <tr>
                                <th>Service Category</th>
                                <th class="amount">Clients</th>
                                <th class="amount">Opening OS</th>
                                <th class="amount">Collection</th>
                                <th class="amount">Latest OS</th>
                                <th class="amount">Unbilled OS</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $grand_client_count = 0;
                                $grand_opening_os = 0;
                                $grand_collection = 0;
                                $grand_latest_os = 0;
                                $grand_unbilled_os = 0;
                            @endphp

                            @foreach ($discontinuedAnalysis['rows'] as $row)
                                @php
                                    $grand_client_count += $row['client_count'];
                                    $grand_opening_os += $row['opening_os'];
                                    $grand_collection += $row['collection'];
                                    $grand_latest_os += $row['latest_os'];
                                    $grand_unbilled_os += $row['unbilled_os'];
                                @endphp

                                <tr>
                                    <td>
                                        <span class="pill">
                                            {{ $row['category'] }}
                                        </span>
                                    </td>
                                    <td class="amount" style="font-weight: 500;">
                                        <a href="{{ route('dashboard.discontinued-clients.index', [
                                            'category' => $row['category'],
                                            'month' => $discontinuedAnalysis['latestMonth'] ? ($discontinuedAnalysis['latestMonth'] instanceof \Carbon\Carbon ? $discontinuedAnalysis['latestMonth']->format('Y-m') : \Carbon\Carbon::parse($discontinuedAnalysis['latestMonth'])->format('Y-m')) : ''
                                        ]) }}" target="_blank">
                                            {{ number_format($row['client_count']) }}
                                        </a>
                                    </td>
                                    <td class="amount" style="font-weight: 500;">
                                        {{ $formatMil($row['opening_os']) }}
                                    </td>
                                    <td class="amount" style="font-weight: 500;">
                                        {{ $formatMil($row['collection']) }}
                                    </td>
                                    <td class="amount" style="font-weight: 500;">
                                        {{ $formatMil($row['latest_os']) }}
                                    </td>
                                    <td class="amount" style="font-weight: 500;">
                                        {{ $formatMil($row['unbilled_os']) }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="grand-subtotal-row" style="background-color: #cbd5e1 !important; font-weight: 800;">
                                <td>Total / Grand Subtotal</td>
                                <td class="amount" style="font-weight: 800;">{{ number_format($grand_client_count) }}</td>
                                <td class="amount" style="font-weight: 800;">{{ $formatMil($grand_opening_os) }}</td>
                                <td class="amount" style="font-weight: 800;">{{ $formatMil($grand_collection) }}</td>
                                <td class="amount" style="font-weight: 800;">{{ $formatMil($grand_latest_os) }}</td>
                                <td class="amount" style="font-weight: 800;">{{ $formatMil($grand_unbilled_os) }}</td>
                            </tr>

                        </tbody>

                    </table>

                </article>

            @endif

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
                                                <td class="amount">{{ $formatMil($row['collection']) }}</td>
                                                <td class="amount">{{ $formatMil($row['maturity']) }}</td>
                                                <td class="amount">{{ number_format($row['efficiency'], 2) }}%</td>
                                                <td class="amount">{{ $formatMil($row['latest_os']) }}</td>
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

        {{-- SLIDE 7 --}}
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
                                            {{ $formatMil($collection->collection_amount) }}
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

            <div class="no-print" style="display: flex; justify-content: center; margin-top: 30px; margin-bottom: 20px;">
                <button id="exportPdfBtn" class="button primary" style="min-height: 48px; padding: 0 32px; font-size: 16px; font-weight: 800; border-radius: 999px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(15, 118, 110, 0.25);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export Dashboard to PDF
                </button>
            </div>

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
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + 'M';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(1) + 'M';
                                }
                                return label;
                            }
                        }
                    }
                }
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
    document.getElementById('exportPdfBtn')?.addEventListener('click', function() {
        window.print();
    });

    document.querySelectorAll('.segment-table').forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
</script>
@endpush
