@extends('layouts.app')

@section('title', 'Collections | SCOMM Collection')

@section('content')
    <section class="page-heading" style="margin-bottom: 24px;">
        <div>
            <h1>Collections</h1>
            <p>Comprehensive repository of all logged collections with multi-parameter filters, dishonor tracking, and tabular search.</p>
        </div>
    </section>

    {{-- FILTERS FORM CARD --}}
    <div class="panel" style="margin-bottom: 24px; padding: 16px 20px;">
        <form method="GET" action="{{ route('collections.index') }}" style="margin: 0; display: flex; align-items: flex-end; gap: 10px; width: 100%; flex-wrap: wrap;">
            
            {{-- Client Filter --}}
            <div style="flex: 1.5; min-width: 140px;">
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
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Type</label>
                <select name="collection_type" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
                    <option value="">All Types</option>
                    @foreach($collectionTypes as $type)
                        <option value="{{ $type }}" {{ $selectedType === $type ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Mode Filter --}}
            <div style="flex: 1; min-width: 110px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Payment Mode</label>
                <select name="payment_mode" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
                    <option value="">All Modes</option>
                    @foreach($paymentModes as $pm)
                        <option value="{{ $pm }}" {{ ($selectedPaymentMode ?? '') === $pm ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $pm)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cheque Status Filter --}}
            <div style="flex: 1; min-width: 110px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Cheque Status</label>
                <select name="cheque_status" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
                    <option value="">All Statuses</option>
                    @foreach($chequeStatuses as $cs)
                        <option value="{{ $cs }}" {{ ($selectedChequeStatus ?? '') === $cs ? 'selected' : '' }}>
                            {{ ucfirst($cs) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Collection Month Filter --}}
            <div style="flex: 1; min-width: 110px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Month</label>
                <input type="month" name="collection_month" value="{{ $selectedMonth ? \Carbon\Carbon::parse($selectedMonth)->format('Y-m') : '' }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Date From --}}
            <div style="flex: 1; min-width: 110px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date From</label>
                <input type="date" name="date_from" value="{{ $selectedDateFrom }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Date To --}}
            <div style="flex: 1; min-width: 110px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date To</label>
                <input type="date" name="date_to" value="{{ $selectedDateTo }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Text Search --}}
            <div style="flex: 1.8; min-width: 150px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Search</label>
                <input type="text" name="search" value="{{ $searchText }}" placeholder="Bank, cheque, trace, remarks..." style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Actions --}}
            <div style="display: flex; gap: 6px; align-items: flex-end;">
                @if($selectedClientId || $selectedType || ($selectedPaymentMode ?? '') || ($selectedChequeStatus ?? '') || $selectedMonth || $selectedDateFrom || $selectedDateTo || $searchText)
                    <a href="{{ route('collections.index') }}" class="button" title="Clear Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #64748b; border: 1px solid #64748b; color: #ffffff; border-radius: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                @endif
                <button type="submit" class="button primary" title="Apply Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
                
                {{-- Quick Export filtered result --}}
                <a href="{{ route('collection-entry.export', request()->all()) }}" target="_blank" class="button secondary" title="Export Current Filtered Results CSV" style="height: 34px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; font-size: 12px; font-weight: 700; border-radius: 6px; text-decoration: none;">
                    📥 Export
                </a>
            </div>
            
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>

    {{-- TABLE CARD --}}
    <div class="panel" style="margin-bottom: 24px;">
        
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--line);">
            
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h2 style="margin: 0; font-size: 15px; font-weight: 800;">Collections Repository</h2>
                    <span class="pill" style="background: rgba(15, 118, 110, 0.1); color: var(--primary); padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">
                        {{ $collections->total() }} records
                    </span>
                </div>

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
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="text" id="ssrTableSearch" onkeyup="filterTableRows(this.value)" placeholder="Instant filter page..." style="height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; width: 200px;">
            </div>

        </div>

        <div class="panel-body" style="padding: 0; overflow-x: auto; max-height: 600px; position: relative;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1100px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); background: #f8fafc; position: sticky; top: 0; z-index: 100;">
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc;">Date &amp; Time</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc;">Client Name</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc;">Month</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc;">Type</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc;">Payment Instrument</th>
                        <th style="text-align: right; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc; width: 130px;">Amount</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc; width: 180px;">Remarks</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc; width: 110px;">Created By</th>
                        @if(auth()->check())
                            <th style="text-align: center; padding: 12px 16px; color: var(--ink); font-size: 12.5px; font-weight: 800; background: #f8fafc; width: 140px;">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="collectionTableBody">
                    @forelse($collections as $c)
                        @php
                            $user = auth()->user();
                            $canEdit = $user && app(\App\Http\Controllers\CollectionEntryController::class)->canUserEditCollection($user, $c);
                            $canDelete = $user && ($user->hasRole('collection_hod') || $user->hasRole('admin'));
                        @endphp
                        <tr class="collection-row" style="border-bottom: 1px solid var(--line); transition: background 0.1s; {{ $c->cheque_status === 'dishonored' ? 'background: #fff5f5;' : '' }}">
                            <td style="padding: 12px 16px; font-weight: 600; font-size: 12px; color: var(--muted); white-space: nowrap;">
                                {{ $c->collection_datetime ? \Carbon\Carbon::parse($c->collection_datetime)->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td class="client-name-cell" style="padding: 12px 16px; font-weight: 700; color: var(--ink);">
                                {{ $c->client->client_name ?? 'Untraced Collection' }}
                            </td>
                            <td style="padding: 12px 16px; color: var(--ink); font-size: 12.5px; white-space: nowrap;">
                                {{ $c->collection_month ? \Carbon\Carbon::parse($c->collection_month)->format('M Y') : 'N/A' }}
                            </td>
                            <td class="type-cell" style="padding: 12px 16px;">
                                <span class="pill" style="background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 6px;">
                                    {{ ucwords(str_replace('_', ' ', $c->collection_type)) }}
                                </span>
                            </td>
                            <td class="mode-cell" style="padding: 12px 16px; font-size: 12px;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <div>
                                        <span class="pill" style="background: #e2e8f0; color: #334155; font-size: 10.5px; font-weight: 700;">
                                            {{ $c->payment_mode ? strtoupper(str_replace('_', ' ', $c->payment_mode)) : 'N/A' }}
                                        </span>
                                        @if($c->cheque_status === 'dishonored')
                                            <span class="pill" style="background: #fee2e2; color: #991b1b; font-size: 10px; font-weight: 800;">DISHONORED ({{ $c->dishonor_count }})</span>
                                        @elseif($c->cheque_status === 'honored')
                                            <span class="pill" style="background: #dcfce7; color: #166534; font-size: 10px; font-weight: 800;">HONORED</span>
                                        @endif
                                    </div>
                                    @if($c->cheque_no || $c->client_bank || $c->trace_no)
                                        <div style="font-size: 11px; color: var(--muted);" title="Bank: {{ $c->client_bank ?? 'N/A' }} | Dep: {{ $c->deposit_bank ?? 'N/A' }} | Cheque: {{ $c->cheque_no ?? 'N/A' }} | Trace: {{ $c->trace_no ?? 'N/A' }}">
                                            @if($c->cheque_no) <span>Chq #{{ $c->cheque_no }}</span> @endif
                                            @if($c->client_bank) <span>({{ $c->client_bank }})</span> @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 800; color: #0f766e; font-size: 13.5px;">
                                ৳ {{ number_format($c->collection_amount, 2) }}
                            </td>
                            <td class="remarks-cell" style="padding: 12px 16px; color: var(--muted); font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $c->remarks }}">
                                {{ $c->remarks ?? '—' }}
                            </td>
                            <td class="created-by-cell" style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: var(--ink);">
                                {{ $c->created_by ?? 'System' }}
                            </td>
                            @if(auth()->check())
                                <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                                    @if($canEdit)
                                        <button type="button" 
                                            title="Edit Collection"
                                            onclick="openEditCollectionModal({{ json_encode([
                                                'collection_id' => $c->collection_id,
                                                'client_id' => $c->client_id ? $c->client_id : 'untraced',
                                                'collection_datetime' => $c->collection_datetime ? \Carbon\Carbon::parse($c->collection_datetime)->format('Y-m-d\TH:i:s') : '',
                                                'collection_month_val' => $c->collection_month ? \Carbon\Carbon::parse($c->collection_month)->format('Y-m') : '',
                                                'collection_month' => $c->collection_month ? \Carbon\Carbon::parse($c->collection_month)->format('Y-m-d') : '',
                                                'collection_type' => $c->collection_type,
                                                'collection_amount' => (float) $c->collection_amount,
                                                'remarks' => $c->remarks,
                                                'payment_mode' => $c->payment_mode,
                                                'client_bank' => $c->client_bank,
                                                'deposit_bank' => $c->deposit_bank,
                                                'cheque_no' => $c->cheque_no,
                                                'cheque_date' => $c->cheque_date ? $c->cheque_date->format('Y-m-d') : '',
                                                'cheque_expiry_date' => $c->cheque_expiry_date ? $c->cheque_expiry_date->format('Y-m-d') : '',
                                                'cheque_placement_date' => $c->cheque_placement_date ? $c->cheque_placement_date->format('Y-m-d') : '',
                                                'trace_no' => $c->trace_no,
                                                'cheque_status' => $c->cheque_status,
                                                'dishonored_date' => $c->dishonored_date ? $c->dishonored_date->format('Y-m-d') : '',
                                                'dishonor_count' => $c->dishonor_count,
                                                'dishonor_slip' => $c->dishonor_slip,
                                            ]) }})" 
                                            style="width: 32px; height: 32px; border-radius: 6px; background: #0f766e; color: #ffffff; border: none; cursor: pointer; margin-right: 4px; display: inline-flex; align-items: center; justify-content: center;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <button type="button" 
                                            title="Delete Collection"
                                            onclick="confirmDeleteCollection({{ $c->collection_id }}, '{{ $c->client ? addslashes($c->client->client_name) : 'Untraced Collection' }}', '{{ number_format($c->collection_amount, 2) }}')" 
                                            style="width: 32px; height: 32px; border-radius: 6px; background: #be123c; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    @endif
                                    @if(!$canEdit && !$canDelete)
                                        <span style="color: var(--muted); font-size: 11px;">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->check() ? 9 : 8 }}" style="text-align: center; color: var(--muted); padding: 32px;">No collection records found matching search filters.</td>
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

    @if(auth()->check())
    {{-- EDIT COLLECTION MODAL --}}
    <div id="editCollectionModal" class="modal-overlay no-print" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 95%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow);">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02); position: sticky; top: 0; background: var(--panel); z-index: 10;">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--ink);">✏️ Edit Collection Entry</h3>
                <button onclick="closeEditCollectionModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            <form id="editCollectionForm" onsubmit="submitEditCollectionForm(event)" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" id="edit_collection_id">
                
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Client Name</label>
                    <select id="edit_client_id" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                        <option value="untraced">Untraced Collection</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->client_id }}">{{ $c->client_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Collection Datetime</label>
                        <input type="datetime-local" step="1" id="edit_collection_datetime" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Collection Month</label>
                        <input type="month" id="edit_collection_month_select" onchange="updateEditMonthHidden(this.value)" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                        <input type="hidden" id="edit_collection_month">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Collection Type</label>
                        <select id="edit_collection_type" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                            @foreach($collectionTypes as $type)
                                <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Amount (৳)</label>
                        <input type="number" step="0.01" min="0" id="edit_collection_amount" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                </div>

                {{-- Banking & Instrument Tracking Section --}}
                <div style="border: 1px solid var(--line); border-radius: 8px; padding: 12px; background: #f8fafc;">
                    <div style="font-weight: 700; font-size: 12.5px; color: var(--primary-dark); margin-bottom: 10px;">🏦 Banking &amp; Instrument Tracking</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Payment Mode</label>
                            <select id="edit_payment_mode" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                                <option value="">Select Mode</option>
                                @foreach($paymentModes as $pm)
                                    <option value="{{ $pm }}">{{ ucwords(str_replace('_', ' ', $pm)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Trace / Ref No</label>
                            <input type="text" id="edit_trace_no" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Client Bank Name (From Bank)</label>
                            <select id="edit_client_bank" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                                <option value="">Select From Bank</option>
                                @foreach($clientBanks as $cb)
                                    <option value="{{ $cb }}">{{ $cb }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Deposited Bank Name</label>
                            <select id="edit_deposit_bank" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                                <option value="">Select Deposited Bank</option>
                                @foreach($depositBanks as $db)
                                    <option value="{{ $db }}">{{ $db }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Cheque No</label>
                            <input type="text" id="edit_cheque_no" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Cheque Date</label>
                            <input type="date" id="edit_cheque_date" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Cheque Expired Date</label>
                            <input type="date" id="edit_cheque_expiry_date" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Cheque Placement Date</label>
                            <input type="date" id="edit_cheque_placement_date" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                    </div>
                </div>

                {{-- Dishonored Cheque Management Section --}}
                <div style="border: 1px solid var(--line); border-radius: 8px; padding: 12px; background: #fff5f5;">
                    <div style="font-weight: 700; font-size: 12.5px; color: #991b1b; margin-bottom: 10px;">⚠️ Dishonored Cheque Management</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Cheque Status</label>
                            <select id="edit_cheque_status" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                                <option value="">Select Status</option>
                                @foreach($chequeStatuses as $cs)
                                    <option value="{{ $cs }}">{{ ucfirst($cs) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Honored / Dishonored Date</label>
                            <input type="date" id="edit_dishonored_date" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">No. of Dishonor</label>
                            <input type="number" min="0" id="edit_dishonor_count" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 2px;">Dishonor Slip / Ref</label>
                            <input type="text" id="edit_dishonor_slip" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); font-size: 12px;">
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Remarks</label>
                    <textarea id="edit_remarks" rows="2" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" onclick="closeEditCollectionModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--line); background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveEditBtn" style="padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- INTERACTION SCRIPTS --}}
    <script>
        function changePageSize(size) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', size);
            urlParams.set('page', 1);
            window.location.search = urlParams.toString();
        }

        function filterTableRows(query) {
            const cleanedQuery = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.collection-row');

            rows.forEach(row => {
                const clientName = row.querySelector('.client-name-cell').innerText.toLowerCase();
                const type = row.querySelector('.type-cell').innerText.toLowerCase();
                const modeCell = row.querySelector('.mode-cell');
                const modeText = modeCell ? modeCell.innerText.toLowerCase() : '';
                const remarks = row.querySelector('.remarks-cell').innerText.toLowerCase();
                const createdByCell = row.querySelector('.created-by-cell');
                const createdBy = createdByCell ? createdByCell.innerText.toLowerCase() : '';

                if (clientName.includes(cleanedQuery) || 
                    type.includes(cleanedQuery) || 
                    modeText.includes(cleanedQuery) ||
                    remarks.includes(cleanedQuery) || 
                    createdBy.includes(cleanedQuery)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function openEditCollectionModal(data) {
            document.getElementById('edit_collection_id').value = data.collection_id;
            document.getElementById('edit_client_id').value = data.client_id;
            document.getElementById('edit_collection_datetime').value = data.collection_datetime;
            document.getElementById('edit_collection_month_select').value = data.collection_month_val;
            document.getElementById('edit_collection_month').value = data.collection_month;
            document.getElementById('edit_collection_type').value = data.collection_type;
            document.getElementById('edit_collection_amount').value = data.collection_amount;
            document.getElementById('edit_remarks').value = data.remarks || '';

            // Banking & dishonor fields
            document.getElementById('edit_payment_mode').value = data.payment_mode || '';
            document.getElementById('edit_client_bank').value = data.client_bank || '';
            document.getElementById('edit_deposit_bank').value = data.deposit_bank || '';
            document.getElementById('edit_cheque_no').value = data.cheque_no || '';
            document.getElementById('edit_cheque_date').value = data.cheque_date || '';
            document.getElementById('edit_cheque_expiry_date').value = data.cheque_expiry_date || '';
            document.getElementById('edit_cheque_placement_date').value = data.cheque_placement_date || '';
            document.getElementById('edit_trace_no').value = data.trace_no || '';

            document.getElementById('edit_cheque_status').value = data.cheque_status || '';
            document.getElementById('edit_dishonored_date').value = data.dishonored_date || '';
            document.getElementById('edit_dishonor_count').value = data.dishonor_count !== null && data.dishonor_count !== undefined ? data.dishonor_count : 0;
            document.getElementById('edit_dishonor_slip').value = data.dishonor_slip || '';
            
            document.getElementById('editCollectionModal').style.display = 'flex';
        }

        function closeEditCollectionModal() {
            document.getElementById('editCollectionModal').style.display = 'none';
        }

        function updateEditMonthHidden(val) {
            if (val) {
                const parts = val.split('-');
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10);
                const lastDay = new Date(year, month, 0).getDate();
                document.getElementById('edit_collection_month').value = `${val}-${String(lastDay).padStart(2, '0')}`;
            } else {
                document.getElementById('edit_collection_month').value = '';
            }
        }

        async function submitEditCollectionForm(e) {
            e.preventDefault();
            const id = document.getElementById('edit_collection_id').value;
            const saveBtn = document.getElementById('saveEditBtn');
            saveBtn.disabled = true;
            saveBtn.innerText = 'Saving...';

            const payload = {
                _token: '{{ csrf_token() }}',
                client_id: document.getElementById('edit_client_id').value,
                collection_datetime: document.getElementById('edit_collection_datetime').value,
                collection_month: document.getElementById('edit_collection_month').value,
                collection_type: document.getElementById('edit_collection_type').value,
                collection_amount: document.getElementById('edit_collection_amount').value,
                remarks: document.getElementById('edit_remarks').value,
                payment_mode: document.getElementById('edit_payment_mode').value,
                client_bank: document.getElementById('edit_client_bank').value,
                deposit_bank: document.getElementById('edit_deposit_bank').value,
                cheque_no: document.getElementById('edit_cheque_no').value,
                cheque_date: document.getElementById('edit_cheque_date').value,
                cheque_expiry_date: document.getElementById('edit_cheque_expiry_date').value,
                cheque_placement_date: document.getElementById('edit_cheque_placement_date').value,
                trace_no: document.getElementById('edit_trace_no').value,
                cheque_status: document.getElementById('edit_cheque_status').value,
                dishonored_date: document.getElementById('edit_dishonored_date').value,
                dishonor_count: document.getElementById('edit_dishonor_count').value,
                dishonor_slip: document.getElementById('edit_dishonor_slip').value,
            };

            try {
                const res = await fetch(`{{ url('/collections') }}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alert('Collection entry updated successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Error updating collection entry.');
                }
            } catch (err) {
                alert('An unexpected error occurred.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save Changes';
            }
        }

        async function confirmDeleteCollection(id, clientName, amount) {
            if (!confirm(`Are you sure you want to delete the collection entry of ৳${amount} for "${clientName}"?\n\nThis will remove the transaction from the collection table, permanently retain an audit log in collection_logs, and recalculate the monthly summaries.`)) {
                return;
            }

            try {
                const res = await fetch(`{{ url('/collections') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alert('Collection entry deleted successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Error deleting collection entry.');
                }
            } catch (err) {
                alert('An unexpected error occurred.');
            }
        }
    </script>
@endsection
