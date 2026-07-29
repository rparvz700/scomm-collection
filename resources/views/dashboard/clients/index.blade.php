@extends('layouts.app')

@section('title', 'Client Drilldown')

@push('styles')
<style>

    .page-header {
        margin-bottom: 18px;
    }

    .page-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
    }

    .page-header p {
        margin: 6px 0 0;
        color: var(--muted);
    }

    .panel {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    th, td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--line);
        white-space: nowrap;
        font-size: 13px;
    }

    th {
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        color: var(--muted);
    }

    .amount {
        text-align: right;
    }

    .trend-btn {
        padding: 6px 10px;
        border-radius: 6px;
        background: #0f766e;
        color: #fff;
        font-size: 12px;
        text-decoration: none;
        cursor: pointer;
        display: inline-block;
    }

    .trend-btn:hover {
        opacity: .9;
    }

    /* MODAL */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,.55);
    }

    .modal-content {
        background: #fff;
        margin: 5% auto;
        padding: 20px;
        width: 85%;
        border-radius: 10px;
        max-height: 80vh;
        overflow: auto;
    }

    .modal-content.large {
        width: 90%;
        max-height: 90vh;
        margin: 3% auto;
    }

    .close {
        float: right;
        font-size: 26px;
        cursor: pointer;
    }

    .filter-bar {
        margin: 10px 0 20px;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    select {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid var(--line);
    }

    .meta-line {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: center;
        margin-top: 10px;
        font-size: 14px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
    }

    .meta-tag {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        letter-spacing: .2px;
    }

    /* Segment */
    .segment-tag {
        background: rgba(15, 118, 110, .12);
        color: #0f766e;
    }

    /* Range */
    .range-tag {
        background: rgba(59, 130, 246, .12);
        color: #2563eb;
    }

    /* Risk */
    .risk-tag {
        background: rgba(239, 68, 68, .12);
        color: #dc2626;
    }

    /* Month */
    .month-tag {
        background: rgba(168, 85, 247, .12);
        color: #7e22ce;
    }

    /* Total Clients */
    .client-tag {
        background: rgba(245, 158, 11, .12);
        color: #d97706;
    }

    .trend-modal-content {
        width: 90%;
        max-width: 1200px;
        padding: 22px;
    }

    .trend-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    #clientName {
        margin: 0;
        padding: 8px 16px;
        border-radius: 999px;

        background: linear-gradient(
            135deg,
            #8e76c1,
            #5e779e
        );

        color: #ffffff;

        font-size: 24px;
        font-weight: 800;
        letter-spacing: -.4px;

        box-shadow:
            0 8px 20px rgba(15, 23, 42, .18);

        position: relative;
    }

    #clientName::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;

        background:
            linear-gradient(
                135deg,
                rgba(21, 90, 137, 0.12),
                transparent
            );

        pointer-events: none;
    }

    .trend-title {
        margin: 0;

        font-size: 24px;
        font-weight: 700;

        color: #3a4c78;

        letter-spacing: -.4px;
    }

    .metric-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }

    .metric-selector input {
        display: none;
    }

    .metric-selector label {
        padding: 6px 12px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all .16s ease;
        line-height: 1;
        letter-spacing: .2px;
        user-select: none;
    }

    /* Hover */
    .metric-selector label:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    /* Active */
    .metric-selector input:checked + label {
        background: #0f172a;
        border-color: #0f172a;
        color: #ffffff;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, .08),
            0 0 0 3px rgba(15, 23, 42, .05);
    }

    /* Click feel */
    .metric-selector label:active {
        transform: scale(.97);
    }

    .chart-wrap {
        position: relative;
        height: 250px;
    }

    /* Client details grid styling */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .details-section {
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 16px;
        background: #f8fafc;
    }

    .details-section h3 {
        margin-top: 0;
        margin-bottom: 12px;
        font-size: 15px;
        color: var(--primary-dark);
        border-bottom: 1px solid var(--line);
        padding-bottom: 6px;
    }

    .details-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 13px;
        gap: 12px;
    }

    .details-row:last-child {
        border-bottom: none;
    }

    .details-label {
        font-weight: 600;
        color: var(--muted);
    }

    .details-value {
        font-weight: 700;
        color: var(--ink);
        text-align: right;
    }

    .client-detail-link {
        color: var(--primary);
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border-bottom: 1px dashed var(--primary);
    }

    .client-detail-link:hover {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    .modal-client-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--line);
        padding-bottom: 12px;
    }

    .modal-client-title {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: var(--primary-dark);
    }

    .modal-client-badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 999px;
        font-weight: 800;
        text-transform: uppercase;
        background: #edf2f7;
    }

    .status-badge-active {
        background-color: #dcfce7 !important;
        color: #15803d !important;
    }

    .status-badge-discontinued {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
    }

    .status-badge-watchlist {
        background-color: #fef3c7 !important;
        color: #b45309 !important;
    }

    .status-badge-other {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
    }

    @keyframes row-pulse-highlight {
        0% { background-color: #fef08a; }
        100% { background-color: #fef08a; }
    }
    .row-highlighted td {
        animation: row-pulse-highlight 3s ease-in-out forwards;
    }

</style>@endpush

@section('content')

<div class="page-header">
    <h1>Client Drilldown</h1>
    <p class="meta-line">

        <span class="meta-item">
            Segment:
            <strong class="meta-tag segment-tag">
                {{ $segment }}
            </strong>
        </span>

        <span class="meta-item">
            CR Range:
            <strong class="meta-tag range-tag">
                {{ $range }}
            </strong>
        </span>

        <span class="meta-item">
            Tag:
            <strong class="meta-tag risk-tag">
                {{ $riskCategory }}
            </strong>
        </span>

        <span class="meta-item">
            Month:
            <strong class="meta-tag month-tag">
                {{ $month?->format('Y-m') }}
            </strong>
        </span>

        <span class="meta-item">
            Total Clients:
            <strong class="meta-tag client-tag">
                {{ count($clients) }}
            </strong>
        </span>

    </p>
</div>

<div class="panel">

    <table>

        <thead>
            <tr>
                <th>Client</th>
                <th>Opening CR</th>
                <th>Opening OS</th>
                <th>Closing CR</th>
                <th>Closing OS</th>
                <th>MRC</th>
                <th>Total Backlog</th>
                <th>Collection</th>
                <th>Payment Plan</th>
                <th>Shortfall</th>
                <th>Trend</th>
            </tr>
        </thead>

        <tbody>

            @foreach ($clients as $c)
                @php
                    $prevSummary = $previousSummaries->get($c->client_id);
                @endphp
                <tr id="client-row-{{ $c->client_id }}">

                    <td>
                        @if ($c->client)
                            <a href="javascript:void(0)" class="client-detail-link" data-summary="{{ json_encode([
                                'client_id' => $c->client_id,
                                'client_name' => $c->client->client_name,
                                'current' => [
                                    'client_name' => $c->client->client_name,
                                    'opus_id' => $c->client->opus_id,
                                    'client_status' => $c->client->client_status,
                                    'agreement_status' => $c->client->agreement_status,
                                    'barring_priority' => $c->client->barring_priority,
                                    'btrc_license_discontinuation_date' => $c->client->btrc_license_discontinuation_date?->format('Y-m-d'),
                                    'legal' => $c->client->legal ? 'Yes' : 'No',
                                    'billing_modality_kpi' => $c->client->billing_modality_kpi,
                                    'service_type_billing' => $c->client->service_type_billing,
                                    'license_billing' => $c->client->license_billing,
                                    'btrc_letter' => $c->client->btrc_letter,
                                    'security_coverage' => $c->client->security_coverage,
                                    'payment_plan' => $c->client->payment_plan,
                                    'other_upstream' => $c->client->other_upstream ? 'Yes' : 'No',
                                    'sm_kam' => $c->client->sm_kam,
                                    'team_name' => $c->client->team_name,
                                    'collection_kam' => $c->client->collection_kam,
                                    'collection_supervisor' => $c->client->collection_supervisor,
                                    'nttn_billing_kam' => $c->client->nttn_billing_kam,
                                    'iig_itc_billing_kam' => $c->client->iig_itc_billing_kam,
                                    'nttn_billing_commencement_date' => $c->client->nttn_billing_commencement_date?->format('Y-m-d'),
                                    'iig_itc_billing_commencement_date' => $c->client->iig_itc_billing_commencement_date?->format('Y-m-d'),
                                    
                                    'opening_cr' => number_format($c->opening_cr, 2),
                                    'opening_os' => number_format($c->total_opening_os / 1000000, 2) . 'M',
                                    'closing_cr' => number_format($c->latest_cr, 2),
                                    'closing_os' => number_format($c->total_latest_os / 1000000, 2) . 'M',
                                    'mrc' => number_format($c->total_mrc / 1000000, 2) . 'M',
                                    'backlog' => number_format($c->net_backlog_total / 1000000, 2) . 'M',
                                    'collection' => number_format($c->total_collection / 1000000, 2) . 'M',
                                    'payment_plan_amount' => number_format($c->total_payment_plan / 1000000, 2) . 'M',
                                    'shortfall' => number_format(($c->shortfall_from_payment_plan ?? 0) / 1000000, 2) . 'M',
                                    'remarks' => $c->current_month_remarks,
                                    'actual_month' => $c->summary_month ? $c->summary_month->format('F Y') : (optional($month)->format('F Y') ?? 'This Month'),
                                ],
                                'previous' => $prevSummary ? [
                                    'client_name' => $c->client->client_name,
                                    'opus_id' => $previousClientStates[$c->client_id]['opus_id'] ?? 'N/A',
                                    'client_status' => $previousClientStates[$c->client_id]['client_status'] ?? 'N/A',
                                    'agreement_status' => $previousClientStates[$c->client_id]['agreement_status'] ?? 'N/A',
                                    'barring_priority' => $previousClientStates[$c->client_id]['barring_priority'] ?? 'N/A',
                                    'btrc_license_discontinuation_date' => $previousClientStates[$c->client_id]['btrc_license_discontinuation_date'] ?? 'N/A',
                                    'legal' => $previousClientStates[$c->client_id]['legal'] ?? 'No',
                                    'billing_modality_kpi' => $previousClientStates[$c->client_id]['billing_modality_kpi'] ?? 'N/A',
                                    'service_type_billing' => $previousClientStates[$c->client_id]['service_type_billing'] ?? 'N/A',
                                    'license_billing' => $previousClientStates[$c->client_id]['license_billing'] ?? 'N/A',
                                    'btrc_letter' => $previousClientStates[$c->client_id]['btrc_letter'] ?? 'N/A',
                                    'security_coverage' => $previousClientStates[$c->client_id]['security_coverage'] ?? 'N/A',
                                    'payment_plan' => $previousClientStates[$c->client_id]['payment_plan'] ?? 'N/A',
                                    'other_upstream' => $previousClientStates[$c->client_id]['other_upstream'] ?? 'No',
                                    'sm_kam' => $previousClientStates[$c->client_id]['sm_kam'] ?? 'N/A',
                                    'team_name' => $previousClientStates[$c->client_id]['team_name'] ?? 'N/A',
                                    'collection_kam' => $previousClientStates[$c->client_id]['collection_kam'] ?? 'N/A',
                                    'collection_supervisor' => $previousClientStates[$c->client_id]['collection_supervisor'] ?? 'N/A',
                                    'nttn_billing_kam' => $previousClientStates[$c->client_id]['nttn_billing_kam'] ?? 'N/A',
                                    'iig_itc_billing_kam' => $previousClientStates[$c->client_id]['iig_itc_billing_kam'] ?? 'N/A',
                                    'nttn_billing_commencement_date' => $previousClientStates[$c->client_id]['nttn_billing_commencement_date'] ?? 'N/A',
                                    'iig_itc_billing_commencement_date' => $previousClientStates[$c->client_id]['iig_itc_billing_commencement_date'] ?? 'N/A',
 
                                    'opening_cr' => number_format($prevSummary->opening_cr, 2),
                                    'opening_os' => number_format($prevSummary->total_opening_os / 1000000, 2) . 'M',
                                    'closing_cr' => number_format($prevSummary->latest_cr, 2),
                                    'closing_os' => number_format($prevSummary->total_latest_os / 1000000, 2) . 'M',
                                    'mrc' => number_format($prevSummary->total_mrc / 1000000, 2) . 'M',
                                    'backlog' => number_format($prevSummary->net_backlog_total / 1000000, 2) . 'M',
                                    'collection' => number_format($prevSummary->total_collection / 1000000, 2) . 'M',
                                    'payment_plan_amount' => number_format($prevSummary->total_payment_plan / 1000000, 2) . 'M',
                                    'shortfall' => number_format(($prevSummary->shortfall_from_payment_plan ?? 0) / 1000000, 2) . 'M',
                                    'remarks' => $prevSummary->current_month_remarks,
                                    'actual_month' => $prevSummary->summary_month ? $prevSummary->summary_month->format('F Y') : ($month ? $month->copy()->subMonth()->format('F Y') : 'Last Month'),
                                ] : null,
                            ]) }}">
                                {{ $c->client->client_name }}
                            </a>
                            @if ($prevSummary)
                                @php
                                    $currentCr = (float) $c->latest_cr;
                                    $prevCr = (float) $prevSummary->latest_cr;
                                    $currentMonthName = $c->summary_month ? $c->summary_month->format('F Y') : 'Current Month';
                                    $prevMonthName = $prevSummary->summary_month ? $prevSummary->summary_month->format('F Y') : 'Previous Month';
                                    $latestRating = $c->latest_rating_category ?? 'N/A';
                                @endphp

                                @if ($currentCr < $prevCr)
                                    <span style="color: #16a34a; margin-left: 6px; cursor: help; font-weight: bold; font-size: 14px;" 
                                          title="Current Month ({{ $currentMonthName }}): {{ number_format($currentCr, 2) }} vs Last Month ({{ $prevMonthName }}): {{ number_format($prevCr, 2) }} Closing CR | Latest Rating Category: {{ $latestRating }}">▲</span>
                                @elseif ($currentCr > $prevCr)
                                    <span style="color: #dc2626; margin-left: 6px; cursor: help; font-weight: bold; font-size: 14px;" 
                                          title="Current Month ({{ $currentMonthName }}): {{ number_format($currentCr, 2) }} vs Last Month ({{ $prevMonthName }}): {{ number_format($prevCr, 2) }} Closing CR | Latest Rating Category: {{ $latestRating }}">▼</span>
                                @endif
                            @endif
                        @else
                            N/A
                        @endif
                    </td>

                    <td class="amount">
                        {{ number_format($c->opening_cr, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_opening_os / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format($c->latest_cr, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_latest_os / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_mrc / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format($c->net_backlog_total / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_collection / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_payment_plan / 1000000, 2) }}M
                    </td>

                    <td class="amount">
                        {{ number_format(($c->shortfall_from_payment_plan ?? 0) / 1000000, 2) }}M
                    </td>

                    <td>
                        <a href="javascript:void(0)"
                           class="trend-btn"
                           data-client="{{ $c->client_id }}"
                            data-client-name="{{ $c->client->client_name ?? 'N/A' }}"
                           data-url="{{ route('dashboard.client.trend', $c->client_id) }}">
                            View Trend
                        </a>
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>

{{-- TREND MODAL --}}
<div id="trendModal" class="modal">

    <div class="modal-content trend-modal-content">

        <span class="close" onclick="closeTrend()">&times;</span>
        
        <div class="trend-header">
            <h2 id="clientName">Client Name</h2>
            <h2 class="trend-title">Trend Analysis</h2>
        </div>

        <!-- History Statistics Cards -->
        <div id="trendHistoryStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 15px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--line); text-align: center;">
                <span style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">Client Status</span>
                <span id="statClientStatus" class="modal-client-badge" style="font-size: 13px; padding: 4px 12px; font-weight: 800; text-transform: uppercase; display: inline-block;">N/A</span>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--line); text-align: center;">
                <span style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">Service Discont. Date</span>
                <strong id="statDiscontinuationDate" style="font-size: 14px; color: var(--ink); font-weight: 700;">N/A</strong>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--line); text-align: center;">
                <span style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">Opening Rating Category</span>
                <span id="statOpeningRating" class="modal-client-badge" style="font-size: 13px; padding: 4px 12px; font-weight: 800; text-transform: uppercase; display: inline-block;">N/A</span>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--line); text-align: center;">
                <span style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">Latest Rating Category</span>
                <span id="statLatestRating" class="modal-client-badge" style="font-size: 13px; padding: 4px 12px; font-weight: 800; text-transform: uppercase; display: inline-block;">N/A</span>
            </div>
        </div>
        

        <div class="metric-selector">

            <input type="checkbox" id="metric-opening-cr" value="opening_cr" checked hidden>
            <label for="metric-opening-cr">Opening CR</label>

            <input type="checkbox" id="metric-opening-os" value="opening_os" hidden>
            <label for="metric-opening-os">Opening OS</label>

            <input type="checkbox" id="metric-closing-cr" value="closing_cr" checked hidden>
            <label for="metric-closing-cr">Closing CR</label>

            <input type="checkbox" id="metric-closing-os" value="closing_os" hidden>
            <label for="metric-closing-os">Closing OS</label>

            <input type="checkbox" id="metric-mrc" value="mrc" hidden>
            <label for="metric-mrc">MRC</label>

            <input type="checkbox" id="metric-backlog" value="backlog" hidden>
            <label for="metric-backlog">Backlog</label>

            <input type="checkbox" id="metric-collection" value="collection" hidden>
            <label for="metric-collection">Collection</label>

        </div>

        <div class="chart-wrap">
            <canvas id="trendChart"></canvas>
        </div>

        <div id="trendHistorySection" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 1px solid var(--line); padding-top: 20px; margin-top: 20px;">
            <div>
                <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 800; text-transform: uppercase; color: var(--primary-dark);">Monthly Rating History</h3>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px; padding: 4px; background: #fff;">
                    <table style="min-width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--line);">
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Month</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: right;">Opening CR</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Opening Rating</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: right;">Closing CR</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Latest Rating</th>
                            </tr>
                        </thead>
                        <tbody id="trendSnapshotsBody">
                            <!-- Filled dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 800; text-transform: uppercase; color: var(--primary-dark);">Client Change Logs</h3>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px; padding: 4px; background: #fff;">
                    <table style="min-width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--line);">
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Date</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Field</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">Old Value</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">New Value</th>
                                <th style="padding: 8px; font-size: 11px; text-transform: uppercase; color: var(--muted); text-align: left;">By</th>
                            </tr>
                        </thead>
                        <tbody id="trendLogsBody">
                            <!-- Filled dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- CLIENT DETAILS MODAL --}}
