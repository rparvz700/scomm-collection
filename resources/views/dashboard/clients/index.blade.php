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
                        {{ $c->client->client_name ?? 'N/A' }}
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

</script>

@endpush