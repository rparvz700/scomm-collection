@extends('layouts.app')

@section('title', 'Clients | SCOMM Collection')

@push('styles')
    <style>
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-input {
            min-width: 250px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 0 12px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        .client-table-wrap {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: var(--panel);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .client-table {
            width: 100%;
            border-collapse: collapse;
        }

        .client-table th, 
        .client-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #edf1f7;
            text-align: left;
            font-size: 13px;
        }

        .client-table th {
            background: #f8fafc;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .client-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-discontinued {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-other {
            background: #f1f5f9;
            color: #475569;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-link {
            font-weight: 700;
            text-decoration: none;
            color: var(--primary);
            font-size: 13px;
        }

        .action-link:hover {
            color: var(--accent);
        }

        .action-btn-danger {
            background: none;
            border: none;
            color: var(--danger);
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            font-size: 13px;
            font-family: inherit;
        }

        .action-btn-danger:hover {
            opacity: 0.8;
        }

        /* Pagination overrides */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 20px 0 0;
        }

        .pagination li a, 
        .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border: 1px solid var(--line);
            border-radius: 6px;
            text-decoration: none;
            color: var(--ink);
            font-weight: 700;
            background: #ffffff;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .pagination li a:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f8fafc;
        }

        .pagination li.active span {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .pagination li.disabled span {
            color: var(--muted);
            background: #f1f5f9;
            cursor: not-allowed;
        }

        /* Discontinue Modal custom style */
        .discontinue-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,.55);
            align-items: center;
            justify-content: center;
        }

        .discontinue-modal-content {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 440px;
            box-shadow: var(--shadow);
            position: relative;
        }

        .discontinue-modal h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 18px;
            color: var(--danger);
        }

        /* Sortable table headers */
        .client-table th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 28px;
            transition: background-color 0.2s ease;
        }

        .client-table th.sortable:hover {
            background: #f1f5f9;
            color: var(--ink);
        }

        .client-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.35;
            font-size: 10px;
        }

        .client-table th.sortable.sort-asc::after {
            content: '▲';
            opacity: 1;
            color: var(--primary);
        }

        .client-table th.sortable.sort-desc::after {
            content: '▼';
            opacity: 1;
            color: var(--primary);
        }
    </style>
@endpush

