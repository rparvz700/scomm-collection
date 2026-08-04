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

        .risk-segment-table {
            min-width: 100%;
        }

        .risk-segment-table td {
            font-weight: 500 !important;
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
            grid-template-columns: 1fr;
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
            font-size: clamp(20px, 4vw, 30px);
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
            padding: 8px 14px;
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
        
        /* Avatar Image Preview Modal styles */
        .emp-avatar-lazy {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .emp-avatar-lazy:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .avatar-modal-close {
            transition: transform 0.2s, color 0.2s;
        }
        .avatar-modal-close:hover {
            color: #ef4444 !important;
            transform: scale(1.15) rotate(90deg);
        }
    </style>
@endpush

@php
    $today = now();
    $day = $today->day;
    $monthName = $today->format('F');
    $prevMonthName = $today->copy()->subMonth()->format('F');
    $currentYear = $today->year;
    $quarter = ceil($today->month / 3);
    $quarterShortMonths = [];
    $startMonth = ($quarter - 1) * 3 + 1;
    for ($m = $startMonth; $m < $startMonth + 3; $m++) {
        $quarterShortMonths[] = strtoupper(Carbon\Carbon::create($currentYear, $m, 1)->format('M'));
    }

    if ($day <= 5) {
        $contextMessage = "Currently in <strong>{$monthName} opening</strong> phase. <strong>{$prevMonthName} closing</strong> operations are ongoing, and new collections for {$monthName} have not officially started yet.";
    } else {
        $contextMessage = "Currently in <strong>{$monthName} collection</strong> phase. <strong>{$prevMonthName} closing</strong> is fully finalized, and {$monthName} billing/collection is in full swing.";
    }
@endphp

{{-- DYNAMIC CONTEXT BANNER WITH HORIZONTAL TIMELINE --}}
<div class="no-print" style="margin-bottom: 18px; background: var(--panel); border: 1px solid var(--line); border-radius: 10px; padding: 10px 20px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; gap: 16px; color: var(--ink); flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 14px; flex-shrink: 0;">
        <div style="background: rgba(2, 104, 224, 0.08); border: 1px solid rgba(2, 104, 224, 0.2); border-radius: 6px; padding: 6px 10px; display: flex; align-items: center; gap: 8px; flex-shrink: 0; color: var(--primary);">
            <span style="font-size: 22px; font-weight: 900; line-height: 1;">Q{{ $quarter }}</span>
            <div style="display: flex; flex-direction: column; font-size: 8.5px; font-weight: 800; line-height: 1.15; color: var(--muted); letter-spacing: 0.5px;">
                @foreach($quarterShortMonths as $sm)
                    <span>{{ $sm }}</span>
                @endforeach
            </div>
        </div>
        <div style="font-size: 13px; line-height: 1.35; color: var(--ink); max-width: 320px;">
            {!! $contextMessage !!}
        </div>
    </div>

    {{-- HORIZONTAL TIMELINE --}}
    <div style="display: flex; align-items: center; gap: 6px; flex: 1; min-width: 280px; max-width: 560px; background: rgba(0,0,0,0.02); padding: 4px 8px; border-radius: 24px; border: 1px solid var(--line);">
        <button id="timelinePrevBtn" type="button" style="background: var(--panel); border: 1px solid var(--line); border-radius: 50%; width: 26px; height: 26px; font-size: 14px; font-weight: 800; color: var(--ink); cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" title="Previous Months">&lsaquo;</button>
        
        <div id="timelineTrack" style="display: flex; align-items: center; gap: 5px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; flex: 1; padding: 2px 0; scroll-behavior: smooth;">
            <!-- Rendered via JS -->
        </div>

        <button id="timelineNextBtn" type="button" style="background: var(--panel); border: 1px solid var(--line); border-radius: 50%; width: 26px; height: 26px; font-size: 14px; font-weight: 800; color: var(--ink); cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" title="Next Months">&rsaquo;</button>
    </div>

    <div style="display: flex; gap: 8px; align-items: center; flex-shrink: 0;">
        <button class="button primary" style="width: 36px; height: 36px; padding: 0; background: var(--line); border: 1px solid var(--line); color: var(--ink); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onclick="toggleGlossaryModal()" title="Data Dictionary &amp; Metric Glossary" aria-label="Data Dictionary &amp; Metric Glossary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
        </button>

        <button id="exportPdfBtn" class="button primary" style="width: 36px; height: 36px; padding: 0; background: var(--line); border: 1px solid var(--line); color: var(--ink); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" title="Export Presentation PDF" aria-label="Export Presentation PDF">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>
</div>

{{-- DATA GLOSSARY MODAL OVERLAY --}}
<div id="glossaryModal" class="modal-overlay no-print" style="opacity: 0; pointer-events: none; transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; display: flex; justify-content: center; align-items: center;">
    <div id="glossaryContent" style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 680px; box-shadow: var(--shadow); max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02);">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--ink);">
                <span>📖</span> Data Dictionary &amp; Metric Glossary
            </h2>
            <button onclick="toggleGlossaryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
        </div>
        <div style="padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px; text-align: left;">
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Total Opening OS (MRC+Backlog)</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The sum of the opening outstanding balance (both standard Monthly Recurring Charge and long-standing backlog) at the start of the month.
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Total Collection</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    Total actual money collected from the client during the current month across postpaid and prepaid NTTN/IIG/ITC/NIX circuits.
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">MRC (Monthly Recurring Charge)</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The contracted recurring revenue billed for the current month. Collection is allocated to this first under the LIFO model.
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">MRC Collection (LIFO)</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The portion of the current month's collection allocated to satisfy the MRC first.
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Backlog / Opening Backlog</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    Long-standing outstanding balance from prior months. Collection is allocated here only after MRC is fully satisfied (LIFO).
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Backlog Collection (LIFO)</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The portion of the current month's collection allocated to clear historical backlog (after MRC is fully covered).
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Total Latest/Closing OS</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The closing outstanding balance at the end of the month, calculated as: Opening OS + Billed MRC - Total Collection.
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">OS against only MRC</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    The uncollected portion of the current month's billed MRC, calculated as: Total Billed MRC - Total Collection MRC (LIFO).
                </p>
            </div>
            <div>
                <strong style="color: var(--primary); font-size: 14px;">Shortfall Tagging</strong>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                    Clients are dynamically analyzed for shortfall. If current month's collection is less than MRC, they have an MRC Shortfall. If it is less than MRC + Backlog, they have a Backlog Shortfall.
                </p>
            </div>
        </div>
        <div style="padding: 16px 24px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.02); display: flex; justify-content: flex-end;">
            <button class="button primary" onclick="toggleGlossaryModal()">Close Glossary</button>
        </div>
    </div>
</div>

{{-- MANAGEMENT GUIDANCE LOGS MODAL OVERLAY --}}
<div id="guidanceLogsModal" class="modal-overlay no-print" style="opacity: 0; pointer-events: none; transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; display: flex; justify-content: center; align-items: center;">
    <div id="guidanceLogsContent" style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 850px; box-shadow: var(--shadow); max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <div style="padding: 16px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02);">
            <h2 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--ink);">
                <span>📝</span> Management Guidance Audit Logs
            </h2>
            <button onclick="toggleGuidanceLogsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
        </div>

        <div style="padding: 14px 24px; border-bottom: 1px solid var(--line); background: var(--panel); display: flex; justify-content: space-between; align-items: center; gap: 16px;">
            <input type="text" id="guidanceLogSearchInput" placeholder="Search by client name, user, or guidance..." style="flex: 1; max-width: 400px; padding: 8px 14px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
            <span id="guidanceLogTotalCount" style="font-size: 12px; color: var(--muted); font-weight: 700;">Loading...</span>
        </div>

        <div style="padding: 20px 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 12px;" id="guidanceLogListContainer">
            <div style="text-align:center; padding: 24px; color: var(--muted);">Loading logs...</div>
        </div>

        <div style="padding: 12px 24px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.02); display: flex; justify-content: space-between; align-items: center;">
            <button id="guidanceLogPrevBtn" class="button secondary btn-small" onclick="fetchGuidanceLogsPage(-1)" disabled>&larr; Previous</button>
            <span id="guidanceLogPageInfo" style="font-size: 12px; font-weight: 700; color: var(--muted);">Page 1 of 1</span>
            <button id="guidanceLogNextBtn" class="button secondary btn-small" onclick="fetchGuidanceLogsPage(1)" disabled>Next &rarr;</button>
        </div>
    </div>
