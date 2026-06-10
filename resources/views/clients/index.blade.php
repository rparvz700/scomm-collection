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
                    <th class="sortable" data-sort="service_type_billing">Service Type</th>
                    <th class="sortable" data-sort="team_name">Team Name</th>
                    <th class="sortable" data-sort="collection_kam">Collection KAM</th>
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
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function openDiscontinueModal(clientId, clientName) {
            const form = document.getElementById('discontinueForm');
            form.action = `/clients/${clientId}/discontinue`;
            
            document.getElementById('discontinueClientText').innerText = 
                `Are you sure you want to mark "${clientName}" as Discontinued? This will freeze their active subscription.`;
            
            const modal = document.getElementById('discontinueModal');
            modal.style.display = 'flex';
        }

        function closeDiscontinueModal() {
            document.getElementById('discontinueModal').style.display = 'none';
        }

        // Close when clicking background
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('discontinueModal');
            if (e.target === modal) {
                closeDiscontinueModal();
            }
        });

        // Client-side search, sort, and pagination logic
        $(document).ready(function() {
            // Data passed from Blade
            const clients = @json($clients);
            const canUpdateClients = @json(auth()->user()->can('update clients'));
            const editRouteTemplate = "{{ route('clients.edit', ':id') }}";
            
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

            // Render function
            function render() {
                // 1. Filter
                const query = searchInput.value.toLowerCase().trim();
                if (query) {
                    clearBtn.style.display = 'inline-block';
                    filteredClients = clients.filter(client => {
                        return (client.client_name && client.client_name.toLowerCase().includes(query)) ||
                               (client.opus_id && client.opus_id.toLowerCase().includes(query)) ||
                               (client.client_status && client.client_status.toLowerCase().includes(query)) ||
                               (client.service_type_billing && client.service_type_billing.toLowerCase().includes(query)) ||
                               (client.team_name && client.team_name.toLowerCase().includes(query)) ||
                               (client.collection_kam && client.collection_kam.toLowerCase().includes(query));
                    });
                } else {
                    clearBtn.style.display = 'none';
                    filteredClients = [...clients];
                }

                // 2. Sort
                filteredClients.sort((a, b) => {
                    let valA = a[sortField] || '';
                    let valB = b[sortField] || '';

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
                            <td colspan="7" style="text-align: center; color: var(--muted); padding: 30px;">
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
                    if (canUpdateClients) {
                        const editUrl = editRouteTemplate.replace('%3Aid', client.client_id).replace(':id', client.client_id);
                        actionsHtml = `
                            <div class="actions-cell">
                                <a class="action-link" href="${editUrl}">Edit</a>
                        `;
                        if (statusLower !== 'discontinued') {
                            // Safe escape client name
                            const escName = (client.client_name || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
                            actionsHtml += `
                                <button class="action-btn-danger" type="button" onclick="openDiscontinueModal('${client.client_id}', '${escName}')">
                                    Discontinue
                                </button>
                            `;
                        }
                        actionsHtml += `</div>`;
                    } else {
                        actionsHtml = `<span class="muted">No actions</span>`;
                    }

                    tr.innerHTML = `
                        <td style="font-weight: 700;">${client.client_name || 'N/A'}</td>
                        <td>${client.opus_id || 'N/A'}</td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${client.client_status || 'N/A'}
                            </span>
                        </td>
                        <td>${client.service_type_billing || 'N/A'}</td>
                        <td>${client.team_name || 'N/A'}</td>
                        <td>${client.collection_kam || 'N/A'}</td>
                        <td>${actionsHtml}</td>
                    `;
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

            // Input listener
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                render();
            });

            // Clear button listener
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
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
