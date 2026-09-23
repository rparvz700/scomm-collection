@extends('layouts.app')

@section('title', 'Collection Log | SCOMM Collection')

@section('content')
    <section class="page-heading" style="margin-bottom: 24px;">
        <div>
            <h1>Collections Log</h1>
            <p>Comprehensive repository of all logged collections with multi-parameter filters and tabular search.</p>
        </div>
    </section>

    {{-- FILTERS FORM CARD (1 LINE) --}}
    <div class="panel" style="margin-bottom: 24px; padding: 16px 20px;">
        <form method="GET" action="{{ route('collections.index') }}" style="margin: 0; display: flex; align-items: flex-end; gap: 10px; width: 100%; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px;">
            
            {{-- Client Filter --}}
            <div style="flex: 1.5; min-width: 150px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Client Name</label>
                <select name="client_id" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
                    <option value="">All Clients</option>
                    <option value="untraced" {{ $selectedClientId === 'untraced' ? 'selected' : '' }}>Untraced Collection</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->client_id }}" {{ $selectedClientId == $c->client_id ? 'selected' : '' }}>
                            {{ $c->client_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Collection Type Filter --}}
            <div style="flex: 1.2; min-width: 130px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Collection Type</label>
                <select name="collection_type" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
                    <option value="">All Types</option>
                    @foreach($collectionTypes as $type)
                        <option value="{{ $type }}" {{ $selectedType === $type ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Collection Month Filter --}}
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Month</label>
                <input type="month" name="collection_month" value="{{ $selectedMonth ? \Carbon\Carbon::parse($selectedMonth)->format('Y-m') : '' }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Date From --}}
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date From</label>
                <input type="date" name="date_from" value="{{ $selectedDateFrom }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Date To --}}
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date To</label>
                <input type="date" name="date_to" value="{{ $selectedDateTo }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Text Search --}}
            <div style="flex: 2; min-width: 180px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Search Remarks</label>
                <input type="text" name="search" value="{{ $searchText }}" placeholder="Search remarks..." style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Actions --}}
            <div style="display: flex; gap: 6px; min-width: 80px;">
                @if($selectedClientId || $selectedType || $selectedMonth || $selectedDateFrom || $selectedDateTo || $searchText)
                    <a href="{{ route('collections.index') }}" class="button" title="Clear Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #64748b; border: 1px solid #64748b; color: #ffffff; border-radius: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                @endif
                <button type="submit" class="button primary" title="Apply Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
            </div>
            
            {{-- Keep per_page size --}}
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>

    {{-- SERVER SIDE RENDERING TABLE CARD --}}
    <div class="panel" style="margin-bottom: 24px;">
        
        {{-- TABLE SEARCH & PAGE SIZE IN ONE LINE --}}
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--line);">
            
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                {{-- Row count badge --}}
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h2 style="margin: 0; font-size: 15px; font-weight: 800;">Collections Log</h2>
                    <span class="pill" style="background: rgba(15, 118, 110, 0.1); color: var(--primary); padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">
                        {{ $collections->total() }} records
                    </span>
                </div>

                {{-- Page size selector --}}
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--muted);">
                    <span>Show</span>
                    <select id="ssrPageSize" onchange="changePageSize(this.value)" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-weight: bold; cursor: pointer; outline: none; height: 28px;">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span>entries</span>
                </div>
            </div>
            
            {{-- Instant table filter search --}}
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="text" id="ssrTableSearch" onkeyup="filterTableRows(this.value)" placeholder="Search this page..." style="height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; width: 200px;">
            </div>

        </div>

        <div class="panel-body" style="padding: 0; overflow-x: auto; max-height: 600px; position: relative;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); background: #f8fafc; position: sticky; top: 0; z-index: 100;">
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc;">Date &amp; Time</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc;">Client Name</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc;">Collection Month</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc;">Collection Type</th>
                        <th style="text-align: right; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc; width: 150px;">Amount</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc; width: 220px;">Remarks</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; background: #f8fafc; width: 120px;">Updated By</th>
                    </tr>
                </thead>
                <tbody id="collectionTableBody">
                    @forelse($collections as $c)
                        <tr class="collection-row" style="border-bottom: 1px solid var(--line); transition: background 0.1s;">
                            <td style="padding: 12px 16px; font-weight: 600; font-size: 12.5px; color: var(--muted); white-space: nowrap;">
                                {{ $c->collection_datetime ? \Carbon\Carbon::parse($c->collection_datetime)->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td class="client-name-cell" style="padding: 12px 16px; font-weight: 700; color: var(--ink);">
                                {{ $c->client->client_name ?? 'Untraced Collection' }}
                            </td>
                            <td style="padding: 12px 16px; color: var(--ink); font-size: 13px;">
                                {{ $c->collection_month ? \Carbon\Carbon::parse($c->collection_month)->format('F Y') : 'N/A' }}
                            </td>
                            <td class="type-cell" style="padding: 12px 16px;">
                                <span class="pill" style="background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 6px;">
                                    {{ ucwords(str_replace('_', ' ', $c->collection_type)) }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 800; color: #0f766e; font-size: 13.5px;">
                                ৳ {{ number_format($c->collection_amount, 0) }}
                            </td>
                            <td class="remarks-cell" style="padding: 12px 16px; color: var(--muted); font-size: 12.5px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $c->remarks }}">
                                {{ $c->remarks ?? '—' }}
                            </td>
                            <td class="updated-by-cell" style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: var(--ink);">
                                {{ $c->updated_by ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--muted); padding: 32px;">No collection records found matching search filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION LINKS FOOTER --}}
        <div class="panel-footer" style="padding: 16px 20px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(0,0,0,0.01);">
            <div style="font-size: 13px; color: var(--muted); font-weight: 500;">
                Showing {{ $collections->firstItem() ?? 0 }} to {{ $collections->lastItem() ?? 0 }} of {{ $collections->total() }} entries
            </div>
            <div>
                {{ $collections->links() }}
            </div>
        </div>

    </div>

    {{-- INTERACTION SCRIPTS --}}
    <script>
        // Change entries size
        function changePageSize(size) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', size);
            urlParams.set('page', 1); // Reset to page 1
            window.location.search = urlParams.toString();
        }

        // Live client-side row filtering for currently loaded page rows
        function filterTableRows(query) {
            const cleanedQuery = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.collection-row');

            rows.forEach(row => {
                const clientName = row.querySelector('.client-name-cell').innerText.toLowerCase();
                const type = row.querySelector('.type-cell').innerText.toLowerCase();
                const remarks = row.querySelector('.remarks-cell').innerText.toLowerCase();
                const updatedBy = row.querySelector('.updated-by-cell').innerText.toLowerCase();

                if (clientName.includes(cleanedQuery) || 
                    type.includes(cleanedQuery) || 
                    remarks.includes(cleanedQuery) || 
                    updatedBy.includes(cleanedQuery)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
@endsection