@section('content')
    <section class="page-heading">
        <div>
            <h1>Clients</h1>
            <p>Manage customer master profiles, assignments, and service operational status.</p>
        </div>
        @can('create clients')
            <a href="{{ route('clients.create') }}" class="button primary">Add client</a>
        @endcan
    </section>

    <section class="summary-cards" style="display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
        <div class="summary-card" style="flex: 1; min-width: 140px; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow);">
            <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Total Clients</div>
            <div id="sumTotalCount" style="font-size: 22px; font-weight: 800; color: var(--ink); margin-top: 4px;">0</div>
        </div>
        <div class="summary-card" style="flex: 1; min-width: 140px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow);">
            <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Active</div>
            <div id="sumActiveCount" style="font-size: 22px; font-weight: 800; color: #166534; margin-top: 4px;">0</div>
        </div>
        <div class="summary-card" style="flex: 1; min-width: 140px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow);">
            <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px;">Discontinued</div>
            <div id="sumDiscontinuedCount" style="font-size: 22px; font-weight: 800; color: #991b1b; margin-top: 4px;">0</div>
        </div>
        <div class="summary-card" style="flex: 1; min-width: 140px; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow);">
            <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">Barred</div>
            <div id="sumBarredCount" style="font-size: 22px; font-weight: 800; color: #b91c1c; margin-top: 4px;">0</div>
        </div>
        <div class="summary-card" style="flex: 1; min-width: 140px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow);">
            <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px;">High Risk</div>
            <div id="sumHighRiskCount" style="font-size: 22px; font-weight: 800; color: #9a3412; margin-top: 4px;">0</div>
        </div>
    </section>

    <section class="filter-bar">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 13px; font-weight: 700; color: var(--muted);">Show</span>
            <select id="pageSizeSelect" style="height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                <option value="10">10</option>
                <option value="15" selected>15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span style="font-size: 13px; font-weight: 700; color: var(--muted);">entries</span>
        </div>
        
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 13px; font-weight: 700; color: var(--muted);">Status:</span>
                <select id="statusFilter" style="height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Discontinued">Discontinued</option>
                    <option value="Barred">Barred</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 13px; font-weight: 700; color: var(--muted);">Risk:</span>
                @php
                    $crRanges = config('risk.ranges') ?? [];
                    $riskCategories = collect($crRanges)->pluck('category')->unique()->filter()->values();
                @endphp
                <select id="riskFilter" style="height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Risk Levels</option>
                    <option value="High Risk">High Risk (Risky/High/Most)</option>
                    @foreach($riskCategories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="filter-form">
            <input class="filter-input" id="clientSearch" type="text" placeholder="Search across all columns in real-time..." style="width: 300px;">
            <button class="button" id="clearSearch" type="button" style="display: none;">Clear</button>
        </div>
    </section>

    <div class="client-table-wrap">
        <table class="client-table" id="clientTable">
            <thead>
                <tr>
                    <th class="sortable sort-asc" data-sort="client_name">Client Name</th>
                    <th class="sortable" data-sort="opus_id">OPUS ID</th>
                    <th class="sortable" data-sort="client_status">Status</th>
                    <th class="sortable" data-sort="license_billing">License Billing</th>
                    <th class="sortable" data-sort="team_name">Team Name</th>
                    <th class="sortable" data-sort="collection_kam">Collection KAM</th>
                    <th class="sortable" data-sort="current_month_cr">Current Month CR</th>
                    <th class="sortable" data-sort="risk_segment">Risk Segment</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="clientTableBody">
                <!-- Will be dynamically populated via Javascript -->
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 14px; padding: 0 4px;">
        <div id="tableInfo" style="font-size: 13px; color: var(--muted); font-weight: 700;">
            Showing 0 to 0 of 0 entries
        </div>
        <div id="paginationContainer"></div>
    </div>

    {{-- DISCONTINUE CONFIRMATION MODAL --}}
    <div id="discontinueModal" class="discontinue-modal">
        <form id="discontinueForm" class="discontinue-modal-content" method="POST" action="">
            @csrf
            <h2>Discontinue Client</h2>
            <p id="discontinueClientText" style="font-size: 14px; margin-bottom: 18px; line-height: 1.5; color: var(--ink);">
                Are you sure you want to mark this client as Discontinued?
            </p>
            
            <div class="field">
                <label for="service_discontinuation_date">Service Discontinuation Date</label>
                <input id="service_discontinuation_date" name="service_discontinuation_date" type="date" value="{{ date('Y-m-d') }}" required>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button class="button" type="button" onclick="closeDiscontinueModal()">Cancel</button>
                <button class="button primary" style="background-color: var(--danger); border-color: var(--danger);" type="submit">Discontinue</button>
            </div>
        </form>
    </div>

    {{-- CLIENT LOGS MODAL --}}
    <div id="clientLogsModal" class="discontinue-modal" style="z-index: 10001;">
        <div class="discontinue-modal-content" style="max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <span style="float: right; font-size: 24px; cursor: pointer; font-weight: bold; line-height: 1; color: var(--muted); margin-top: -10px;" onclick="closeLogsModal()">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 15px; font-size: 20px; color: var(--primary-dark); border-bottom: 2px solid var(--line); padding-bottom: 8px;">
                Client Audit Logs: <span id="logModalClientName" style="color: var(--ink);">Client Name</span>
            </h2>
            
            <div style="overflow-x: auto; margin-top: 15px; border: 1px solid var(--line); border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--line);">
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Date</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Field</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Old Value</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">New Value</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Updated By</th>
                        </tr>
                    </thead>
                    <tbody id="clientLogsTableBody">
                        <!-- Filled dynamically via JS -->
                    </tbody>
                </table>
            </div>
            
            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="button primary" type="button" onclick="closeLogsModal()">Close</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const discontinueRouteTemplate = "{{ route('clients.discontinue', ':id') }}";
        const logsRouteTemplate = "{{ route('clients.logs', ':id') }}";

        function openDiscontinueModal(clientId, clientName) {
            const form = document.getElementById('discontinueForm');
            form.action = discontinueRouteTemplate.replace('%3Aid', clientId).replace(':id', clientId);
            
            document.getElementById('discontinueClientText').innerText = 
                `Are you sure you want to mark "${clientName}" as Discontinued? This will freeze their active subscription.`;
            
            const modal = document.getElementById('discontinueModal');
            modal.style.display = 'flex';
        }

        function closeDiscontinueModal() {
            document.getElementById('discontinueModal').style.display = 'none';
        }

        async function openLogsModal(clientId, clientName) {
            document.getElementById('logModalClientName').innerText = clientName;
            const tableBody = document.getElementById('clientLogsTableBody');
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">Loading logs...</td></tr>`;
            
            const modal = document.getElementById('clientLogsModal');
            modal.style.display = 'flex';
            
            try {
                const logsUrl = logsRouteTemplate.replace('%3Aid', clientId).replace(':id', clientId);
                const response = await fetch(logsUrl);
                const logs = await response.json();
                
                tableBody.innerHTML = '';
                if (logs && logs.length > 0) {
                    logs.forEach(log => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #edf2f7';
                        
                        tr.innerHTML = `
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--ink); white-space: nowrap;">${escapeHtml(log.date)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; font-weight: 700; color: var(--ink);">${escapeHtml(log.field)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--muted);">${escapeHtml(log.old)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; font-weight: 700; color: var(--primary-dark);">${escapeHtml(log.new)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--ink); white-space: nowrap;">${escapeHtml(log.user)}</td>
                        `;
                        tableBody.appendChild(tr);
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">No logs found for this client.</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--danger);">Failed to load logs. Please try again.</td></tr>`;
            }
        }

        function closeLogsModal() {
            document.getElementById('clientLogsModal').style.display = 'none';
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Close when clicking background
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('discontinueModal');
            if (e.target === modal) {
                closeDiscontinueModal();
            }
            const logsModal = document.getElementById('clientLogsModal');
            if (e.target === logsModal) {
                closeLogsModal();
            }
        });

        // Client-side search, sort, and pagination logic
        $(document).ready(function() {
            // Data passed from Blade
            const clients = @json($clients);
            const canUpdateClients = @json(auth()->user()->can('update clients'));
            const editRouteTemplate = "{{ route('clients.edit', ':id') }}";
            const drilldownBaseUrl = "{{ route('dashboard.clients.index') }}";
            
            let filteredClients = [...clients];
            let currentPage = 1;
            let pageSize = parseInt($('#pageSizeSelect').val()) || 15;
            let sortField = 'client_name';
            let sortDirection = 'asc';

            const searchInput = document.getElementById('clientSearch');
            const clearBtn = document.getElementById('clearSearch');
            const clientTableBody = document.getElementById('clientTableBody');
            const tableInfo = document.getElementById('tableInfo');
            const paginationContainer = document.getElementById('paginationContainer');

            let lastRenderedStatus = null;

            function updateTableHeader(status) {
                if (lastRenderedStatus === status) return;
                lastRenderedStatus = status;

                const thead = document.querySelector('#clientTable thead');
                let headerHtml = '';

                if (status === 'Barred') {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'barring_percentage' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="barring_percentage">Barred %</th>
                            <th class="sortable ${sortField === 'barred_at' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="barred_at">Barring Aging</th>
                            <th class="sortable ${sortField === 'collection_kam' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="collection_kam">Collection KAM</th>
                            <th class="sortable ${sortField === 'current_month_cr' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="current_month_cr">Current Month CR</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                } else if (status === 'Discontinued') {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'legal' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="legal">Legal Action</th>
                            <th class="sortable ${sortField === 'service_discontinuation_date' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="service_discontinuation_date">Discontinued Date</th>
                            <th class="sortable ${sortField === 'btrc_license_discontinuation_date' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="btrc_license_discontinuation_date">BTRC License Discont.</th>
                            <th class="sortable ${sortField === 'btrc_letter' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="btrc_letter">BTRC Letter</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                } else {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'license_billing' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="license_billing">License Billing</th>
                            <th class="sortable ${sortField === 'team_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="team_name">Team Name</th>
                            <th class="sortable ${sortField === 'collection_kam' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="collection_kam">Collection KAM</th>
                            <th class="sortable ${sortField === 'current_month_cr' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="current_month_cr">Current Month CR</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                }

                thead.innerHTML = headerHtml;

                // Re-bind click listeners for sorting
                thead.querySelectorAll('th.sortable').forEach(th => {
                    th.addEventListener('click', () => {
                        const field = th.dataset.sort;
                        if (sortField === field) {
                            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortField = field;
                            sortDirection = 'asc';
                        }

                        // Reset visual classes
                        thead.querySelectorAll('th.sortable').forEach(h => {
                            h.classList.remove('sort-asc', 'sort-desc');
                        });
                        th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                        render();
                    });
                });
            }

            // Render function
            function render() {
                // 1. Filter
                const query = searchInput.value.toLowerCase().trim();
                const selectedStatus = document.getElementById('statusFilter').value;
                const selectedRisk = document.getElementById('riskFilter').value;

                updateTableHeader(selectedStatus);

                filteredClients = clients.filter(client => {
                    // Check search query
                    if (query) {
                        const matchesSearch = 
                               (client.client_name && client.client_name.toLowerCase().includes(query)) ||
                               (client.opus_id && client.opus_id.toLowerCase().includes(query)) ||
                               (client.client_status && client.client_status.toLowerCase().includes(query)) ||
                               (client.license_billing && client.license_billing.toLowerCase().includes(query)) ||
                               (client.team_name && client.team_name.toLowerCase().includes(query)) ||
                               (client.collection_kam && client.collection_kam.toLowerCase().includes(query)) ||
                               (client.current_month_cr && client.current_month_cr.toLowerCase().includes(query)) ||
                               (client.risk_segment && client.risk_segment.toLowerCase().includes(query));
                        if (!matchesSearch) return false;
                    }

                    // Check status
                    if (selectedStatus && selectedStatus !== 'All') {
                        const statusMatch = client.client_status && client.client_status.toLowerCase() === selectedStatus.toLowerCase();
                        if (!statusMatch) return false;
                    }

                    // Check risk
                    if (selectedRisk && selectedRisk !== 'All') {
                        if (selectedRisk === 'High Risk') {
                            const riskMatch = client.risk_segment && ['Risky', 'High Risky', 'Most Risky'].includes(client.risk_segment);
                            if (!riskMatch) return false;
                        } else {
                            const riskMatch = client.risk_segment && client.risk_segment.toLowerCase() === selectedRisk.toLowerCase();
                            if (!riskMatch) return false;
                        }
                    }

                    return true;
                });

                // Calculate summary counts from current filtered list
                let activeCount = 0;
                let discontinuedCount = 0;
                let barredCount = 0;
                let highRiskCount = 0;

                filteredClients.forEach(client => {
                    const status = (client.client_status || '').toLowerCase();
                    if (status === 'active') activeCount++;
                    else if (status === 'discontinued') discontinuedCount++;
                    else if (status === 'barred') barredCount++;

                    if (client.risk_segment && ['Risky', 'High Risky', 'Most Risky'].includes(client.risk_segment)) {
                        highRiskCount++;
                    }
                });

                document.getElementById('sumTotalCount').textContent = filteredClients.length;
                document.getElementById('sumActiveCount').textContent = activeCount;
                document.getElementById('sumDiscontinuedCount').textContent = discontinuedCount;
                document.getElementById('sumBarredCount').textContent = barredCount;
                document.getElementById('sumHighRiskCount').textContent = highRiskCount;

                if (query || (selectedStatus && selectedStatus !== 'All') || (selectedRisk && selectedRisk !== 'All')) {
                    clearBtn.style.display = 'inline-block';
                } else {
                    clearBtn.style.display = 'none';
                }

                // 2. Sort
                filteredClients.sort((a, b) => {
                    let valA = a[sortField];
                    let valB = b[sortField];

                    if (sortField === 'current_month_cr' || sortField === 'barring_percentage') {
                        const numA = valA === 'N/A' || valA === null || valA === undefined ? -999999 : parseFloat(valA);
                        const numB = valB === 'N/A' || valB === null || valB === undefined ? -999999 : parseFloat(valB);
                        if (numA < numB) return sortDirection === 'asc' ? -1 : 1;
                        if (numA > numB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    }

                    if (sortField === 'legal') {
                        const boolA = valA ? 1 : 0;
                        const boolB = valB ? 1 : 0;
                        if (boolA < boolB) return sortDirection === 'asc' ? -1 : 1;
                        if (boolA > boolB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    }

                    valA = valA || '';
                    valB = valB || '';

                    // Convert to lowercase strings for case-insensitive sorting if they are strings
                    if (typeof valA === 'string') valA = valA.toLowerCase();
                    if (typeof valB === 'string') valB = valB.toLowerCase();

                    if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                    if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });

                // 3. Paginate
                const totalEntries = filteredClients.length;
                const totalPages = Math.ceil(totalEntries / pageSize) || 1;
                
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const startIndex = (currentPage - 1) * pageSize;
                const endIndex = Math.min(startIndex + pageSize, totalEntries);

                // Render Table Rows
                clientTableBody.innerHTML = '';
                
                if (totalEntries === 0) {
                    clientTableBody.innerHTML = `
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--muted); padding: 30px;">
                                No clients match your search criteria.
                            </td>
                        </tr>
                    `;
                    tableInfo.textContent = 'Showing 0 to 0 of 0 entries';
                    paginationContainer.innerHTML = '';
                    return;
                }

                const displayed = filteredClients.slice(startIndex, endIndex);
                displayed.forEach(client => {
                    const tr = document.createElement('tr');
                    tr.className = 'client-row';
                    tr.style.opacity = '0';
                    tr.style.transition = 'opacity 0.2s ease-in-out';

                    // Determine status class
                    const statusLower = (client.client_status || '').toLowerCase();
                    let statusClass = 'status-other';
                    if (statusLower.includes('active')) {
                        statusClass = 'status-active';
                    } else if (statusLower.includes('discontinued') || statusLower.includes('barred')) {
                        statusClass = 'status-discontinued';
                    }

                    // Build actions HTML
                    let actionsHtml = '';
                    const escName = (client.client_name || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
                    if (canUpdateClients) {
                        const editUrl = editRouteTemplate.replace('%3Aid', client.client_id).replace(':id', client.client_id);
                        actionsHtml = `
                            <div class="actions-cell">
                                <a class="action-link" href="${editUrl}">Edit</a>
                                <button class="action-link" style="background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit;" type="button" onclick="openLogsModal('${client.client_id}', '${escName}')">
                                    Logs
                                </button>
                            </div>
                        `;
                    } else {
                        actionsHtml = `
                            <div class="actions-cell">
                                <button class="action-link" style="background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit;" type="button" onclick="openLogsModal('${client.client_id}', '${escName}')">
                                    Logs
                                </button>
                            </div>
                        `;
                    }

                    // Build drilldown URL
                    let drilldownLink = '';
                    if (client.summary_month) {
                        drilldownLink = `${drilldownBaseUrl}?segment=${encodeURIComponent(client.segment_name)}&range=${encodeURIComponent(client.cr_range)}&month=${encodeURIComponent(client.summary_month)}&client_id=${client.client_id}`;
                    } else {
                        drilldownLink = `${drilldownBaseUrl}?segment=${encodeURIComponent(client.segment_name)}&client_id=${client.client_id}`;
                    }

                    if (selectedStatus === 'Barred') {
                        const barPercent = client.barring_percentage !== null && client.barring_percentage !== undefined ? parseFloat(client.barring_percentage).toFixed(2) + '%' : '0.00%';
                        let agingStr = 'N/A';
                        let agingStyle = '';
                        if (client.barred_at) {
                            const barredDate = new Date(client.barred_at);
                            const today = new Date();
                            const diffTime = Math.abs(today - barredDate);
                            const diffDays = diffTime / (1000 * 60 * 60 * 24);
                            
                            const agingMonthsNum = parseFloat((diffDays / 30.4375).toFixed(1));
                            agingStr = agingMonthsNum.toFixed(1) + 'm';

                            if (agingMonthsNum >= 3.0) {
                                agingStyle = 'color: #ef4444; font-weight: 800; background: #fee2e2; padding: 4px 10px; border-radius: 6px; border: 1px solid #fca5a5;';
                            } else if (agingMonthsNum >= 2.0) {
                                agingStyle = 'color: #f97316; font-weight: 800; background: #fff7ed; padding: 4px 10px; border-radius: 6px; border: 1px solid #ffedd5;';
                            } else {
                                agingStyle = 'color: #1e293b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px;';
                            }
                        }

                        tr.innerHTML = `
                            <td style="font-weight: 700;">${client.client_name || 'N/A'}</td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    ${client.client_status || 'N/A'}
                                </span>
                            </td>
                            <td style="font-weight: 700; color: var(--primary-dark);">${barPercent}</td>
                            <td>
                                <span style="${agingStyle}">
                                    ${agingStr}
                                </span>
                            </td>
                            <td>${client.collection_kam || 'N/A'}</td>
                            <td>${client.current_month_cr || 'N/A'}</td>
                            <td>
                                <a href="${drilldownLink}" target="_blank" class="action-link" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.risk_segment || 'N/A'}
                                </a>
                            </td>
                            <td>${actionsHtml}</td>
                        `;
                    } else if (selectedStatus === 'Discontinued') {
                        const legalText = client.legal ? '<span style="color: #ef4444; font-weight: 800; background: #fee2e2; padding: 2px 8px; border-radius: 999px; font-size: 11px; text-transform: uppercase;">Yes</span>' : '<span style="color: #64748b; font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 999px; font-size: 11px; text-transform: uppercase;">No</span>';
                        
                        const formatJsDate = (dateStr) => {
                            if (!dateStr) return 'N/A';
                            try {
                                const d = new Date(dateStr);
                                if (isNaN(d.getTime())) return 'N/A';
                                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                            } catch (e) {
                                return 'N/A';
                            }
                        };

                        const discontDate = formatJsDate(client.service_discontinuation_date);
                        const btrcLicenseDate = formatJsDate(client.btrc_license_discontinuation_date);
                        const btrcLetterText = client.btrc_letter || 'N/A';

                        tr.innerHTML = `
                            <td style="font-weight: 700;">${client.client_name || 'N/A'}</td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    ${client.client_status || 'N/A'}
                                </span>
                            </td>
                            <td>${legalText}</td>
                            <td style="font-weight: 700; color: #b91c1c;">${discontDate}</td>
                            <td>${btrcLicenseDate}</td>
                            <td>${btrcLetterText}</td>
                            <td>
                                <a href="${drilldownLink}" target="_blank" class="action-link" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.risk_segment || 'N/A'}
                                </a>
                            </td>
                            <td>${actionsHtml}</td>
                        `;
                    } else {
                        tr.innerHTML = `
                            <td style="font-weight: 700;">${client.client_name || 'N/A'}</td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    ${client.client_status || 'N/A'}
                                </span>
                            </td>
                            <td>${client.license_billing || 'N/A'}</td>
                            <td>${client.team_name || 'N/A'}</td>
                            <td>${client.collection_kam || 'N/A'}</td>
                            <td>${client.current_month_cr || 'N/A'}</td>
                            <td>
                                <a href="${drilldownLink}" target="_blank" class="action-link" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.risk_segment || 'N/A'}
                                </a>
                            </td>
                            <td>${actionsHtml}</td>
                        `;
                    }

                    clientTableBody.appendChild(tr);

                    // Trigger reflow & fade in
                    setTimeout(() => {
                        tr.style.opacity = '1';
                    }, 20);
                });
                // Update Info text
                const showStart = totalEntries === 0 ? 0 : startIndex + 1;
                const showEnd = endIndex;
                let infoString = `Showing ${showStart} to ${showEnd} of ${totalEntries} entries`;
                if (totalEntries < clients.length) {
                    infoString += ` (filtered from ${clients.length} total entries)`;
                }
                tableInfo.textContent = infoString;

                // Render pagination buttons
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                paginationContainer.innerHTML = '';
                if (totalPages <= 1) return;

                const ul = document.createElement('ul');
                ul.className = 'pagination';

                // First Page Button
                const firstLi = document.createElement('li');
                if (currentPage === 1) {
                    firstLi.className = 'disabled';
                    firstLi.innerHTML = `<span>&laquo;&laquo;</span>`;
                } else {
                    const firstA = document.createElement('a');
                    firstA.href = 'javascript:void(0)';
                    firstA.innerHTML = `&laquo;&laquo;`;
                    firstA.addEventListener('click', () => {
                        currentPage = 1;
                        render();
                    });
                    firstLi.appendChild(firstA);
                }
                ul.appendChild(firstLi);

                // Prev Page Button
                const prevLi = document.createElement('li');
                if (currentPage === 1) {
                    prevLi.className = 'disabled';
                    prevLi.innerHTML = `<span>&laquo;</span>`;
                } else {
                    const prevA = document.createElement('a');
                    prevA.href = 'javascript:void(0)';
                    prevA.innerHTML = `&laquo;`;
                    prevA.addEventListener('click', () => {
                        currentPage--;
                        render();
                    });
                    prevLi.appendChild(prevA);
                }
                ul.appendChild(prevLi);

                // Page Numbers
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, currentPage + 2);

                if (startPage > 1) {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = 'javascript:void(0)';
                    a.textContent = '1';
                    a.addEventListener('click', () => {
                        currentPage = 1;
                        render();
                    });
                    li.appendChild(a);
                    ul.appendChild(li);

                    if (startPage > 2) {
                        const dots = document.createElement('li');
                        dots.className = 'disabled';
                        dots.innerHTML = `<span>...</span>`;
                        ul.appendChild(dots);
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    const li = document.createElement('li');
                    if (i === currentPage) {
                        li.className = 'active';
                        li.innerHTML = `<span>${i}</span>`;
                    } else {
                        const a = document.createElement('a');
                        a.href = 'javascript:void(0)';
                        a.textContent = i;
                        a.addEventListener('click', () => {
                            currentPage = i;
                            render();
                        });
                        li.appendChild(a);
                    }
                    ul.appendChild(li);
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        const dots = document.createElement('li');
                        dots.className = 'disabled';
                        dots.innerHTML = `<span>...</span>`;
                        ul.appendChild(dots);
                    }

                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = 'javascript:void(0)';
                    a.textContent = totalPages;
                    a.addEventListener('click', () => {
                        currentPage = totalPages;
                        render();
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                }

                // Next Page Button
                const nextLi = document.createElement('li');
                if (currentPage === totalPages) {
                    nextLi.className = 'disabled';
                    nextLi.innerHTML = `<span>&raquo;</span>`;
                } else {
                    const nextA = document.createElement('a');
                    nextA.href = 'javascript:void(0)';
                    nextA.innerHTML = `&raquo;`;
                    nextA.addEventListener('click', () => {
                        currentPage++;
                        render();
                    });
                    nextLi.appendChild(nextA);
                }
                ul.appendChild(nextLi);

                // Last Page Button
                const lastLi = document.createElement('li');
                if (currentPage === totalPages) {
                    lastLi.className = 'disabled';
                    lastLi.innerHTML = `<span>&raquo;&raquo;</span>`;
                } else {
                    const lastA = document.createElement('a');
                    lastA.href = 'javascript:void(0)';
                    lastA.innerHTML = `&raquo;&raquo;`;
                    lastA.addEventListener('click', () => {
                        currentPage = totalPages;
                        render();
                    });
                    lastLi.appendChild(lastA);
                }
                ul.appendChild(lastLi);

                paginationContainer.appendChild(ul);
            }

            // Setup sorting events
            document.querySelectorAll('#clientTable th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const field = th.dataset.sort;
                    if (sortField === field) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortField = field;
                        sortDirection = 'asc';
                    }

                    // Update class names on headers
                    document.querySelectorAll('#clientTable th.sortable').forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                    render();
                });
            });

            // Parse URL search parameters on load
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            const riskParam = urlParams.get('risk');

            if (statusParam) {
                document.getElementById('statusFilter').value = statusParam;
            }
            if (riskParam) {
                document.getElementById('riskFilter').value = riskParam;
            }

            // Bind change events
            $('#statusFilter, #riskFilter').on('change', () => {
                currentPage = 1;
                render();
            });

            // Input listener
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                render();
            });

            // Clear button listener
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                document.getElementById('statusFilter').value = 'All';
                document.getElementById('riskFilter').value = 'All';
                currentPage = 1;
                render();
            });

            // Page size selector listener
            document.getElementById('pageSizeSelect').addEventListener('change', (e) => {
                pageSize = parseInt(e.target.value) || 15;
                currentPage = 1;
                render();
            });

            // Initial render
            render();
        });
    </script>
@endpush