<div id="clientDetailsModal" class="modal">
    <div class="modal-content large">
        <span class="close" onclick="closeClientDetails()">&times;</span>
        
        <div class="modal-client-header">
            <div style="display: flex; align-items: center; gap: 14px;">
                <h2 class="modal-client-title" id="m-clientName">Client Details</h2>
                <button id="modalHistoryBtn" style="padding: 6px 12px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; background: #0f766e; color: #fff;" type="button">Last 12 Month Trend</button>
                <button id="modalToggleMonthBtn" style="padding: 6px 12px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; background: #475569; color: #fff;" type="button">Last Month Data</button>
            </div>
            <span class="modal-client-badge" id="m-clientStatus">Status</span>
        </div>

        <div id="clientDetailsModalBody">
            <!-- Populated via Javascript -->
        </div>
    </div>
</div>

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    let trendChartInstance = null;
    let activeClientId = null;

    document.querySelectorAll('.trend-btn').forEach(btn => {

        btn.addEventListener('click', function () {

            const clientName = this.dataset.clientName;

            document.getElementById('clientName').innerText = clientName;

            activeClientId = this.dataset.client;

            document.getElementById('trendModal').style.display = 'block';

            loadTrend();

        });

    });

    document.querySelectorAll('.metric-selector input')
        .forEach(el => {
            el.addEventListener('change', loadTrend);
        });

    async function loadTrend() {

    if (!activeClientId) {
        return;
    }

    const activeButton = document.querySelector(
        `.trend-btn[data-client="${activeClientId}"]`
    );

    const url = activeButton.dataset.url;

    const response = await fetch(url);

    const trendData = await response.json();

    const allDatasets = {

        opening_cr: {
            label: 'Opening CR',
            data: trendData.opening_cr,
            borderColor: '#2563eb',
            backgroundColor: '#2563eb',
            yAxisID: 'y1',
        },

        opening_os: {
            label: 'Opening OS',
            data: trendData.opening_os,
            borderColor: '#fddc04',
            backgroundColor: '#fddc04',
            yAxisID: 'y',
        },

        closing_cr: {
            label: 'Closing CR',
            data: trendData.closing_cr,
            borderColor: '#dc2626',
            backgroundColor: '#dc2626',
            yAxisID: 'y1',
        },

        closing_os: {
            label: 'Closing OS',
            data: trendData.closing_os,
            borderColor: '#ea0ca4',
            backgroundColor: '#ea0ca4',
            yAxisID: 'y',
        },

        mrc: {
            label: 'MRC',
            data: trendData.mrc,
            borderColor: '#014e5a',
            backgroundColor: '#014e5a',
            yAxisID: 'y',
        },

        backlog: {
            label: 'Backlog',
            data: trendData.backlog,
            borderColor: '#7d0909',
            backgroundColor: '#7d0909',
            yAxisID: 'y',
        },

        collection: {
            label: 'Collection',
            data: trendData.collection,
            borderColor: '#16a34a',
            backgroundColor: '#16a34a',
            yAxisID: 'y',
        }

    };

    const selectedMetrics = [];

    document.querySelectorAll('.metric-selector input:checked')
        .forEach(el => {

            const key = el.value;

            selectedMetrics.push({
                ...allDatasets[key],
                tension: 0.35,
                fill: false,
                pointRadius: 3
            });

        });

    if (trendChartInstance) {
        trendChartInstance.destroy();
    }

    const ctx = document.getElementById('trendChart');

    trendChartInstance = new Chart(ctx, {

        type: 'line',

        data: {
            labels: trendData.labels,
            datasets: selectedMetrics
        },

        options: {

            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false,
            },

            plugins: {

                legend: {
                    position: 'bottom',

                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 10,
                        boxHeight: 10,
                        padding: 18,

                        generateLabels(chart) {

                            const datasets = chart.data.datasets;

                            return datasets.map((dataset, i) => ({

                                text: dataset.label,
                                fillStyle: dataset.borderColor,
                                strokeStyle: dataset.borderColor,
                                lineWidth: 0,
                                hidden: !chart.isDatasetVisible(i),
                                datasetIndex: i,
                                pointStyle: 'circle'

                            }));
                        }
                    }
                }

            },

            scales: {

                y: {
                    type: 'linear',
                    position: 'left',
                    ticks: {
                        callback(value) {
                            return Number(value).toLocaleString();
                        }
                    }
                },

                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    min: 0
                }

            }

        }

    });

    // Render snapshots table
    const snapshotsBody = document.getElementById('trendSnapshotsBody');
    snapshotsBody.innerHTML = '';
    if (trendData.snapshots && trendData.snapshots.length > 0) {
        trendData.snapshots.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left;">${row.month}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: right;">${row.opening_cr}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left;">
                    <span class="modal-client-badge ${getRatingBadgeClass(row.opening_rating)}" style="font-size: 10px; padding: 2px 6px;">${row.opening_rating}</span>
                </td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: right;">${row.closing_cr}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left;">
                    <span class="modal-client-badge ${getRatingBadgeClass(row.latest_rating)}" style="font-size: 10px; padding: 2px 6px;">${row.latest_rating}</span>
                </td>
            `;
            snapshotsBody.appendChild(tr);
        });
    } else {
        snapshotsBody.innerHTML = `<tr><td colspan="5" style="padding: 12px; text-align: center; color: var(--muted); font-size: 12px;">No snapshot history found.</td></tr>`;
    }

    // Render client change logs table
    const logsBody = document.getElementById('trendLogsBody');
    logsBody.innerHTML = '';
    if (trendData.logs && trendData.logs.length > 0) {
        trendData.logs.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left; white-space: nowrap;">${row.date}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left; font-weight: 700;">${row.field}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left; color: var(--muted);">${row.old}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left; font-weight: 700; color: var(--ink);">${row.new}</td>
                <td style="padding: 8px; font-size: 12px; border-bottom: 1px solid #edf2f7; text-align: left; white-space: nowrap;">${row.user}</td>
            `;
            logsBody.appendChild(tr);
        });
    } else {
        logsBody.innerHTML = `<tr><td colspan="5" style="padding: 12px; text-align: center; color: var(--muted); font-size: 12px;">No change logs found for this client.</td></tr>`;
    }

    // Populate history statistics cards
    const statStatus = document.getElementById('statClientStatus');
    if (statStatus) {
        const statusVal = trendData.client_status || 'N/A';
        statStatus.innerText = statusVal;
        statStatus.className = 'modal-client-badge ' + getRatingBadgeClass(statusVal);
    }

    const statDiscont = document.getElementById('statDiscontinuationDate');
    if (statDiscont) {
        statDiscont.innerText = trendData.service_discontinuation_date || 'N/A';
    }

    const statOpening = document.getElementById('statOpeningRating');
    if (statOpening) {
        const openingVal = trendData.opening_rating_category || 'N/A';
        statOpening.innerText = openingVal;
        statOpening.className = 'modal-client-badge ' + getRatingBadgeClass(openingVal);
    }

    const statLatest = document.getElementById('statLatestRating');
    if (statLatest) {
        const latestVal = trendData.latest_rating_category || 'N/A';
        statLatest.innerText = latestVal;
        statLatest.className = 'modal-client-badge ' + getRatingBadgeClass(latestVal);
    }
}
function getRatingBadgeClass(rating) {
    if (!rating) return 'status-badge-other';
    const rLower = rating.toLowerCase();
    if (rLower.includes('most risky') || rLower.includes('critical') || rLower.includes('severe') || rLower.includes('barred') || rLower.includes('discontinued')) {
        return 'status-badge-discontinued'; // red
    } else if (rLower.includes('high') || rLower.includes('risky') || rLower.includes('watchlist') || rLower.includes('suspended') || rLower.includes('proposed')) {
        return 'status-badge-watchlist'; // orange
    } else if (rLower.includes('good') || rLower.includes('best') || rLower.includes('active') || rLower.includes('low')) {
        return 'status-badge-active'; // green
    } else if (rLower.includes('moderate')) {
        return 'status-badge-other'; // grey
    }
    return 'status-badge-other';
}
    function closeTrend() {

        document.getElementById('trendModal').style.display = 'none';

    }

    function closeClientDetails() {
        document.getElementById('clientDetailsModal').style.display = 'none';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatRemarks(str) {
        if (!str) return 'No remarks provided.';
        return escapeHtml(str).replace(/\n/g, '<br>');
    }

    let activeClientDetail = null;
    let displayingPreviousMonth = false;

    function renderModalBody(monthData) {
        return `
            <div class="details-grid">
                <!-- Section 1: Financial Summary -->
                <div class="details-section">
                    <h3 id="financialMetricsTitle">Financial Metrics (${monthData.actual_month})</h3>
                    <div class="details-row">
                        <span class="details-label">Opening CR:</span>
                        <span class="details-value" id="m-openingCr">${monthData.opening_cr}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Closing CR:</span>
                        <span class="details-value" id="m-closingCr">${monthData.closing_cr}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Billed MRC:</span>
                        <span class="details-value" id="m-billedMrc">${monthData.mrc}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Collection:</span>
                        <span class="details-value" id="m-collection">${monthData.collection}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Net Backlog:</span>
                        <span class="details-value" id="m-netBacklog">${monthData.backlog}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Opening OS:</span>
                        <span class="details-value" id="m-openingOs">${monthData.opening_os}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Closing OS:</span>
                        <span class="details-value" id="m-closingOs">${monthData.closing_os}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Payment Plan:</span>
                        <span class="details-value" id="m-paymentPlan">${monthData.payment_plan_amount}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Shortfall:</span>
                        <span class="details-value" id="m-shortfall">${monthData.shortfall}</span>
                    </div>
                    <div class="details-row" style="flex-direction: column; align-items: flex-start; border-bottom: none; margin-top: 10px; background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid var(--line);">
                        <span class="details-label" id="remarksLabel" style="margin-bottom: 4px; color: var(--primary-dark);">${monthData.actual_month} Remarks:</span>
                        <span class="details-value" id="m-remarksValue" style="text-align: left; font-weight: normal; color: var(--ink); line-height: 1.4; word-break: break-word; width: 100%;">${formatRemarks(monthData.remarks)}</span>
                    </div>
                </div>

                <!-- Section 2: Account & KAM Assignment -->
                <div class="details-section">
                    <h3>Account Details</h3>
                    <div class="details-row">
                        <span class="details-label">OPUS ID:</span>
                        <span class="details-value">${monthData.opus_id || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Agreement Status:</span>
                        <span class="details-value">${monthData.agreement_status || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Service Type:</span>
                        <span class="details-value">${monthData.service_type_billing || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Team:</span>
                        <span class="details-value">${monthData.team_name || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Collection KAM:</span>
                        <span class="details-value">${monthData.collection_kam || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Collection Supervisor:</span>
                        <span class="details-value">${monthData.collection_supervisor || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">SM KAM:</span>
                        <span class="details-value">${monthData.sm_kam || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">NTTN Billing KAM:</span>
                        <span class="details-value">${monthData.nttn_billing_kam || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">IIG/ITC Billing KAM:</span>
                        <span class="details-value">${monthData.iig_itc_billing_kam || 'N/A'}</span>
                    </div>
                </div>

                <!-- Section 3: Compliance & Legal -->
                <div class="details-section">
                    <h3>Compliance & Dates</h3>
                    <div class="details-row">
                        <span class="details-label">Barring Priority:</span>
                        <span class="details-value">${monthData.barring_priority || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Legal Status:</span>
                        <span class="details-value">${monthData.legal}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Security Coverage:</span>
                        <span class="details-value">${monthData.security_coverage || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">BTRC Letter:</span>
                        <span class="details-value">${monthData.btrc_letter || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">License Billing:</span>
                        <span class="details-value">${monthData.license_billing || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Other Upstream:</span>
                        <span class="details-value">${monthData.other_upstream}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">BTRC Discont./capping:</span>
                        <span class="details-value">${monthData.btrc_license_discontinuation_date || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">NTTN Commenced:</span>
                        <span class="details-value">${monthData.nttn_billing_commencement_date || 'N/A'}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">IIG/ITC Commenced:</span>
                        <span class="details-value">${monthData.iig_itc_billing_commencement_date || 'N/A'}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function updateStatusBadge(status) {
        const statusBadge = document.getElementById('m-clientStatus');
        if (!statusBadge) return;
        
        statusBadge.innerText = 'Status: ' + (status || 'Unknown');
        statusBadge.className = 'modal-client-badge';
        
        const statusLower = (status || '').toLowerCase();
        if (statusLower.includes('active')) {
            statusBadge.classList.add('status-badge-active');
        } else if (statusLower.includes('discontinued') || statusLower.includes('barred')) {
            statusBadge.classList.add('status-badge-discontinued');
        } else if (statusLower.includes('watchlist') || statusLower.includes('suspended') || statusLower.includes('proposed')) {
            statusBadge.classList.add('status-badge-watchlist');
        } else {
            statusBadge.classList.add('status-badge-other');
        }
    }

    document.querySelectorAll('.client-detail-link').forEach(link => {
        link.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.summary);
            
            activeClientDetail = data;
            displayingPreviousMonth = false;
            
            document.getElementById('m-clientName').innerText = data.client_name || 'N/A';
            updateStatusBadge(data.current.client_status);
            
            // Set data for history button
            const historyBtn = document.getElementById('modalHistoryBtn');
            if (historyBtn) {
                historyBtn.dataset.clientId = data.client_id || '';
                historyBtn.dataset.clientName = data.client_name || 'N/A';
            }
            
            // Set toggle button visibility & state
            const toggleBtn = document.getElementById('modalToggleMonthBtn');
            if (toggleBtn) {
                if (data.previous) {
                    toggleBtn.style.display = 'inline-block';
                    toggleBtn.innerText = 'Last Month Data';
                } else {
                    toggleBtn.style.display = 'none';
                }
            }
            
            const body = document.getElementById('clientDetailsModalBody');
            body.innerHTML = renderModalBody(data.current);

            document.getElementById('clientDetailsModal').style.display = 'block';
        });
    });

    // History button event listener to launch Trend view
    const modalHistoryBtn = document.getElementById('modalHistoryBtn');
    if (modalHistoryBtn) {
        modalHistoryBtn.addEventListener('click', function() {
            const clientId = this.dataset.clientId;
            const clientName = this.dataset.clientName;
            if (!clientId) return;

            closeClientDetails();

            activeClientId = clientId;
            document.getElementById('clientName').innerText = clientName;
            document.getElementById('trendModal').style.display = 'block';
            loadTrend();
        });
    }

    // Toggle button event listener to switch between current and last month data
    const modalToggleMonthBtn = document.getElementById('modalToggleMonthBtn');
    if (modalToggleMonthBtn) {
        modalToggleMonthBtn.addEventListener('click', function() {
            if (!activeClientDetail) return;
            
            displayingPreviousMonth = !displayingPreviousMonth;
            const body = document.getElementById('clientDetailsModalBody');
            
            if (displayingPreviousMonth) {
                body.innerHTML = renderModalBody(activeClientDetail.previous);
                this.innerText = 'Current Month Data';
                updateStatusBadge(activeClientDetail.previous.client_status);
            } else {
                body.innerHTML = renderModalBody(activeClientDetail.current);
                this.innerText = 'Last Month Data';
                updateStatusBadge(activeClientDetail.current.client_status);
            }
        });
    }

    window.addEventListener('click', (e) => {
        const detailsModal = document.getElementById('clientDetailsModal');
        const trendModal = document.getElementById('trendModal');
        if (e.target === detailsModal) {
            detailsModal.style.display = 'none';
        }
        if (e.target === trendModal) {
            trendModal.style.display = 'none';
        }
    });

    // Highlight and scroll to target client if client_id is passed in URL
    const urlParams = new URLSearchParams(window.location.search);
    const highlightClientId = urlParams.get('client_id');
    if (highlightClientId) {
        setTimeout(() => {
            const row = document.getElementById('client-row-' + highlightClientId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('row-highlighted');
            }
        }, 300);
    }

</script>
@endpush