</div>

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
                            'value' => '<a href="' . route('clients.index', ['status' => 'Active']) . '" target="_blank" style="text-decoration:none; color:inherit; border-bottom:1px dashed #166534;">' . number_format($activeClientCount) . '</a>',
                            'is_html' => true,
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
                                                <a href="' . route('clients.index', ['status' => 'Barred']) . '" target="_blank" style="text-decoration:none; color:inherit; border-bottom:1px dashed #991b1b; display:block;">
                                                    <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                        ' . number_format($barredSubCount) . '
                                                    </span>
                                                    <span style="font-size:11px; font-weight:500; color:#b91c1c; text-transform:uppercase;">
                                                        Barred
                                                    </span>
                                                </a>
                                            </div>
                                            <div>
                                                <a href="' . route('clients.index', ['status' => 'Discontinued']) . '" target="_blank" style="text-decoration:none; color:inherit; border-bottom:1px dashed #991b1b; display:block;">
                                                    <span style="font-size: clamp(20px, 2.5vw, 26px); font-weight:800;">
                                                        ' . number_format($discontinuedSubCount) . '
                                                    </span>
                                                    <span style="font-size:11px; font-weight:500; color:#b91c1c; text-transform:uppercase;">
                                                        Discont.
                                                    </span>
                                                </a>
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
                            'title' => 'OS against only MRC',
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
                            'value' => '<a href="' . route('clients.index', ['risk' => 'High Risk']) . '" target="_blank" style="text-decoration:none; color:inherit; border-bottom:1px dashed #166534;">' . number_format($highRiskCount) . '</a>',
                            'is_html' => true,
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

        <section class="dashboard-slide">
            @php
                $renderProgressBar = function($percent, $color = null, $isPositive = true) {
                    $val = max(0, min(100, (float) $percent));
                    if ($color === null || $color === '' || $color === '#ffffff') {
                        $hue = $isPositive ? (100 - $val) * 1.2 : $val * 1.2;
                        $color = "hsl(" . round($hue) . ", 85%, 45%)";
                    }
                    return '
                    <div style="display: flex; flex-direction: column; align-items: flex-end; margin-top: 4px; width: 100%;">
                        <div style="width: 100%; height: 8px; background-color: rgba(226, 232, 240, 0.9); border: 2px solid #ffffff; border-radius: 999px; overflow: hidden; position: relative; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);">
                            <div style="width: ' . $val . '%; height: 100%; background-color: ' . $color . '; border-radius: 999px; transition: width 0.4s ease;"></div>
                        </div>
                    </div>';
                };
            @endphp
            @if(isset($combinedMoMSummary[0]))
                <article class="panel" style="margin-bottom: 24px;">
                    <div class="panel-header">
                        <h2>IIG, ISP & Other Operators - Month-on-Month Summary</h2>
                        <span>{{ $currentMonthLabel }} · Month on month</span>
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
                                $currentCombined = $combinedMoMSummary[0];
                                $previousCombined = $combinedMoMSummary[1];
                            @endphp
                            <tr style="font-weight: 800; background-color: #f1f5f9 !important;">
                                <td><strong>{{ $currentCombined['month'] }}</strong></td>
                                <td class="amount">
                                    {{ number_format($currentCombined['clients_count']) }}
                                    {!! $renderMoMArrow($currentCombined['clients_count'], $previousCombined['clients_count'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($currentCombined['opening_os_sum']) }}
                                    {!! $renderMoMArrow($currentCombined['opening_os_sum'], $previousCombined['opening_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($currentCombined['mrc_sum']) }}
                                    {!! $renderMoMArrow($currentCombined['mrc_sum'], $previousCombined['mrc_sum'], 'good-up') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($currentCombined['backlog_sum']) }}
                                    {!! $renderMoMArrow($currentCombined['backlog_sum'], $previousCombined['backlog_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($currentCombined['opening_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($currentCombined['opening_cr_avg'], $previousCombined['opening_cr_avg'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ $formatMil($currentCombined['latest_os_sum']) }}
                                    {!! $renderMoMArrow($currentCombined['latest_os_sum'], $previousCombined['latest_os_sum'], 'good-down') !!}
                                </td>
                                <td class="amount">
                                    {{ number_format($currentCombined['latest_cr_avg'], 2) }}
                                    {!! $renderMoMArrow($currentCombined['latest_cr_avg'], $previousCombined['latest_cr_avg'], 'good-down') !!}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ $previousCombined['month'] }}</strong></td>
                                <td class="amount">{{ number_format($previousCombined['clients_count']) }}</td>
                                <td class="amount">{{ $formatMil($previousCombined['opening_os_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previousCombined['mrc_sum']) }}</td>
                                <td class="amount">{{ $formatMil($previousCombined['backlog_sum']) }}</td>
                                <td class="amount">{{ number_format($previousCombined['opening_cr_avg'], 2) }}</td>
                                <td class="amount">{{ $formatMil($previousCombined['latest_os_sum']) }}</td>
                                <td class="amount">{{ number_format($previousCombined['latest_cr_avg'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </article>
            @endif

            @if(isset($combinedRiskBreakdown['rows']))
                @php
                    $currentMonthObj = \Carbon\Carbon::parse($latestSummary?->summary_month);
                    $nextMonthObj = $currentMonthObj->copy()->addMonth();

                    $currentMonthLabel = $currentMonthObj->format("M'y");
                    $nextMonthLabel = $nextMonthObj->format("F'y");
                    $nextMonthShortLabel = $nextMonthObj->format("M\"y");
                @endphp

                <article class="panel">
                    <div class="panel-header">
                        <h2>ISP, IIG & Other Operator's {{ $currentMonthLabel }} OS Summary</h2>
                        <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                    </div>

                    <table class="segment-table risk-segment-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="amount">No of Clients</th>
                                <th class="amount">CR Segment</th>
                                <th class="amount">Opening OS</th>
                                <th class="amount">Latest MRC</th>
                                <th class="amount">Percentage of total MRC</th>
                                <th class="amount">Net Backlog</th>
                                <th class="amount">Percentage of total Backlog</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($combinedRiskBreakdown['rows'] as $row)
                                @php
                                    $bgStyle = '';
                                    $textStyle = '';
                                    $barColor = '#3b82f6';
                                    if ($row['category'] === 'Best') {
                                        $bgStyle = 'background-color: #e0fef1 !important;';
                                        $barColor = '#10b981';
                                    } elseif ($row['category'] === 'Good') {
                                        $bgStyle = 'background-color: #acdbfa !important;';
                                        $barColor = '#f59e0b';
                                    } elseif ($row['category'] === 'Moderate') {
                                        $bgStyle = 'background-color: #fce58c !important;';
                                        $barColor = '#f59e0b';
                                    } elseif ($row['category'] === 'Risky') {
                                        $bgStyle = 'background-color: #fad2c0 !important;';
                                        $barColor = '#f97316';
                                    } elseif ($row['category'] === 'High Risky') {
                                        $bgStyle = 'background-color: #fe9a93 !important;';
                                        $barColor = '#f43f5e';
                                    } elseif ($row['category'] === 'Most Risky') {
                                        $bgStyle = 'background-color: #ea580c !important;';
                                        $textStyle = 'color: #ffffff !important;';
                                        $barColor = '#ffffff';
                                    }
                                @endphp
                                <tr style="{{ $bgStyle }} {{ $textStyle }}">
                                    <td style="{{ $textStyle }}"><strong>{{ $row['category'] }}</strong></td>
                                    <td class="amount" style="{{ $textStyle }}">{{ number_format($row['client_count']) }}</td>
                                    <td class="amount" style="{{ $textStyle }}">{{ $row['cr_segment'] }}</td>
                                    <td class="amount" style="{{ $textStyle }}">{{ $formatMil($row['latest_os_sum']) }}</td>
                                    <td class="amount" style="{{ $textStyle }}">{{ $formatMil($row['mrc_sum']) }}</td>
                                    <td class="amount" style="{{ $textStyle }}">
                                        {{ number_format($row['mrc_percentage'], 0) }}%
                                        {!! $renderProgressBar($row['mrc_percentage'], null, true) !!}
                                    </td>
                                    <td class="amount" style="{{ $textStyle }}">{{ $formatMil($row['backlog_sum']) }}</td>
                                    <td class="amount" style="{{ $textStyle }}">
                                        {{ number_format($row['backlog_percentage'], 0) }}%
                                        {!! $renderProgressBar($row['backlog_percentage'], null, true) !!}
                                    </td>
                                </tr>
                            @endforeach
                            @php
                                $totals = $combinedRiskBreakdown['totals'];
                            @endphp
                            <tr style="font-weight: 800; font-size: 14px; background-color: #0f172a !important; color: #ffffff !important;">
                                <td style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;"><strong>Sub Total:</strong></td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">{{ number_format($totals['client_count']) }}</td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">0.00 &le; 3.50</td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">{{ $formatMil($totals['latest_os_sum']) }}</td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">{{ $formatMil($totals['mrc_sum']) }}</td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">
                                    100%
                                    {!! $renderProgressBar(100, null, true) !!}
                                </td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">{{ $formatMil($totals['backlog_sum']) }}</td>
                                <td class="amount" style="border-top: 2px double #cbd5e1; border-bottom: 2px double #cbd5e1; color: #ffffff !important;">
                                    100%
                                    {!! $renderProgressBar(100, null, true) !!}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </article>
            @endif

            @if(isset($combinedRiskBreakdown['rows']))
                @php
                    $rows = $combinedRiskBreakdown['rows'];
                    $totals = $combinedRiskBreakdown['totals'];

                    $getSegmentSubtotal = function($segmentData, $startRow, $endRow) {
                        $clients = 0;
                        $openingOs = 0.0;
                        $mrc = 0.0;
                        $backlog = 0.0;

                        if (isset($segmentData['rows'])) {
                            for ($i = $startRow; $i <= $endRow; $i++) {
                                if (isset($segmentData['rows'][$i])) {
                                    $row = $segmentData['rows'][$i];
                                    $clients += $row['client_count'];
                                    $openingOs += $row['closing_os_sum'];
                                    $mrc += $row['latest_mrc_sum'];
                                    $backlog += $row['net_backlog_sum'];
                                }
                            }
                        }

                        return [
                            'clients' => $clients,
                            'opening_os' => $openingOs,
                            'mrc' => $mrc,
                            'backlog' => $backlog,
                        ];
                    };

                    $totalMrc = $totals['mrc_sum'] ?? 0;
                    $totalBacklog = $totals['backlog_sum'] ?? 0;

                    // Best, Good, Moderate (rows 0, 1, 2)
                    $bgmIsp = $getSegmentSubtotal($segmentAnalysis[1] ?? [], 0, 2);
                    $bgmIig = $getSegmentSubtotal($segmentAnalysis[0] ?? [], 0, 2);

                    $bgmTotalClients = $bgmIsp['clients'] + $bgmIig['clients'];
                    $bgmTotalOpeningOs = $bgmIsp['opening_os'] + $bgmIig['opening_os'];
                    $bgmTotalMrc = $bgmIsp['mrc'] + $bgmIig['mrc'];
                    $bgmTotalBacklog = $bgmIsp['backlog'] + $bgmIig['backlog'];

                    $bgmIspMrcPercent = $totalMrc > 0 ? ($bgmIsp['mrc'] / $totalMrc) * 100 : 0;
                    $bgmIspBacklogPercent = $totalBacklog > 0 ? ($bgmIsp['backlog'] / $totalBacklog) * 100 : 0;

                    $bgmIigMrcPercent = $totalMrc > 0 ? ($bgmIig['mrc'] / $totalMrc) * 100 : 0;
                    $bgmIigBacklogPercent = $totalBacklog > 0 ? ($bgmIig['backlog'] / $totalBacklog) * 100 : 0;

                    $bgmTotalMrcPercent = $totalMrc > 0 ? ($bgmTotalMrc / $totalMrc) * 100 : 0;
                    $bgmTotalBacklogPercent = $totalBacklog > 0 ? ($bgmTotalBacklog / $totalBacklog) * 100 : 0;

                    // Risky, High Risky, Most Risky (rows 3, 4, 5)
                    $rhmIsp = $getSegmentSubtotal($segmentAnalysis[1] ?? [], 3, 5);
                    $rhmIig = $getSegmentSubtotal($segmentAnalysis[0] ?? [], 3, 5);

                    $rhmTotalClients = $rhmIsp['clients'] + $rhmIig['clients'];
                    $rhmTotalOpeningOs = $rhmIsp['opening_os'] + $rhmIig['opening_os'];
                    $rhmTotalMrc = $rhmIsp['mrc'] + $rhmIig['mrc'];
                    $rhmTotalBacklog = $rhmIsp['backlog'] + $rhmIig['backlog'];

                    $rhmIspMrcPercent = $totalMrc > 0 ? ($rhmIsp['mrc'] / $totalMrc) * 100 : 0;
                    $rhmIspBacklogPercent = $totalBacklog > 0 ? ($rhmIsp['backlog'] / $totalBacklog) * 100 : 0;

                    $rhmIigMrcPercent = $totalMrc > 0 ? ($rhmIig['mrc'] / $totalMrc) * 100 : 0;
                    $rhmIigBacklogPercent = $totalBacklog > 0 ? ($rhmIig['backlog'] / $totalBacklog) * 100 : 0;

                    $rhmTotalMrcPercent = $totalMrc > 0 ? ($rhmTotalMrc / $totalMrc) * 100 : 0;
                    $rhmTotalBacklogPercent = $totalBacklog > 0 ? ($rhmTotalBacklog / $totalBacklog) * 100 : 0;
                @endphp

                <div style="margin-top: 24px;">
                    <!-- Best, Good, Moderate Card -->
                    <article class="panel" style="margin-bottom: 24px;">
                        <div class="panel-header">
                            <h2>Best, Good & Moderate Client's MRC & Backlog Breakdown</h2>
                            <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                        </div>
                        <table class="segment-table risk-segment-table">
                            <thead>
                                <tr>
                                    <th>Segment</th>
                                    <th class="amount">No of Clients</th>
                                    <th class="amount">CR Segment</th>
                                    <th class="amount">Opening OS</th>
                                    <th class="amount">Latest MRC</th>
                                    <th class="amount">% of Total MRC</th>
                                    <th class="amount">Net Backlog</th>
                                    <th class="amount">% of Total Backlog</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ISP & Other Operators</td>
                                    <td class="amount">{{ number_format($bgmIsp['clients']) }}</td>
                                    <td class="amount">0.00 &le; 2.50</td>
                                    <td class="amount">{{ $formatMil($bgmIsp['opening_os']) }}</td>
                                    <td class="amount">{{ $formatMil($bgmIsp['mrc']) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmIspMrcPercent, 0) }}%
                                        {!! $renderProgressBar($bgmIspMrcPercent, null, false) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($bgmIsp['backlog']) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmIspBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($bgmIspBacklogPercent, null, false) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td>IIG Operators</td>
                                    <td class="amount">{{ number_format($bgmIig['clients']) }}</td>
                                    <td class="amount">0.00 &le; 2.50</td>
                                    <td class="amount">{{ $formatMil($bgmIig['opening_os']) }}</td>
                                    <td class="amount">{{ $formatMil($bgmIig['mrc']) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmIigMrcPercent, 0) }}%
                                        {!! $renderProgressBar($bgmIigMrcPercent, null, false) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($bgmIig['backlog']) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmIigBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($bgmIigBacklogPercent, null, false) !!}
                                    </td>
                                </tr>
                                <tr style="font-weight: 800; background-color: #e6f4ea !important;">
                                    <td><strong>Sub Total:</strong></td>
                                    <td class="amount">{{ number_format($bgmTotalClients) }}</td>
                                    <td class="amount">0.00 &le; 2.50</td>
                                    <td class="amount">{{ $formatMil($bgmTotalOpeningOs) }}</td>
                                    <td class="amount">{{ $formatMil($bgmTotalMrc) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmTotalMrcPercent, 0) }}%
                                        {!! $renderProgressBar($bgmTotalMrcPercent, null, false) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($bgmTotalBacklog) }}</td>
                                    <td class="amount">
                                        {{ number_format($bgmTotalBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($bgmTotalBacklogPercent, null, false) !!}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </article>

                    <!-- Risky, High Risky, Most Risky Card -->
                    <article class="panel" style="margin-bottom: 24px;">
                        <div class="panel-header">
                            <h2>Risky, High Risky & Most Risky Client's MRC & Backlog Breakdown</h2>
                            <span>{{ $currentMonthLabel }} · Latest monthly summary</span>
                        </div>
                        <table class="segment-table risk-segment-table">
                            <thead>
                                <tr>
                                    <th>Segment</th>
                                    <th class="amount">No of Clients</th>
                                    <th class="amount">CR Segment</th>
                                    <th class="amount">Opening OS</th>
                                    <th class="amount">Latest MRC</th>
                                    <th class="amount">% of Total MRC</th>
                                    <th class="amount">Net Backlog</th>
                                    <th class="amount">% of Total Backlog</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ISP & Other Operators</td>
                                    <td class="amount">{{ number_format($rhmIsp['clients']) }}</td>
                                    <td class="amount">&ge; 2.51</td>
                                    <td class="amount">{{ $formatMil($rhmIsp['opening_os']) }}</td>
                                    <td class="amount">{{ $formatMil($rhmIsp['mrc']) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmIspMrcPercent, 0) }}%
                                        {!! $renderProgressBar($rhmIspMrcPercent, null, true) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($rhmIsp['backlog']) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmIspBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($rhmIspBacklogPercent, null, true) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td>IIG Operators</td>
                                    <td class="amount">{{ number_format($rhmIig['clients']) }}</td>
                                    <td class="amount">&ge; 2.51</td>
                                    <td class="amount">{{ $formatMil($rhmIig['opening_os']) }}</td>
                                    <td class="amount">{{ $formatMil($rhmIig['mrc']) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmIigMrcPercent, 0) }}%
                                        {!! $renderProgressBar($rhmIigMrcPercent, null, true) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($rhmIig['backlog']) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmIigBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($rhmIigBacklogPercent, null, true) !!}
                                    </td>
                                </tr>
                                <tr style="font-weight: 800; background-color: #fdf2f2 !important;">
                                    <td><strong>Sub Total:</strong></td>
                                    <td class="amount">{{ number_format($rhmTotalClients) }}</td>
                                    <td class="amount">&ge; 2.51</td>
                                    <td class="amount">{{ $formatMil($rhmTotalOpeningOs) }}</td>
                                    <td class="amount">{{ $formatMil($rhmTotalMrc) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmTotalMrcPercent, 0) }}%
                                        {!! $renderProgressBar($rhmTotalMrcPercent, null, true) !!}
                                    </td>
                                    <td class="amount">{{ $formatMil($rhmTotalBacklog) }}</td>
                                    <td class="amount">
                                        {{ number_format($rhmTotalBacklogPercent, 0) }}%
                                        {!! $renderProgressBar($rhmTotalBacklogPercent, null, true) !!}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </article>
                </div>
            @endif
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
                            <tr style="font-weight: 800; background-color: #f1f5f9 !important;">
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
                            <tr style="font-weight: 800; background-color: #f1f5f9 !important;">
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
                            <tr style="font-weight: 800; background-color: #f1f5f9 !important;">
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
                                {{ number_format($kamPerformance['best']['efficiency'], 2) }}% (Late: {{ $kamPerformance['best']['late_entry'] }}/{{ $kamPerformance['best']['total_entry'] }})
                            </span>
                        </div>
                    @endif

                    @if ($kamPerformance['worst'])
                        <div class="insight-statement">
                            <strong>Worst performing KAM:</strong>
                            {{ $kamPerformance['worst']['kam'] }}

                            <span class="insight-change down">
                                {{ number_format($kamPerformance['worst']['efficiency'], 2) }}% (Late: {{ $kamPerformance['worst']['late_entry'] }}/{{ $kamPerformance['worst']['total_entry'] }})
                            </span>
                        </div>
                    @endif

                </div>

                @php
                    $performanceTables = [
                        'Collection KAMs' => $collectionKams,
                        'Collection Supervisors' => $supervisors,
                        'Sales KAMs' => $smKams,
                        'Sales Team' => $teams,
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
                                @php
                                    $totalClients = array_sum(array_column($rows, 'clients'));
                                    $totalPortfolio = array_sum(array_column($rows, 'maturity'));
                                @endphp

                                <table class="team-performance-table">
                                     <thead>
                                         <tr>
                                             <th>Name</th>
                                             @if ($title === 'Collection KAMs')
                                                 <th>Supervisor</th>
                                             @endif
                                             @if ($title === 'Sales KAMs')
                                                 <th>Team</th>
                                             @endif
                                             <th>Clients (% of Total)</th>
                                             <th>Portfolio (% of Total)</th>
                                             <th>Collection</th>
                                             <th>Achievement (%)</th>
                                             <th>Latest OS</th>
                                             <th>High risk clients (≥ 2.51)</th>
                                             <th>Late entry</th>
                                         </tr>
                                     </thead>

                                     <tbody>

                                         @foreach ($rows as $row)

                                             <tr>
                                                  <td style="vertical-align: middle;">
                                                      <div style="display: flex; align-items: center; gap: 10px;">
                                                          <img class="emp-avatar-lazy" data-email="{{ $row['email'] ?? '' }}" src="https://ui-avatars.com/api/?name={{ urlencode($row['name']) }}&background=e6f4f2&color=0f766e&bold=true&size=40" alt="{{ $row['name'] }}" style="width: 40px; height: 40px; border-radius: 6px; border: 1.5px solid #dbe3ef; flex-shrink: 0;">
                                                          <span style="font-weight: 700; color: #1e293b;">{{ $row['name'] }}</span>
                                                      </div>
                                                  </td>
                                                  @if ($title === 'Collection KAMs')
                                                      <td>{{ $kamSupervisorMap[strtolower(trim($row['name']))] ?? 'Unassigned' }}</td>
                                                  @endif
                                                  @if ($title === 'Sales KAMs')
                                                      <td>{{ $smKamTeamMap[strtolower(trim($row['name']))] ?? 'Unassigned' }}</td>
                                                  @endif
                                                  <td class="amount">
                                                      {{ number_format($row['clients']) }} 
                                                      <span style="font-size: 11px; color: var(--muted); font-weight: normal;">
                                                          ({{ number_format($totalClients > 0 ? ($row['clients'] / $totalClients) * 100 : 0, 1) }}%)
                                                      </span>
                                                  </td>
                                                  <td class="amount">
                                                      {{ $formatMil($row['maturity']) }}
                                                      <span style="font-size: 11px; color: var(--muted); font-weight: normal;">
                                                          ({{ number_format($totalPortfolio > 0 ? ($row['maturity'] / $totalPortfolio) * 100 : 0, 1) }}%)
                                                      </span>
                                                  </td>
                                                  <td class="amount">{{ $formatMil($row['collection']) }}</td>
                                                  <td class="amount" style="font-weight: 700; color: #0f766e;">{{ number_format($row['efficiency'], 2) }}%</td>
                                                  <td class="amount">{{ $formatMil($row['latest_os']) }}</td>
                                                  <td class="amount">{{ number_format($row['high_risk']) }}</td>
                                                  <td class="amount">{{ $row['late_entry'] }} / {{ $row['total_entry'] }}</td>
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



        {{-- SLIDE 8: Management Guidance & Escalation - Barred Clients --}}
        <section class="dashboard-slide">
            <div class="team-performance-heading" style="margin-bottom: 24px;">
                <h2>Management Guidance &amp; Escalations - Long-Standing Barred Clients</h2>
                <span>{{ $currentMonthLabel }}</span>
            </div>

            <section class="grid one" style="margin-bottom: 24px;">
                <article class="panel">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 12px;">
                        <div>
                            <h2>Long-Standing Barred Clients</h2>
                            <span style="font-size: 12px; color: var(--muted);">Sorted by Barring Aging (Descending) &bull; Aging &gt; 2 Months (Seek Guidance)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                            <input type="text" id="barredSearch" placeholder="Search accounts..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--line); border-radius: 6px; background: var(--panel); color: var(--ink); width: 220px; outline: none; margin: 0;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--muted); white-space: nowrap; margin-left: 5px;">Show:</span>
                            <select id="barredPageSize" style="padding: 4px 8px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; background: var(--panel); color: var(--ink); font-weight: 700; cursor: pointer; margin: 0; height: 32px;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; max-height: 480px;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Client Name</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Barred Date</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Aging</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Barred %</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Management Guidance</th>
                                </tr>
                            </thead>
                            <tbody id="barredTableBody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 14px; padding: 0 4px;">
                        <div id="barredTableInfo" style="font-size: 13px; color: var(--muted); font-weight: 500;">
                            Showing 0 to 0 of 0 entries
                        </div>
                        <div id="barredPagination" class="pagination" style="display: flex; gap: 4px; margin: 0; padding: 0; list-style: none;">
                            <!-- Pagination buttons -->
                        </div>
                    </div>
                </article>
            </section>

            <script>
                (function() {
                    const barredList = @json($barredClients);
                    let filtered = [...barredList];
                    let currentPage = 1;
                    let pageSize = 10;

                    const searchInput = document.getElementById('barredSearch');
                    const pageSizeSelect = document.getElementById('barredPageSize');
                    const tableBody = document.getElementById('barredTableBody');
                    const tableInfo = document.getElementById('barredTableInfo');
                    const pagination = document.getElementById('barredPagination');

                    function render() {
                        const query = searchInput.value.toLowerCase().trim();
                        
                        // 1. Filter
                        filtered = barredList.filter(item => {
                            return (item.client_name && item.client_name.toLowerCase().includes(query)) ||
                                   (item.barred_at_formatted && item.barred_at_formatted.toLowerCase().includes(query)) ||
                                   (item.aging_months && (item.aging_months + ' months').includes(query)) ||
                                   (item.barring_percentage !== undefined && (item.barring_percentage + '%').includes(query));
                        });

                        // 2. Paginate
                        const totalEntries = filtered.length;
                        const totalPages = Math.ceil(totalEntries / pageSize) || 1;
                        if (currentPage > totalPages) currentPage = totalPages;
                        if (currentPage < 1) currentPage = 1;

                        const startIdx = (currentPage - 1) * pageSize;
                        const endIdx = Math.min(startIdx + pageSize, totalEntries);

                        tableBody.innerHTML = '';
                        if (totalEntries === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">
                                        No long-standing barred clients match your search criteria.
                                    </td>
                                </tr>
                            `;
                            tableInfo.textContent = 'Showing 0 to 0 of 0 entries';
                            pagination.innerHTML = '';
                            return;
                        }

                        const displayed = filtered.slice(startIdx, endIdx);
                        displayed.forEach(item => {
                            const tr = document.createElement('tr');
                            
                            // Determine rating color style based on aging months
                            let badgeStyle = '';
                            if (item.aging_months >= 3.0) {
                                badgeStyle = 'background-color: #fee2e2; color: #ef4444; font-weight: 800;';
                            } else {
                                badgeStyle = 'background-color: #ffedd5; color: #ea580c; font-weight: 800;';
                            }

                            // Barred % calculation
                            const barPct = Math.max(0, Math.min(100, item.barring_percentage || 0));
                            const barHue = (100 - barPct) * 1.2;
                            const barColor = `hsl(${Math.round(barHue)}, 85%, 45%)`;

                            tr.innerHTML = `
                                <td><strong>${item.client_name || 'N/A'}</strong></td>
                                <td>${item.barred_at_formatted || 'N/A'}</td>
                                <td>
                                    <span class="pill" style="${badgeStyle}">
                                        ${item.aging_months} Months
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background-color:rgba(226, 232, 240, 0.9); height:10px; border:2px solid #ffffff; border-radius:999px; overflow:hidden; min-width:60px; box-shadow: 0 1px 3px rgba(0,0,0,0.15);">
                                            <div style="background-color:${barColor}; height:100%; width:${barPct}%; border-radius:999px; transition: width 0.4s ease;"></div>
                                        </div>
                                        <span>${barPct.toFixed(0)}%</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <input type="text" id="guidance-${item.client_id}" placeholder="Write guidance..." style="width: 100%; min-width: 160px; padding: 6px 10px; border-radius: 4px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size:12px;">
                                        <button class="button primary btn-small" onclick="submitInlineGuidance(${item.client_id})" style="padding: 6px 10px; min-height: unset; height: 28px; font-size: 11px;">Save</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });

                        // Info text
                        const showStart = totalEntries === 0 ? 0 : startIdx + 1;
                        const showEnd = endIdx;
                        tableInfo.textContent = `Showing ${showStart} to ${showEnd} of ${totalEntries} entries`;

                        // Pagination controls
                        renderPagination(totalPages);
                    }

                    function renderPagination(totalPages) {
                        pagination.innerHTML = '';
                        if (totalPages <= 1) return;

                        const btnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 600; border: 1px solid var(--line, #e2e8f0); border-radius: 6px; background: #ffffff; color: var(--ink, #1e293b); cursor: pointer; transition: all 0.2s; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
                        const activeBtnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 700; border: 1px solid #3b82f6; border-radius: 6px; background: #3b82f6; color: #ffffff; cursor: pointer; transition: all 0.2s; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(59,130,246,0.15);';
                        const disabledBtnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 600; border: 1px solid var(--line, #e2e8f0); border-radius: 6px; background: #f8fafc; color: #94a3b8; cursor: not-allowed; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; opacity: 0.6;';

                        function styleBtn(btn, isActive, isDisabled) {
                            if (isDisabled) {
                                btn.style.cssText = disabledBtnCss;
                                btn.disabled = true;
                            } else if (isActive) {
                                btn.style.cssText = activeBtnCss;
                            } else {
                                btn.style.cssText = btnCss;
                                btn.addEventListener('mouseenter', () => btn.style.backgroundColor = '#f1f5f9');
                                btn.addEventListener('mouseleave', () => btn.style.backgroundColor = '#ffffff');
                            }
                        }

                        // Prev button
                        const prevBtn = document.createElement('button');
                        prevBtn.textContent = '« Prev';
                        styleBtn(prevBtn, false, currentPage === 1);
                        prevBtn.addEventListener('click', () => {
                            currentPage--;
                            render();
                        });
                        pagination.appendChild(prevBtn);

                        // Calculate range of page numbers to show
                        let startPage = Math.max(1, currentPage - 2);
                        let endPage = Math.min(totalPages, currentPage + 2);

                        // Adjust if near boundary
                        if (currentPage <= 3) {
                            endPage = Math.min(5, totalPages);
                        }
                        if (currentPage >= totalPages - 2) {
                            startPage = Math.max(1, totalPages - 4);
                        }

                        // First page indicator
                        if (startPage > 1) {
                            const pBtn = document.createElement('button');
                            pBtn.textContent = '1';
                            styleBtn(pBtn, false, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = 1;
                                render();
                            });
                            pagination.appendChild(pBtn);

                            if (startPage > 2) {
                                const dots = document.createElement('span');
                                dots.textContent = '...';
                                dots.style.alignSelf = 'center';
                                dots.style.padding = '0 6px';
                                dots.style.color = 'var(--muted, #64748b)';
                                dots.style.fontSize = '13px';
                                pagination.appendChild(dots);
                            }
                        }

                        // Page numbers
                        for (let i = startPage; i <= endPage; i++) {
                            const pBtn = document.createElement('button');
                            pBtn.textContent = i;
                            styleBtn(pBtn, i === currentPage, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = i;
                                render();
                            });
                            pagination.appendChild(pBtn);
                        }

                        // Last page indicator
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                const dots = document.createElement('span');
                                dots.textContent = '...';
                                dots.style.alignSelf = 'center';
                                dots.style.padding = '0 6px';
                                dots.style.color = 'var(--muted, #64748b)';
                                dots.style.fontSize = '13px';
                                pagination.appendChild(dots);
                            }

                            const pBtn = document.createElement('button');
                            pBtn.textContent = totalPages;
                            styleBtn(pBtn, false, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = totalPages;
                                render();
                            });
                            pagination.appendChild(pBtn);
                        }

                        // Next button
                        const nextBtn = document.createElement('button');
                        nextBtn.textContent = 'Next »';
                        styleBtn(nextBtn, false, currentPage === totalPages);
                        nextBtn.addEventListener('click', () => {
                            currentPage++;
                            render();
                        });
                        pagination.appendChild(nextBtn);
                    }

                    searchInput.addEventListener('input', () => {
                        currentPage = 1;
                        render();
                    });

                    pageSizeSelect.addEventListener('change', (e) => {
                        pageSize = parseInt(e.target.value) || 10;
                        currentPage = 1;
                        render();
                    });

                    render();
                })();
            </script>
        </section>

        <section class="dashboard-slide">
            <div class="team-performance-heading" style="margin-bottom: 24px;">
                <h2>Management Guidance &amp; Escalations - Top Shortfall Accounts</h2>
                <span>{{ $currentMonthLabel }}</span>
            </div>

            <section class="grid one" style="margin-bottom: 24px;">
                <article class="panel">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 12px;">
                        <div>
                            <h2>Top Shortfall Accounts (Active Only)</h2>
                            <span style="font-size: 12px; color: var(--muted);">Sorted by Outstanding Balance (in Millions)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                            <input type="text" id="shortfallSearch" placeholder="Search accounts..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--line); border-radius: 6px; background: var(--panel); color: var(--ink); width: 220px; outline: none; margin: 0;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--muted); white-space: nowrap; margin-left: 5px;">Show:</span>
                            <select id="shortfallPageSize" style="padding: 4px 8px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; background: var(--panel); color: var(--ink); font-weight: 700; cursor: pointer; margin: 0; height: 32px;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; max-height: 480px;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Client Name</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">OS Balance</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">MRC</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">MRC Shortfall</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Backlog Shortfall</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Current Month CR</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Current Month Rating</th>
                                    <th style="position: sticky; top: 0; background: #f8fafc; z-index: 11; border-bottom: 2px solid var(--line);">Management Guidance</th>
                                </tr>
                            </thead>
                            <tbody id="shortfallTableBody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 14px; padding: 0 4px;">
                        <div id="shortfallTableInfo" style="font-size: 13px; color: var(--muted); font-weight: 500;">
                            Showing 0 to 0 of 0 entries
                        </div>
                        <div id="shortfallPagination" class="pagination" style="display: flex; gap: 4px; margin: 0; padding: 0; list-style: none;">
                            <!-- Pagination buttons -->
                        </div>
                    </div>
                </article>
            </section>

            <script>
                (function() {
                    const shortfalls = @json($combinedShortfalls);
                    let filtered = [...shortfalls];
                    let currentPage = 1;
                    let pageSize = 10;

                    const searchInput = document.getElementById('shortfallSearch');
                    const pageSizeSelect = document.getElementById('shortfallPageSize');
                    const tableBody = document.getElementById('shortfallTableBody');
                    const tableInfo = document.getElementById('shortfallTableInfo');
                    const pagination = document.getElementById('shortfallPagination');

                    function render() {
                        const query = searchInput.value.toLowerCase().trim();
                        
                        // 1. Filter
                        filtered = shortfalls.filter(item => {
                            return (item.client_name && item.client_name.toLowerCase().includes(query)) ||
                                   (item.rating && item.rating.toLowerCase().includes(query)) ||
                                   (item.os && (item.os / 1000000).toFixed(2).includes(query)) ||
                                   (item.mrc && (item.mrc / 1000000).toFixed(2).includes(query)) ||
                                   (item.mrc_shortfall && (item.mrc_shortfall / 1000000).toFixed(2).includes(query)) ||
                                   (item.backlog_shortfall && (item.backlog_shortfall / 1000000).toFixed(2).includes(query));
                        });

                        // 2. Paginate
                        const totalEntries = filtered.length;
                        const totalPages = Math.ceil(totalEntries / pageSize) || 1;
                        if (currentPage > totalPages) currentPage = totalPages;
                        if (currentPage < 1) currentPage = 1;

                        const startIdx = (currentPage - 1) * pageSize;
                        const endIdx = Math.min(startIdx + pageSize, totalEntries);

                        tableBody.innerHTML = '';
                        if (totalEntries === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--muted); padding: 30px;">
                                        No shortfall accounts match your search criteria.
                                    </td>
                                </tr>
                            `;
                            tableInfo.textContent = 'Showing 0 to 0 of 0 entries';
                            pagination.innerHTML = '';
                            return;
                        }

                        const displayed = filtered.slice(startIdx, endIdx);
                        displayed.forEach(item => {
                            const tr = document.createElement('tr');
                            
                            // Determine rating color style
                            let ratingHtml = '<span style="color:var(--muted);">N/A</span>';
                            if (item.rating) {
                                let style = '';
                                switch(item.rating) {
                                    case 'Risky': style = 'background-color: #ffedd5; color: #ea580c;'; break;
                                    case 'High Risky': style = 'background-color: #fee2e2; color: #ef4444;'; break;
                                    case 'Most Risky': style = 'background-color: #fca5a5; color: #b91c1c;'; break;
                                    default: style = 'background-color: #d1fae5; color: #065f46;'; break;
                                }
                                ratingHtml = `<span class="pill" style="${style} font-weight: 700;">${item.rating}</span>`;
                            }

                            // Build formatters
                            const formatM = (val) => (val / 1000000).toFixed(2) + 'M';

                            tr.innerHTML = `
                                <td>
                                    <a href="{{ route('dashboard.clients.index') }}?segment=${encodeURIComponent(item.segment)}&range=${encodeURIComponent(item.range)}&month=${encodeURIComponent(item.month_param)}&client_id=${item.client_id}" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 700;">
                                        ${item.client_name || 'N/A'}
                                    </a>
                                </td>
                                <td class="amount">${formatM(item.os)}</td>
                                <td class="amount">${formatM(item.mrc)}</td>
                                <td class="amount" style="color: #ef4444; font-weight: 800;">${formatM(item.mrc_shortfall)}</td>
                                <td class="amount" style="color: #ea580c; font-weight: 800;">${formatM(item.backlog_shortfall)}</td>
                                <td>${(item.cr || 0).toFixed(2)}</td>
                                <td>${ratingHtml}</td>
                                <td>
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <input type="text" id="guidance-${item.client_id}" placeholder="Write guidance..." style="width: 100%; min-width: 160px; padding: 6px 10px; border-radius: 4px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size:12px;">
                                        <button class="button primary btn-small" onclick="submitInlineGuidance(${item.client_id})" style="padding: 6px 10px; min-height: unset; height: 28px; font-size: 11px;">Save</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });

                        // Info text
                        const showStart = totalEntries === 0 ? 0 : startIdx + 1;
                        const showEnd = endIdx;
                        tableInfo.textContent = `Showing ${showStart} to ${showEnd} of ${totalEntries} entries`;

                        // Pagination controls
                        renderPagination(totalPages);
                    }

                    function renderPagination(totalPages) {
                        pagination.innerHTML = '';
                        if (totalPages <= 1) return;

                        const btnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 600; border: 1px solid var(--line, #e2e8f0); border-radius: 6px; background: #ffffff; color: var(--ink, #1e293b); cursor: pointer; transition: all 0.2s; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
                        const activeBtnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 700; border: 1px solid #3b82f6; border-radius: 6px; background: #3b82f6; color: #ffffff; cursor: pointer; transition: all 0.2s; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(59,130,246,0.15);';
                        const disabledBtnCss = 'padding: 6px 12px; font-size: 13px; font-weight: 600; border: 1px solid var(--line, #e2e8f0); border-radius: 6px; background: #f8fafc; color: #94a3b8; cursor: not-allowed; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; opacity: 0.6;';

                        function styleBtn(btn, isActive, isDisabled) {
                            if (isDisabled) {
                                btn.style.cssText = disabledBtnCss;
                                btn.disabled = true;
                            } else if (isActive) {
                                btn.style.cssText = activeBtnCss;
                            } else {
                                btn.style.cssText = btnCss;
                                btn.addEventListener('mouseenter', () => btn.style.backgroundColor = '#f1f5f9');
                                btn.addEventListener('mouseleave', () => btn.style.backgroundColor = '#ffffff');
                            }
                        }

                        // Prev button
                        const prevBtn = document.createElement('button');
                        prevBtn.textContent = '« Prev';
                        styleBtn(prevBtn, false, currentPage === 1);
                        prevBtn.addEventListener('click', () => {
                            currentPage--;
                            render();
                        });
                        pagination.appendChild(prevBtn);

                        // Calculate range of page numbers to show
                        let startPage = Math.max(1, currentPage - 2);
                        let endPage = Math.min(totalPages, currentPage + 2);

                        // Adjust if near boundary
                        if (currentPage <= 3) {
                            endPage = Math.min(5, totalPages);
                        }
                        if (currentPage >= totalPages - 2) {
                            startPage = Math.max(1, totalPages - 4);
                        }

                        // First page indicator
                        if (startPage > 1) {
                            const pBtn = document.createElement('button');
                            pBtn.textContent = '1';
                            styleBtn(pBtn, false, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = 1;
                                render();
                            });
                            pagination.appendChild(pBtn);

                            if (startPage > 2) {
                                const dots = document.createElement('span');
                                dots.textContent = '...';
                                dots.style.alignSelf = 'center';
                                dots.style.padding = '0 6px';
                                dots.style.color = 'var(--muted, #64748b)';
                                dots.style.fontSize = '13px';
                                pagination.appendChild(dots);
                            }
                        }

                        // Page numbers
                        for (let i = startPage; i <= endPage; i++) {
                            const pBtn = document.createElement('button');
                            pBtn.textContent = i;
                            styleBtn(pBtn, i === currentPage, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = i;
                                render();
                            });
                            pagination.appendChild(pBtn);
                        }

                        // Last page indicator
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                const dots = document.createElement('span');
                                dots.textContent = '...';
                                dots.style.alignSelf = 'center';
                                dots.style.padding = '0 6px';
                                dots.style.color = 'var(--muted, #64748b)';
                                dots.style.fontSize = '13px';
                                pagination.appendChild(dots);
                            }

                            const pBtn = document.createElement('button');
                            pBtn.textContent = totalPages;
                            styleBtn(pBtn, false, false);
                            pBtn.addEventListener('click', () => {
                                currentPage = totalPages;
                                render();
                            });
                            pagination.appendChild(pBtn);
                        }

                        // Next button
                        const nextBtn = document.createElement('button');
                        nextBtn.textContent = 'Next »';
                        styleBtn(nextBtn, false, currentPage === totalPages);
                        nextBtn.addEventListener('click', () => {
                            currentPage++;
                            render();
                        });
                        pagination.appendChild(nextBtn);
                    }

                    searchInput.addEventListener('input', () => {
                        currentPage = 1;
                        render();
                    });

                    pageSizeSelect.addEventListener('change', (e) => {
                        pageSize = parseInt(e.target.value) || 10;
                        currentPage = 1;
                        render();
                    });

                    render();
                })();
            </script>
        </section>
    </div>

</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<script>
    function toggleGlossaryModal() {
        const modal = document.getElementById('glossaryModal');
        const content = document.getElementById('glossaryContent');
        if (modal && content) {
            const isVisible = modal.style.opacity === '1';
            if (isVisible) {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                content.style.transform = 'scale(0.95)';
            } else {
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                content.style.transform = 'scale(1)';
            }
        }
    }

    let guidanceLogCurrentPage = 1;
    let guidanceLogSearchTerm = '';
    let guidanceLogSearchDebounce = null;

    function toggleGuidanceLogsModal() {
        const modal = document.getElementById('guidanceLogsModal');
        const content = document.getElementById('guidanceLogsContent');
        if (!modal) return;
        
        const isOpen = modal.style.opacity === '1';
        if (isOpen) {
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
            content.style.transform = 'scale(0.95)';
        } else {
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
            content.style.transform = 'scale(1)';
            loadGuidanceLogs(1);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadGuidanceLogs(page = 1) {
        guidanceLogCurrentPage = page;
        const container = document.getElementById('guidanceLogListContainer');
        const pageInfo = document.getElementById('guidanceLogPageInfo');
        const totalCount = document.getElementById('guidanceLogTotalCount');
        const prevBtn = document.getElementById('guidanceLogPrevBtn');
        const nextBtn = document.getElementById('guidanceLogNextBtn');

        if (container) container.innerHTML = '<div style="text-align:center; padding: 24px; color: var(--muted);">Loading logs...</div>';

        const url = `{{ route('web.api.guidance-logs') }}?page=${page}&search=${encodeURIComponent(guidanceLogSearchTerm)}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                if (totalCount) totalCount.innerText = `Total Logs: ${res.total}`;
                if (pageInfo) pageInfo.innerText = `Page ${res.current_page} of ${res.last_page || 1}`;

                if (prevBtn) prevBtn.disabled = res.current_page <= 1;
                if (nextBtn) nextBtn.disabled = res.current_page >= res.last_page;

                if (!res.data || res.data.length === 0) {
                    if (container) container.innerHTML = '<div style="text-align:center; padding: 24px; color: var(--muted);">No guidance logs found matching search.</div>';
                    return;
                }

                let html = '<div style="display:flex; flex-direction:column; gap:12px;">';
                res.data.forEach(log => {
                    html += `
                        <div style="border-bottom: 1px solid var(--line); padding-bottom: 10px; text-align: left;">
                            <div style="display:flex; justify-content:space-between; font-size: 11px; color: var(--muted); margin-bottom: 4px;">
                                <strong>${escapeHtml(log.user_name)} (${escapeHtml(log.user_role)})</strong>
                                <span>${escapeHtml(log.created_at_formatted || log.created_at_human)}</span>
                            </div>
                            <div style="font-size: 13.5px; color: var(--ink); margin-bottom: 4px;">
                                <strong style="color:var(--primary);">${escapeHtml(log.client_name)}</strong>: 
                                ${escapeHtml(log.guidance_text)}
                            </div>
                            ${log.action_taken ? `<span class="pill info" style="font-size:10px; background-color:#eff6ff; color:#3b82f6; padding: 2px 6px; border-radius:4px; font-weight:800;">Action: ${escapeHtml(log.action_taken)}</span>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                if (container) container.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                if (container) container.innerHTML = '<div style="text-align:center; padding: 24px; color: #ef4444;">Failed to load guidance logs.</div>';
            });
    }

    function fetchGuidanceLogsPage(delta) {
        loadGuidanceLogs(guidanceLogCurrentPage + delta);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('guidanceLogSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(guidanceLogSearchDebounce);
                guidanceLogSearchTerm = e.target.value;
                guidanceLogSearchDebounce = setTimeout(() => {
                    loadGuidanceLogs(1);
                }, 300);
            });
        }
    });

    function updateWorkflowStatus(clientId, status, actionTaken = '') {
        if (!confirm('Are you sure you want to update this client\'s workflow status?')) return;

        fetch('{{ route("dashboard.workflow.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                client_id: clientId,
                status: status,
                action_taken: actionTaken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error updating status');
            }
        })
        .catch(err => console.error(err));
    }

    function submitInlineGuidance(clientId) {
        const textEl = document.getElementById('guidance-' + clientId);
        if (!textEl) return;
        const text = textEl.value;

        if (!text.trim()) {
            alert('Please enter guidance text.');
            return;
        }

        fetch('{{ route("dashboard.guidance.log") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                client_id: clientId,
                guidance_text: text,
                action_taken: 'Management Guidance Comment'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Guidance logged successfully.');
                location.reload();
            } else {
                alert('Error logging guidance.');
            }
        })
        .catch(err => console.error(err));
    }

    const trendChart = @json($trendChart);

    const trendCanvas = document.getElementById('backlogCollectionTrend');

    if (trendCanvas) {

        new Chart(trendCanvas, {
            type: 'line',

            data: {
                labels: trendChart.labels,
                datasets: [
                    {
                        label: 'Total Opening OS (MRC+Backlog)',
                        data: trendChart.total_opening_os,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'Total Collection',
                        data: trendChart.total_collection,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'Total MRC',
                        data: trendChart.total_mrc,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'MRC Collection (LIFO)',
                        data: trendChart.collection_mrc,
                        borderColor: '#84cc16',
                        backgroundColor: 'rgba(132, 204, 22, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'Net Backlog',
                        data: trendChart.net_backlog_total,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'Backlog Collection (LIFO)',
                        data: trendChart.collection_backlog,
                        borderColor: '#ec4899',
                        backgroundColor: 'rgba(236, 72, 153, .08)',
                        tension: .35,
                        fill: false,
                        pointRadius: 4,
                    },
                    {
                        label: 'Total Latest/Closing OS',
                        data: trendChart.total_latest_os,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, .08)',
                        tension: .35,
                        fill: false,
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

    // --- HORIZONTAL TIMELINE LOGIC ---
    (function initTimeline() {
        const availableMonths = @json($availableMonths ?? []);
        const activeMonth = @json($selectedMonth ?? '');
        
        const baseDateStr = activeMonth || (availableMonths.length > 0 ? availableMonths[availableMonths.length - 1] : new Date().toISOString().slice(0, 10));
        let anchorDate = new Date(baseDateStr);
        let windowOffset = 0;

        function renderTimeline() {
            const track = document.getElementById('timelineTrack');
            if (!track) return;
            track.innerHTML = '';

            const endDate = new Date(anchorDate.getFullYear(), anchorDate.getMonth() + 2 + windowOffset, 1);
            const monthsWindow = [];

            for (let i = 17; i >= 0; i--) {
                const d = new Date(endDate.getFullYear(), endDate.getMonth() - i, 1);
                monthsWindow.push(d);
            }

            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

            monthsWindow.forEach(d => {
                const year = d.getFullYear();
                const monthNum = String(d.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(year, d.getMonth() + 1, 0).getDate();
                const fullDateStr = `${year}-${monthNum}-${String(lastDay).padStart(2, '0')}`;
                const label = `${monthNames[d.getMonth()]} ${String(year).slice(2)}`;

                const isActive = activeMonth && (activeMonth.startsWith(`${year}-${monthNum}`));

                const pill = document.createElement('a');
                pill.href = `?month=${fullDateStr}`;
                pill.className = 'timeline-pill' + (isActive ? ' active-pill' : '');
                pill.style.cssText = isActive
                    ? 'background: #2563eb; color: #ffffff; border: 1px solid #2563eb; border-radius: 20px; padding: 4px 12px; font-size: 11px; font-weight: 800; white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(37,99,235,0.3); flex-shrink: 0;'
                    : 'background: rgba(0,0,0,0.03); color: var(--ink); border: 1px solid var(--line); border-radius: 20px; padding: 4px 12px; font-size: 11px; font-weight: 700; white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; transition: all 0.2s;';

                if (isActive) {
                    pill.innerHTML = `<span style="width: 6px; height: 6px; background: #ffffff; border-radius: 50%;"></span>${label}`;
                } else {
                    pill.innerHTML = label;
                }

                track.appendChild(pill);
            });

            setTimeout(() => {
                const activePill = track.querySelector('.active-pill');
                if (activePill) {
                    activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                } else {
                    track.scrollTo({ left: track.scrollWidth, behavior: 'smooth' });
                }
            }, 80);
        }

        renderTimeline();

        const track = document.getElementById('timelineTrack');
        const prevBtn = document.getElementById('timelinePrevBtn');
        const nextBtn = document.getElementById('timelineNextBtn');

        if (prevBtn && track) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (track.scrollLeft <= 10) {
                    windowOffset -= 6;
                    renderTimeline();
                } else {
                    track.scrollBy({ left: -220, behavior: 'smooth' });
                }
            });
        }

        if (nextBtn && track) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
                    windowOffset += 6;
                    renderTimeline();
                } else {
                    track.scrollBy({ left: 220, behavior: 'smooth' });
                }
            });
        }
     })();

     // Async load employee avatar images
     document.addEventListener('DOMContentLoaded', () => {
         document.querySelectorAll('.emp-avatar-lazy').forEach(img => {
             const email = img.getAttribute('data-email');
             if (email && email.trim() !== '') {
                 fetch(`{{ route('employee.avatar.json') }}?email=${encodeURIComponent(email)}`)
                     .then(response => response.json())
                     .then(data => {
                         if (data.success && data.image) {
                             img.src = data.image;
                         }
                     })
                     .catch(err => {
                         // Fallback remains as the default ui-avatars.com source
                     });
             }
         });

         // Modal preview triggers
         const modal = document.getElementById('avatarPreviewModal');
         const previewImg = document.getElementById('avatarPreviewImg');
         const previewName = document.getElementById('avatarPreviewName');
         const previewEmail = document.getElementById('avatarPreviewEmail');

         document.querySelectorAll('.emp-avatar-lazy').forEach(img => {
             img.addEventListener('click', () => {
                 const email = img.getAttribute('data-email');
                 const name = img.getAttribute('alt');
                 
                 previewImg.src = img.src;
                 previewName.textContent = name;
                 previewEmail.textContent = email && email.trim() !== '' ? email : 'No Email Assigned';
                 
                 modal.style.display = 'flex';
             });
         });

         // Close modal handlers
         const closeModal = () => {
             modal.style.display = 'none';
         };
         
         document.querySelector('.avatar-modal-close').addEventListener('click', closeModal);
         modal.addEventListener('click', (e) => {
             if (e.target === modal) {
                 closeModal();
             }
         });
     });
 </script>

 <!-- Avatar Image Preview Modal -->
 <div id="avatarPreviewModal" class="avatar-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.85); align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: all 0.3s ease;">
     <span class="avatar-modal-close" style="position: absolute; top: 20px; right: 30px; color: #f8fafc; font-size: 40px; font-weight: bold; cursor: pointer; transition: 0.2s;">&times;</span>
     <div style="background: white; padding: 12px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; max-width: 90%; max-height: 90%; display: flex; flex-direction: column; align-items: center;">
         <img id="avatarPreviewImg" src="" style="width: 240px; height: 240px; border-radius: 8px; object-fit: cover; border: 2px solid #e2e8f0;">
         <h3 id="avatarPreviewName" style="margin-top: 14px; margin-bottom: 4px; font-size: 18px; font-weight: 700; color: #0f172a;"></h3>
         <p id="avatarPreviewEmail" style="font-size: 14px; color: #64748b; margin: 0; font-family: monospace;"></p>
     </div>
 </div>
@endpush
