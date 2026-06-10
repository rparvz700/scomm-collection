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
        font-weight: 700;
        background: #edf2f7;
    }

</style>
@endpush

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

                <tr>

                    <td>
                        @if ($c->client)
                            <a href="javascript:void(0)" class="client-detail-link" data-summary="{{ json_encode([
                                'client_name' => $c->client->client_name,
                                'opus_id' => $c->client->opus_id,
                                'client_status' => $c->client->client_status,
                                'barring_priority' => $c->client->barring_priority,
                                'btrc_license_discontinuation_date' => $c->client->btrc_license_discontinuation_date?->format('Y-m-d'),
                                'legal' => $c->client->legal ? 'Yes' : 'No',
                                'service_discontinuation_date' => $c->client->service_discontinuation_date?->format('Y-m-d'),
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
                                'opening_os' => number_format($c->total_opening_os, 2),
                                'closing_cr' => number_format($c->latest_cr, 2),
                                'closing_os' => number_format($c->total_latest_os, 2),
                                'mrc' => number_format($c->total_mrc, 2),
                                'backlog' => number_format($c->net_backlog_total, 2),
                                'collection' => number_format($c->total_collection, 2),
                                'payment_plan_amount' => number_format($c->total_payment_plan, 2),
                                'shortfall' => number_format($c->shortfall_from_payment_plan ?? 0, 2),
                                'current_month_remarks' => $c->current_month_remarks,
                            ]) }}">
                                {{ $c->client->client_name }}
                            </a>
                        @else
                            N/A
                        @endif
                    </td>

                    <td class="amount">
                        {{ number_format($c->opening_cr, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_opening_os, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->latest_cr, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_latest_os, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_mrc, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->net_backlog_total, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_collection, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->total_payment_plan, 2) }}
                    </td>

                    <td class="amount">
                        {{ number_format($c->shortfall_from_payment_plan ?? 0, 2) }}
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

    </div>

</div>

{{-- CLIENT DETAILS MODAL --}}
<div id="clientDetailsModal" class="modal">
    <div class="modal-content large">
        <span class="close" onclick="closeClientDetails()">&times;</span>
        
        <div class="modal-client-header">
            <h2 class="modal-client-title" id="m-clientName">Client Details</h2>
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

    document.querySelectorAll('.client-detail-link').forEach(link => {
        link.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.summary);
            
            document.getElementById('m-clientName').innerText = data.client_name || 'N/A';
            document.getElementById('m-clientStatus').innerText = data.client_status ? `Status: ${data.client_status}` : 'Status: Unknown';
            
            const body = document.getElementById('clientDetailsModalBody');
            
            body.innerHTML = `
                <div class="details-grid">
                    <!-- Section 1: Financial Summary -->
                    <div class="details-section">
                        <h3>Financial Metrics (This Month)</h3>
                        <div class="details-row">
                            <span class="details-label">Opening CR:</span>
                            <span class="details-value">${data.opening_cr}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Closing CR:</span>
                            <span class="details-value">${data.closing_cr}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Billed MRC:</span>
                            <span class="details-value">${data.mrc}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Collection:</span>
                            <span class="details-value">${data.collection}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Net Backlog:</span>
                            <span class="details-value">${data.backlog}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Opening OS:</span>
                            <span class="details-value">${data.opening_os}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Closing OS:</span>
                            <span class="details-value">${data.closing_os}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Payment Plan:</span>
                            <span class="details-value">${data.payment_plan_amount}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Shortfall:</span>
                            <span class="details-value">${data.shortfall}</span>
                        </div>
                        <div class="details-row" style="flex-direction: column; align-items: flex-start; border-bottom: none; margin-top: 10px; background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid var(--line);">
                            <span class="details-label" style="margin-bottom: 4px; color: var(--primary-dark);">Current Month Remarks:</span>
                            <span class="details-value" style="text-align: left; font-weight: normal; color: var(--ink); line-height: 1.4; word-break: break-word; width: 100%;">${formatRemarks(data.current_month_remarks)}</span>
                        </div>
                    </div>

                    <!-- Section 2: Account & KAM Assignment -->
                    <div class="details-section">
                        <h3>Account Details</h3>
                        <div class="details-row">
                            <span class="details-label">OPUS ID:</span>
                            <span class="details-value">${data.opus_id || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Service Type:</span>
                            <span class="details-value">${data.service_type_billing || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Team:</span>
                            <span class="details-value">${data.team_name || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Collection KAM:</span>
                            <span class="details-value">${data.collection_kam || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Collection Supervisor:</span>
                            <span class="details-value">${data.collection_supervisor || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">SM KAM:</span>
                            <span class="details-value">${data.sm_kam || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">NTTN Billing KAM:</span>
                            <span class="details-value">${data.nttn_billing_kam || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">IIG/ITC Billing KAM:</span>
                            <span class="details-value">${data.iig_itc_billing_kam || 'N/A'}</span>
                        </div>
                    </div>

                    <!-- Section 3: Compliance & Legal -->
                    <div class="details-section">
                        <h3>Compliance & Dates</h3>
                        <div class="details-row">
                            <span class="details-label">Barring Priority:</span>
                            <span class="details-value">${data.barring_priority || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Legal Status:</span>
                            <span class="details-value">${data.legal}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Security Coverage:</span>
                            <span class="details-value">${data.security_coverage || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">BTRC Letter:</span>
                            <span class="details-value">${data.btrc_letter || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">License Billing:</span>
                            <span class="details-value">${data.license_billing || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Other Upstream:</span>
                            <span class="details-value">${data.other_upstream}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">BTRC Discontinuation:</span>
                            <span class="details-value">${data.btrc_license_discontinuation_date || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Service Discontinuation:</span>
                            <span class="details-value">${data.service_discontinuation_date || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">NTTN Commenced:</span>
                            <span class="details-value">${data.nttn_billing_commencement_date || 'N/A'}</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label">IIG/ITC Commenced:</span>
                            <span class="details-value">${data.iig_itc_billing_commencement_date || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('clientDetailsModal').style.display = 'block';
        });
    });

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

</script>
@endpush