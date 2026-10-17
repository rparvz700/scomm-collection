@extends('layouts.app')

@section('title', 'Waivers | SCOMM Collection')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 Theme Integration */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid var(--line) !important;
            border-radius: 6px !important;
            background-color: var(--panel) !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--ink) !important;
            line-height: 38px !important;
            padding-left: 12px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 20px !important;
            font-weight: bold;
            color: var(--muted);
        }
        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--line) !important;
            border-radius: 6px !important;
            min-height: 38px !important;
            padding: 3px 6px !important;
            background-color: var(--panel) !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f1f5f9 !important;
            border: 1px solid var(--line) !important;
            border-radius: 4px !important;
            padding: 2px 8px !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            color: var(--ink) !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: var(--danger) !important;
            margin-right: 5px !important;
            font-weight: bold !important;
        }
        .select2-dropdown {
            border: 1px solid var(--line) !important;
            border-radius: 6px !important;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15) !important;
            z-index: 100005 !important;
            font-size: 13px !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--line) !important;
            border-radius: 4px !important;
            padding: 6px 10px !important;
            outline: none !important;
            font-size: 12.5px !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary) !important;
        }

        /* Modern iOS-Style Toggle Switch */
        .switch-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .switch-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .25s ease-in-out;
            border-radius: 24px;
        }
        .switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .25s ease-in-out;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .switch-toggle input:checked + .switch-slider {
            background-color: var(--primary);
        }
        .switch-toggle input:checked + .switch-slider:before {
            transform: translateX(20px);
        }

        /* Spinner */
        .spinner-ring {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(15, 118, 110, 0.2);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        /* Breakdown Table Ultra-Compact Overrides */
        .breakdown-table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .breakdown-table thead tr th {
            padding: 4px 6px !important;
            height: 24px !important;
            min-height: unset !important;
            max-height: 24px !important;
            font-size: 10px !important;
            font-weight: 800 !important;
            color: #475569 !important;
            background: #f1f5f9 !important;
            border-bottom: 1px solid #cbd5e1 !important;
            vertical-align: middle !important;
            line-height: 1.1 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
        }

        .breakdown-table tbody tr {
            height: 24px !important;
            min-height: unset !important;
        }

        .breakdown-table tbody tr td {
            padding: 2px 6px !important;
            height: 24px !important;
            min-height: unset !important;
            font-size: 11px !important;
            vertical-align: middle !important;
            line-height: 1.15 !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .breakdown-table tfoot tr td {
            padding: 4px 6px !important;
            height: 26px !important;
            min-height: unset !important;
            font-size: 11px !important;
            vertical-align: middle !important;
            font-weight: 800 !important;
            background: #f8fafc !important;
            border-top: 2px solid #cbd5e1 !important;
            line-height: 1.1 !important;
        }

        /* Checkbox compact size override */
        .breakdown-table input[type="checkbox"],
        .breakdown-table input[type="checkbox"]:focus {
            width: 13px !important;
            height: 13px !important;
            min-height: 13px !important;
            max-height: 13px !important;
            padding: 0 !important;
            margin: 0 !important;
            vertical-align: middle !important;
            cursor: pointer !important;
            accent-color: var(--primary) !important;
            box-shadow: none !important;
            outline: none !important;
            border-radius: 2px !important;
        }

        /* Percentage input compact size override */
        .breakdown-table input[type="number"],
        .breakdown-table input[type="number"].row-pct-input,
        .breakdown-table input.row-pct-input {
            width: 44px !important;
            height: 20px !important;
            min-height: 20px !important;
            max-height: 20px !important;
            padding: 0 2px !important;
            margin: 0 !important;
            font-size: 10.5px !important;
            font-weight: 700 !important;
            text-align: center !important;
            border: 1px solid var(--line) !important;
            border-radius: 3px !important;
            background: #ffffff !important;
            outline: none !important;
            box-shadow: none !important;
            line-height: 18px !important;
        }

        .breakdown-table input.row-pct-input:focus,
        .breakdown-table input[type="number"]:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 1px var(--primary) !important;
        }

        /* Modality pill ultra-compact override */
        .breakdown-table .pill {
            min-height: 15px !important;
            max-height: 15px !important;
            height: 15px !important;
            padding: 0 4px !important;
            font-size: 9px !important;
            line-height: 13px !important;
            border-radius: 3px !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        /* Bulk input and button overrides */
        .bulk-input-field {
            width: 46px !important;
            height: 22px !important;
            min-height: 22px !important;
            max-height: 22px !important;
            padding: 0 3px !important;
            margin: 0 !important;
            font-size: 10.5px !important;
            font-weight: 700 !important;
            text-align: center !important;
            border: 1px solid var(--line) !important;
            border-radius: 3px !important;
            outline: none !important;
            box-shadow: none !important;
            background: #ffffff !important;
            line-height: 20px !important;
        }

        .bulk-btn {
            height: 22px !important;
            min-height: 22px !important;
            max-height: 22px !important;
            padding: 0 8px !important;
            margin: 0 !important;
            font-size: 10.5px !important;
            font-weight: 700 !important;
            line-height: 20px !important;
            border: 1px solid var(--line) !important;
            border-radius: 3px !important;
            background: #ffffff !important;
            color: var(--ink) !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .bulk-btn:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
        }
    </style>
@endpush

@section('content')
    <section class="page-heading" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1>Waivers</h1>
            <p>Manage client waiver entries, applicable dates, % breakdown on component OS, and rollover deductions.</p>
        </div>
        <button type="button" onclick="openAddWaiverModal()" class="button primary" style="height: 38px; padding: 0 16px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Waiver
        </button>
    </section>

    {{-- FILTERS FORM CARD --}}
    <div class="panel" style="margin-bottom: 24px; padding: 16px 20px;">
        <form method="GET" action="{{ route('waivers.index') }}" style="margin: 0; display: flex; align-items: flex-end; gap: 12px; width: 100%; flex-wrap: wrap;">
            
            {{-- Client Filter with Select2 --}}
            <div style="flex: 2; min-width: 220px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Client Name</label>
                <select name="client_id" id="filter_client_id" class="select2-filter">
                    <option value="">All Clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->client_id }}" {{ $selectedClientId == $c->client_id ? 'selected' : '' }}>
                            {{ $c->client_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date From</label>
                <input type="date" name="date_from" value="{{ $selectedDateFrom }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 38px;">
            </div>

            {{-- Date To --}}
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date To</label>
                <input type="date" name="date_to" value="{{ $selectedDateTo }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 38px;">
            </div>

            {{-- Text Search --}}
            <div style="flex: 2; min-width: 200px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Search Remarks &amp; User</label>
                <input type="text" name="search" value="{{ $searchText }}" placeholder="Search client, description, amount..." style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 38px;">
            </div>

            {{-- Actions --}}
            <div style="display: flex; gap: 6px; min-width: 80px;">
                @if($selectedClientId || $selectedDateFrom || $selectedDateTo || $searchText)
                    <a href="{{ route('waivers.index') }}" class="button" title="Clear Filters" style="height: 38px; width: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #64748b; border: 1px solid #64748b; color: #ffffff; border-radius: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                @endif
                <button type="submit" class="button primary" title="Apply Filters" style="height: 38px; width: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
            </div>
            
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>

    {{-- WAIVERS TABLE CARD --}}
    <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: nowrap; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--line);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="margin: 0; font-size: 15px; font-weight: 800;">Waiver Entries</h2>
                <span class="pill" style="background: rgba(14, 116, 144, 0.1); color: #0891b2; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">
                    {{ $waivers->total() }} records
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);">
                <span>Show</span>
                <select id="filter_per_page" onchange="changePageSize(this.value)" class="select2-perpage">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span>entries</span>
            </div>
        </div>

        <div class="panel-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); background: #f8fafc;">
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800;">Applicable From</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800;">Client Name</th>
                        <th style="text-align: right; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 170px;">Waiver Amount</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 240px;">Description</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 120px;">Attachment</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 130px;">Created By</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waivers as $w)
                        <tr style="border-bottom: 1px solid var(--line); transition: background 0.1s;">
                            <td style="padding: 12px 16px; font-weight: 700; font-size: 12.5px; color: var(--ink); white-space: nowrap;">
                                {{ $w->applicable_from_date ? $w->applicable_from_date->format('d M Y') : 'N/A' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--primary);">
                                {{ $w->client->client_name ?? 'N/A' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 800; color: #0284c7; font-size: 13.5px;">
                                <div>৳ {{ number_format($w->waiver_amount, 2) }}</div>
                                <div style="display: flex; gap: 4px; justify-content: flex-end; flex-wrap: wrap; margin-top: 4px;">
                                    @if($w->subtract_from_next_month_os)
                                        <span class="pill" style="font-size: 9.5px; background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 4px; font-weight: 700;" title="Subtract from Latest OS in next month rollover">Rollover Deduct</span>
                                    @endif
                                    @if(!empty($w->breakdown_config))
                                        <span class="pill" style="font-size: 9.5px; background: #f0fdf4; color: #166534; padding: 1px 6px; border-radius: 4px; font-weight: 700;" title="Component % Breakdown Configured">Breakdown %</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 16px; color: var(--muted); font-size: 12.5px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $w->description }}">
                                {{ $w->description ?? '—' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if($w->attachment_path)
                                    <a href="{{ route('waivers.attachment', $w->id) }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11.5px; font-weight: 700; border-radius: 6px; background: #e0f2fe; color: #0369a1; text-decoration: none;">
                                        📎 View
                                    </a>
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">None</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: 12.5px; font-weight: 600; color: var(--ink);">
                                {{ $w->created_by ?? 'System' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                                <button type="button" 
                                    onclick="openEditWaiverModal({{ json_encode([
                                        'id' => $w->id,
                                        'client_id' => $w->client_id,
                                        'applicable_from_date' => $w->applicable_from_date ? $w->applicable_from_date->format('Y-m-d') : '',
                                        'waiver_amount' => (float) $w->waiver_amount,
                                        'subtract_from_next_month_os' => (bool) $w->subtract_from_next_month_os,
                                        'breakdown_config' => $w->breakdown_config,
                                        'description' => $w->description,
                                        'attachment_name' => $w->attachment_name,
                                    ]) }})" 
                                    style="padding: 4px 10px; font-size: 11.5px; font-weight: 700; border-radius: 6px; background: #0f766e; color: #fff; border: none; cursor: pointer; margin-right: 4px;">
                                    ✏️ Edit
                                </button>
                                <button type="button" 
                                    onclick="confirmDeleteWaiver({{ $w->id }}, '{{ addslashes($w->client->client_name ?? 'Client') }}', '{{ number_format($w->waiver_amount, 2) }}')" 
                                    style="padding: 4px 10px; font-size: 11.5px; font-weight: 700; border-radius: 6px; background: #be123c; color: #fff; border: none; cursor: pointer;">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--muted); padding: 32px;">No waiver records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($waivers->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--line);">
                {{ $waivers->links() }}
            </div>
        @endif
    </div>

    {{-- ADD WAIVER MODAL --}}
    <div id="addWaiverModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 95%; max-width: 860px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: var(--shadow); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02); flex-shrink: 0;">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--ink);">➕ Add Waiver Entry</h3>
                <button type="button" onclick="closeAddWaiverModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            
            <form id="addWaiverForm" action="{{ route('waivers.store') }}" method="POST" enctype="multipart/form-data" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                
                {{-- Client Selector & Applicable Date in a 2-Column Grid --}}
                <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; align-items: flex-start;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Select Client</label>
                        <select name="client_id" id="create_client_id" class="select2-client" required style="width: 100%;">
                            <option value="">-- Choose Client --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->client_id }}">{{ $c->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Applicable From Date</label>
                        <input type="date" id="add_applicable_from_date" name="applicable_from_date" required onchange="onApplicableDateChanged('add')" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; height: 38px;">
                    </div>
                </div>

                {{-- DYNAMIC OS BREAKDOWN & % CALCULATOR CONTAINER --}}
                <div id="add_breakdown_container" style="display: none; background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px;">
                    
                    {{-- Header Row: Title & Month on Left, Ultra-Compact Bulk Tool on Right --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding-bottom: 5px; border-bottom: 1px solid var(--line); flex-wrap: wrap; gap: 6px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span id="add_breakdown_title" style="font-size: 12px; font-weight: 800; color: var(--ink);">OS Breakdown</span>
                            <span id="add_month_badge" style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 1.5px 7px; border-radius: 4px; font-weight: 700;"></span>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
                            <span style="font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase;">Bulk:</span>
                            <input type="number" step="0.1" min="0" max="100" id="add_bulk_pct" placeholder="Fill %" class="bulk-input-field">
                            <button type="button" onclick="applyBulkPct('add')" class="bulk-btn">Apply %</button>
                        </div>
                    </div>

                    {{-- Stats Row: Opening OS and Latest OS EQUALLY DISTRIBUTED HORIZONTALLY --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 5px; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 10.5px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Opening OS</span>
                            <strong id="add_grand_opening" style="font-size: 11.5px; font-weight: 800; color: var(--ink); font-family: monospace, sans-serif;">৳ 0.00</strong>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #bae6fd; border-radius: 5px; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #0284c7; text-transform: uppercase;">Latest OS</span>
                            <strong id="add_grand_latest" style="font-size: 11.5px; font-weight: 800; color: #0284c7; font-family: monospace, sans-serif;">৳ 0.00</strong>
                        </div>
                    </div>

                    {{-- Compact Breakdown Table --}}
                    <div style="max-height: 290px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff;">
                        <table class="breakdown-table">
                            <thead>
                                <tr style="position: sticky; top: 0; z-index: 2;">
                                    <th style="width: 24px; text-align: center;">
                                        <input type="checkbox" id="add_master_chk" checked onchange="toggleAllRowChecks('add', this)" title="Select/Deselect All">
                                    </th>
                                    <th style="width: 68px; text-align: left;">Modality</th>
                                    <th style="width: 90px; text-align: left;">License</th>
                                    <th style="width: 100px; text-align: right;">Opening OS</th>
                                    <th style="width: 105px; text-align: right; color: #0284c7;">Latest OS</th>
                                    <th style="width: 75px; text-align: center;">Waiver %</th>
                                    <th style="width: 110px; text-align: right; color: #166534;">Waiver (৳)</th>
                                </tr>
                            </thead>
                            <tbody id="add_breakdown_tbody">
                                <!-- Populated dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: left; color: var(--ink);">TOTALS</td>
                                    <td style="text-align: right; color: var(--muted);" id="add_selected_opening_os">৳ 0.00</td>
                                    <td style="text-align: right; color: #0284c7;" id="add_selected_latest_os">৳ 0.00</td>
                                    <td style="text-align: center; color: var(--ink);" id="add_effective_pct">0.00%</td>
                                    <td style="text-align: right; color: #166534; font-size: 11.5px;" id="add_total_waiver_calc">৳ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Prominent Non-Editable Total Waiver Amount Display Card Below Table --}}
                    <div style="margin-top: 10px; padding: 10px 14px; background: #ffffff; border: 2px solid #22c55e; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #166534; letter-spacing: 0.5px;">Total Waiver Amount</div>
                            <div style="font-size: 11px; color: var(--muted); margin-top: 1px;">Auto-calculated from license percentage inputs</div>
                        </div>
                        <div style="text-align: right;">
                            <span id="add_total_waiver_display" style="font-size: 19px; font-weight: 800; color: #15803d; font-family: monospace, sans-serif;">৳ 0.00</span>
                            <input type="hidden" name="waiver_amount" id="add_waiver_amount" value="0.00">
                        </div>
                    </div>

                    <input type="hidden" name="breakdown_config" id="add_breakdown_config_json">
                </div>

                {{-- TOGGLE: SUBTRACT FROM LATEST OS IN NEXT MONTH --}}
                <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: var(--ink); margin: 0; cursor: pointer; display: block;" for="add_toggle_subtract_os">
                                Subtract Waiver Amount from Latest OS from Next Month
                            </label>
                            <span style="font-size: 11px; color: var(--muted); display: block; margin-top: 2px;">
                                Subtract this waiver from Latest OS and push the remainder (<code>Latest OS − Waiver Amount</code>) to Backlog in next month's rollover.
                            </span>
                        </div>
                        <div id="add_toggle_wrapper">
                            <label class="switch-toggle">
                                <input type="checkbox" name="subtract_from_next_month_os" id="add_toggle_subtract_os" value="1">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- Informational Rollover Deduction Note --}}
                    <div style="margin-top: 10px; padding: 8px 12px; background: #e0f2fe; border-left: 3px solid #0284c7; border-radius: 4px; font-size: 11.5px; color: #0369a1; line-height: 1.45;">
                        <strong>ℹ️ Rollover Deduction Logic Note:</strong>
                        When this option is enabled, it takes effect <strong>only if the selected applicable date is in the current month or future</strong>. During the month-opening rollover script execution, these exact component waiver amounts will be subtracted from the client's closing <strong>Latest OS</strong>, carrying forward the reduced balance as next month's opening <strong>Backlog</strong>. Past/historical rollover months already finalized will not be modified.
                    </div>

                    {{-- Live Warning Alert for Past Dates --}}
                    <div id="add_rollover_date_alert" style="display: none; margin-top: 8px; padding: 7px 12px; background: #fef2f2; border-left: 3px solid #ef4444; border-radius: 4px; font-size: 11px; color: #991b1b; line-height: 1.4;"></div>
                </div>

                {{-- Attachment & Description --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Attachment File (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" style="width: 100%; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px;">
                    <small style="color: var(--muted); font-size: 11px;">Supported: PDF, PNG, JPG, DOC, XLS (Max 10MB)</small>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Description / Reason</label>
                    <textarea name="description" rows="3" placeholder="Enter details or approval reference for this waiver..." style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; flex-shrink: 0;">
                    <button type="button" onclick="closeAddWaiverModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--line); background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;">Save Waiver</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT WAIVER MODAL --}}
    <div id="editWaiverModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 95%; max-width: 860px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: var(--shadow); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02); flex-shrink: 0;">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--ink);">✏️ Edit Waiver Entry</h3>
                <button type="button" onclick="closeEditWaiverModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            
            <form id="editWaiverForm" method="POST" enctype="multipart/form-data" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_waiver_id">

                {{-- Client Selector & Applicable Date in a 2-Column Grid --}}
                <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; align-items: flex-start;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Select Client</label>
                        <select id="edit_client_id" name="client_id" class="select2-client" required style="width: 100%;">
                            @foreach($clients as $c)
                                <option value="{{ $c->client_id }}">{{ $c->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Applicable From Date</label>
                        <input type="date" id="edit_applicable_from_date" name="applicable_from_date" required onchange="onApplicableDateChanged('edit')" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; height: 38px;">
                    </div>
                </div>

                {{-- DYNAMIC OS BREAKDOWN & % CALCULATOR CONTAINER --}}
                <div id="edit_breakdown_container" style="display: none; background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px;">
                    
                    {{-- Header Row: Title & Month on Left, Ultra-Compact Bulk Tool on Right --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding-bottom: 5px; border-bottom: 1px solid var(--line); flex-wrap: wrap; gap: 6px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span id="edit_breakdown_title" style="font-size: 12px; font-weight: 800; color: var(--ink);">OS Breakdown</span>
                            <span id="edit_month_badge" style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 1.5px 7px; border-radius: 4px; font-weight: 700;"></span>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
                            <span style="font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase;">Bulk:</span>
                            <input type="number" step="0.1" min="0" max="100" id="edit_bulk_pct" placeholder="Fill %" class="bulk-input-field">
                            <button type="button" onclick="applyBulkPct('edit')" class="bulk-btn">Apply %</button>
                        </div>
                    </div>

                    {{-- Stats Row: Opening OS and Latest OS EQUALLY DISTRIBUTED HORIZONTALLY --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 5px; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 10.5px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Opening OS</span>
                            <strong id="edit_grand_opening" style="font-size: 11.5px; font-weight: 800; color: var(--ink); font-family: monospace, sans-serif;">৳ 0.00</strong>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #bae6fd; border-radius: 5px; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #0284c7; text-transform: uppercase;">Latest OS</span>
                            <strong id="edit_grand_latest" style="font-size: 11.5px; font-weight: 800; color: #0284c7; font-family: monospace, sans-serif;">৳ 0.00</strong>
                        </div>
                    </div>

                    {{-- Compact Breakdown Table --}}
                    <div style="max-height: 290px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff;">
                        <table class="breakdown-table">
                            <thead>
                                <tr style="position: sticky; top: 0; z-index: 2;">
                                    <th style="width: 24px; text-align: center;">
                                        <input type="checkbox" id="edit_master_chk" checked onchange="toggleAllRowChecks('edit', this)" title="Select/Deselect All">
                                    </th>
                                    <th style="width: 68px; text-align: left;">Modality</th>
                                    <th style="width: 90px; text-align: left;">License</th>
                                    <th style="width: 100px; text-align: right;">Opening OS</th>
                                    <th style="width: 105px; text-align: right; color: #0284c7;">Latest OS</th>
                                    <th style="width: 75px; text-align: center;">Waiver %</th>
                                    <th style="width: 110px; text-align: right; color: #166534;">Waiver (৳)</th>
                                </tr>
                            </thead>
                            <tbody id="edit_breakdown_tbody">
                                <!-- Populated dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: left; color: var(--ink);">TOTALS</td>
                                    <td style="text-align: right; color: var(--muted);" id="edit_selected_opening_os">৳ 0.00</td>
                                    <td style="text-align: right; color: #0284c7;" id="edit_selected_latest_os">৳ 0.00</td>
                                    <td style="text-align: center; color: var(--ink);" id="edit_effective_pct">0.00%</td>
                                    <td style="text-align: right; color: #166534; font-size: 11.5px;" id="edit_total_waiver_calc">৳ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Prominent Non-Editable Total Waiver Amount Display Card Below Table --}}
                    <div style="margin-top: 10px; padding: 10px 14px; background: #ffffff; border: 2px solid #22c55e; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #166534; letter-spacing: 0.5px;">Total Waiver Amount</div>
                            <div style="font-size: 11px; color: var(--muted); margin-top: 1px;">Auto-calculated from license percentage inputs</div>
                        </div>
                        <div style="text-align: right;">
                            <span id="edit_total_waiver_display" style="font-size: 19px; font-weight: 800; color: #15803d; font-family: monospace, sans-serif;">৳ 0.00</span>
                            <input type="hidden" name="waiver_amount" id="edit_waiver_amount" value="0.00">
                        </div>
                    </div>

                    <input type="hidden" name="breakdown_config" id="edit_breakdown_config_json">
                </div>

                {{-- TOGGLE: SUBTRACT FROM LATEST OS IN NEXT MONTH --}}
                <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: var(--ink); margin: 0; cursor: pointer; display: block;" for="edit_toggle_subtract_os">
                                Subtract Waiver Amount from Latest OS from Next Month
                            </label>
                            <span style="font-size: 11px; color: var(--muted); display: block; margin-top: 2px;">
                                Subtract this waiver from Latest OS and push the remainder (<code>Latest OS − Waiver Amount</code>) to Backlog in next month's rollover.
                            </span>
                        </div>
                        <div id="edit_toggle_wrapper">
                            <label class="switch-toggle">
                                <input type="checkbox" name="subtract_from_next_month_os" id="edit_toggle_subtract_os" value="1">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- Informational Rollover Deduction Note --}}
                    <div style="margin-top: 10px; padding: 8px 12px; background: #e0f2fe; border-left: 3px solid #0284c7; border-radius: 4px; font-size: 11.5px; color: #0369a1; line-height: 1.45;">
                        <strong>ℹ️ Rollover Deduction Logic Note:</strong>
                        When this option is enabled, it takes effect <strong>only if the selected applicable date is in the current month or future</strong>. During the month-opening rollover script execution, these exact component waiver amounts will be subtracted from the client's closing <strong>Latest OS</strong>, carrying forward the reduced balance as next month's opening <strong>Backlog</strong>. Past/historical rollover months already finalized will not be modified.
                    </div>

                    {{-- Live Warning Alert for Past Dates --}}
                    <div id="edit_rollover_date_alert" style="display: none; margin-top: 8px; padding: 7px 12px; background: #fef2f2; border-left: 3px solid #ef4444; border-radius: 4px; font-size: 11px; color: #991b1b; line-height: 1.4;"></div>
                </div>

                {{-- Attachment & Description --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Attachment File</label>
                    <div id="edit_existing_attachment" style="font-size: 11.5px; color: #0284c7; margin-bottom: 4px; font-weight: 600;"></div>
                    <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" style="width: 100%; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px;">
                    <small style="color: var(--muted); font-size: 11px;">Leave empty to keep existing attachment</small>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Description / Reason</label>
                    <textarea id="edit_description" name="description" rows="3" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; flex-shrink: 0;">
                    <button type="button" onclick="closeEditWaiverModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--line); background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveWaiverEditBtn" style="padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // State storage for breakdown calculator
        const breakdownStates = {
            add: {
                clientId: null,
                clientData: null,
                rows: {} // key -> { key, modality, license, opening_os, latest_os, active, pct, amount }
            },
            edit: {
                clientId: null,
                clientData: null,
                rows: {}
            }
        };

        $(document).ready(function() {
            // 1. Initialize Filter Bar Client Select2
            $('#filter_client_id').select2({
                placeholder: 'All Clients',
                allowClear: true,
                width: '100%'
            });

            // 2. Initialize Filter Bar Per-Page Select2
            $('#filter_per_page').select2({
                minimumResultsForSearch: Infinity,
                width: '76px'
            });

            // 3. Initialize Add Modal Client Select2 with modal dropdownParent
            $('#create_client_id').select2({
                placeholder: '-- Choose Client --',
                dropdownParent: $('#addWaiverModal'),
                width: '100%'
            }).on('change', function() {
                const clientId = $(this).val();
                if (clientId) {
                    loadClientOsBreakdown('add', clientId);
                } else {
                    document.getElementById('add_breakdown_container').style.display = 'none';
                }
            });

            // 4. Initialize Edit Modal Client Select2 with modal dropdownParent
            $('#edit_client_id').select2({
                placeholder: '-- Choose Client --',
                dropdownParent: $('#editWaiverModal'),
                width: '100%'
            }).on('change', function() {
                const clientId = $(this).val();
                if (clientId && breakdownStates.edit.clientId != clientId) {
                    loadClientOsBreakdown('edit', clientId);
                }
            });
        });

        function formatCurrency(val) {
            return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function isCurrentOrFutureMonth(dateStr) {
            if (!dateStr) return true;
            const now = new Date();
            const currentMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);
            const d = new Date(dateStr);
            return d >= currentMonthStart;
        }

        function checkApplicableDateScope(prefix) {
            const dateInput = document.getElementById(`${prefix}_applicable_from_date`);
            const toggleInput = document.getElementById(`${prefix}_toggle_subtract_os`);
            const toggleWrapper = document.getElementById(`${prefix}_toggle_wrapper`);
            const alertEl = document.getElementById(`${prefix}_rollover_date_alert`);
            if (!dateInput) return;

            const dateVal = dateInput.value;
            const isCurrentOrFuture = isCurrentOrFutureMonth(dateVal);

            if (!isCurrentOrFuture) {
                // Past date -> disable and uncheck toggle switch
                if (toggleInput) {
                    toggleInput.checked = false;
                    toggleInput.disabled = true;
                }
                if (toggleWrapper) {
                    toggleWrapper.style.opacity = '0.4';
                    toggleWrapper.style.cursor = 'not-allowed';
                    toggleWrapper.title = 'Rollover deduction is disabled for past months';
                }
                if (alertEl) {
                    alertEl.style.display = 'block';
                    alertEl.innerHTML = `⚠️ <strong>Note:</strong> Rollover deduction is <strong>only applicable if the selected applicable date is in the current month or future</strong>. Since <code>${dateVal}</code> is in a past finalized month, this option is disabled.`;
                }
            } else {
                if (toggleInput) {
                    toggleInput.disabled = false;
                }
                if (toggleWrapper) {
                    toggleWrapper.style.opacity = '1';
                    toggleWrapper.style.cursor = 'pointer';
                    toggleWrapper.title = '';
                }
                if (alertEl) {
                    alertEl.style.display = 'none';
                }
            }
        }

        function onApplicableDateChanged(prefix) {
            checkApplicableDateScope(prefix);
            const clientSelectId = prefix === 'add' ? '#create_client_id' : '#edit_client_id';
            const clientId = $(clientSelectId).val();
            if (clientId) {
                loadClientOsBreakdown(prefix, clientId);
            }
        }

        async function loadClientOsBreakdown(prefix, clientId, initialConfig = null, targetDate = null) {
            const container = document.getElementById(`${prefix}_breakdown_container`);
            const tbody = document.getElementById(`${prefix}_breakdown_tbody`);
            container.style.display = 'block';

            const dateInput = document.getElementById(`${prefix}_applicable_from_date`);
            const dateVal = targetDate || (dateInput ? dateInput.value : '');

            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 18px; color: var(--muted);"><div class="spinner-ring"></div><div style="margin-top: 6px; font-weight: 600; font-size: 11.5px;">Loading OS breakdown for selected month...</div></td></tr>`;

            // Capture existing percentage inputs if user already typed values before changing date
            const existingPcts = {};
            const state = breakdownStates[prefix];
            if (state && state.rows) {
                Object.values(state.rows).forEach(r => {
                    if (r.pct > 0) existingPcts[r.key] = r.pct;
                });
            }

            try {
                let url = `{{ url('/waivers/client-breakdown') }}/${clientId}`;
                if (dateVal) {
                    url += `?date=${encodeURIComponent(dateVal)}`;
                }

                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 14px; color: var(--danger); font-weight: 700;">Failed to load breakdown.</td></tr>`;
                    return;
                }

                state.clientId = clientId;
                state.clientData = data;
                state.rows = {};

                // Update Header Badges and Title
                const monthLabel = data.summary_month_label || 'Selected Month';
                const titleEl = document.getElementById(`${prefix}_breakdown_title`);
                if (titleEl) {
                    titleEl.innerText = 'OS Breakdown';
                }
                const badgeEl = document.getElementById(`${prefix}_month_badge`);
                if (badgeEl) {
                    badgeEl.innerText = monthLabel;
                }

                document.getElementById(`${prefix}_grand_opening`).innerText = '৳ ' + formatCurrency(data.totals.grand_opening);
                document.getElementById(`${prefix}_grand_latest`).innerText = '৳ ' + formatCurrency(data.totals.grand_latest);

                // Initial config restoration if editing
                const savedComponents = (initialConfig && initialConfig.components) ? initialConfig.components : {};

                // Initialize state rows for all components
                data.components.forEach(comp => {
                    const saved = savedComponents[comp.key];
                    const hasSaved = Boolean(saved);
                    const isOutOfScope = (parseFloat(comp.latest_os) || 0) <= 0;

                    let pct = 0;
                    if (hasSaved) {
                        pct = parseFloat(saved.waiver_pct) || 0;
                    } else if (existingPcts[comp.key] !== undefined) {
                        pct = existingPcts[comp.key];
                    }

                    const amt = isOutOfScope ? 0 : Math.round(parseFloat(comp.latest_os) * (pct / 100) * 100) / 100;
                    const isActive = isOutOfScope ? false : (initialConfig ? hasSaved : (pct > 0 || true));

                    state.rows[comp.key] = {
                        key: comp.key,
                        modality: comp.modality,
                        license: comp.license,
                        opening_os: parseFloat(comp.opening_os) || 0,
                        latest_os: parseFloat(comp.latest_os) || 0,
                        is_out_of_scope: isOutOfScope,
                        active: isActive,
                        pct: isOutOfScope ? 0 : pct,
                        amount: amt
                    };
                });

                // Check if editing an existing waiver without breakdown_config
                const currentAmtInput = document.getElementById(`${prefix}_waiver_amount`);
                const existingAmt = currentAmtInput ? parseFloat(currentAmtInput.value) : 0;

                if (!initialConfig && Object.keys(existingPcts).length === 0 && existingAmt > 0) {
                    distributeTotalToRows(prefix, existingAmt);
                } else {
                    renderBreakdownRows(prefix);
                    recalculateWaiverSummary(prefix);
                }

            } catch (err) {
                console.error('Breakdown fetch error:', err);
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 14px; color: var(--danger); font-weight: 700;">Error fetching data. Please try again.</td></tr>`;
            }
        }

        function distributeTotalToRows(prefix, totalAmount) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;
            const validRows = Object.values(state.rows).filter(r => !r.is_out_of_scope && r.latest_os > 0);
            const totalValidOs = validRows.reduce((sum, r) => sum + r.latest_os, 0);

            if (totalValidOs > 0 && totalAmount > 0) {
                let accumulated = 0;
                validRows.forEach((row, idx) => {
                    row.active = true;
                    if (idx === validRows.length - 1) {
                        row.amount = Math.max(0, Math.round((totalAmount - accumulated) * 100) / 100);
                    } else {
                        const ratio = row.latest_os / totalValidOs;
                        row.amount = Math.round(totalAmount * ratio * 100) / 100;
                        accumulated += row.amount;
                    }
                    row.pct = Math.min(100, Math.round((row.amount / row.latest_os * 100) * 100) / 100);
                });
            }
            renderBreakdownRows(prefix);
            recalculateWaiverSummary(prefix);
        }

        function renderBreakdownRows(prefix) {
            const state = breakdownStates[prefix];
            const tbody = document.getElementById(`${prefix}_breakdown_tbody`);
            if (!state.clientData || !state.clientData.components) return;

            let html = '';

            state.clientData.components.forEach(comp => {
                const isOutOfScope = (parseFloat(comp.latest_os) || 0) <= 0;
                const rowState = state.rows[comp.key] || { active: !isOutOfScope, pct: 0, amount: 0, is_out_of_scope: isOutOfScope };
                const pctVal = rowState.pct > 0 ? rowState.pct : '';
                const amtFormatted = formatCurrency(rowState.amount || 0);

                const modLower = (comp.modality || '').toLowerCase();
                const modBg = modLower === 'prepaid' ? '#fef3c7' : (modLower === 'discontinued' ? '#fee2e2' : '#e0f2fe');
                const modColor = modLower === 'prepaid' ? '#92400e' : (modLower === 'discontinued' ? '#991b1b' : '#0369a1');

                const rowBg = isOutOfScope ? '#f8fafc' : (rowState.active ? '#ffffff' : '#f8fafc');
                const rowOpacity = isOutOfScope ? '0.45' : (rowState.active ? '1' : '0.55');

                html += `
                    <tr style="border-bottom: 1px solid #f1f5f9; background: ${rowBg}; opacity: ${rowOpacity}; line-height: 1.15;" id="${prefix}_row_${comp.key}">
                        <td style="padding: 2px 4px; text-align: center; width: 24px;">
                            ${isOutOfScope
                                ? `<input type="checkbox" disabled title="Out of scope (No Outstanding OS)" style="width: 12px; height: 12px; margin: 0; vertical-align: middle; cursor: not-allowed; opacity: 0.5;">`
                                : `<input type="checkbox" class="${prefix}-row-active-chk" data-key="${comp.key}" ${rowState.active ? 'checked' : ''} onchange="toggleRowActive('${prefix}', '${comp.key}', this.checked)" style="width: 12px; height: 12px; margin: 0; vertical-align: middle; cursor: pointer; accent-color: var(--primary);">`
                            }
                        </td>
                        <td style="padding: 2px 6px; font-weight: 600; font-size: 10px;">
                            <span class="pill" style="font-size: 9px; padding: 0.5px 4px; background: ${modBg}; color: ${modColor}; border-radius: 3px; font-weight: 700; line-height: 1;">
                                ${comp.modality}
                            </span>
                        </td>
                        <td style="padding: 2px 6px; font-weight: 700; color: var(--ink); font-size: 11px; white-space: nowrap;">
                            ${comp.license}
                        </td>
                        <td style="padding: 2px 6px; text-align: right; color: var(--muted); font-size: 10.5px;">
                            ৳ ${formatCurrency(comp.opening_os)}
                        </td>
                        <td style="padding: 2px 6px; text-align: right; font-weight: 700; color: ${isOutOfScope ? '#94a3b8' : '#0284c7'}; font-size: 11px;">
                            ৳ ${formatCurrency(comp.latest_os)}
                        </td>
                        <td style="padding: 2px 6px; text-align: center;">
                            ${isOutOfScope
                                ? `<span style="font-size: 9px; font-weight: 700; color: #94a3b8; background: #e2e8f0; padding: 1px 4px; border-radius: 3px; display: inline-block; line-height: 1;" title="No outstanding balance to waive">Out of scope</span>`
                                : `<div style="display: inline-flex; align-items: center; justify-content: center; gap: 2px;">
                                    <input type="number" step="0.01" min="0" max="100" class="${prefix}-row-pct row-pct-input" id="${prefix}_row_pct_${comp.key}" data-key="${comp.key}" value="${pctVal}" placeholder="0" 
                                        oninput="handleRowPctInput('${prefix}', '${comp.key}', this)" 
                                        onkeyup="handleRowPctInput('${prefix}', '${comp.key}', this)" 
                                        onchange="handleRowPctInput('${prefix}', '${comp.key}', this)" 
                                        style="width: 44px; height: 20px; text-align: center; border: 1px solid var(--line); border-radius: 3px; font-size: 10.5px; font-weight: 700; outline: none; padding: 0 2px;">
                                    <span style="font-size: 9.5px; font-weight: 700; color: var(--muted);">%</span>
                                   </div>`
                            }
                        </td>
                        <td style="padding: 2px 6px; text-align: right;">
                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 2px;">
                                <span id="${prefix}_row_amt_${comp.key}" style="font-size: 11px; font-weight: 800; color: ${isOutOfScope ? '#94a3b8' : '#166534'}; font-family: monospace, sans-serif;">
                                    ৳ ${amtFormatted}
                                </span>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            updateMasterCheckState(prefix);
        }

        function updateMasterCheckState(prefix) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;
            const validRows = Object.values(state.rows).filter(r => !r.is_out_of_scope && r.latest_os > 0);
            if (!validRows.length) return;
            const anyActive = validRows.some(r => r.active);
            const allActive = validRows.every(r => r.active);
            const masterChk = document.getElementById(`${prefix}_master_chk`);
            if (masterChk) {
                masterChk.checked = anyActive && allActive;
                masterChk.indeterminate = anyActive && !allActive;
            }
        }

        function toggleAllRowChecks(prefix, masterChk) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;
            const isChecked = masterChk.checked;

            Object.values(state.rows).forEach(row => {
                if (!row.is_out_of_scope && row.latest_os > 0) {
                    row.active = isChecked;
                    const chk = document.querySelector(`.${prefix}-row-active-chk[data-key="${row.key}"]`);
                    if (chk) chk.checked = isChecked;
                    const tr = document.getElementById(`${prefix}_row_${row.key}`);
                    if (tr) {
                        tr.style.opacity = isChecked ? '1' : '0.55';
                        tr.style.background = isChecked ? '#ffffff' : '#f8fafc';
                    }
                }
            });

            updateMasterCheckState(prefix);
            recalculateWaiverSummary(prefix);
        }

        function toggleRowActive(prefix, key, isActive) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows || !state.rows[key]) return;
            const row = state.rows[key];
            if (row.is_out_of_scope || row.latest_os <= 0) {
                row.active = false;
                return;
            }

            row.active = isActive;
            const tr = document.getElementById(`${prefix}_row_${key}`);
            if (tr) {
                tr.style.opacity = isActive ? '1' : '0.55';
                tr.style.background = isActive ? '#ffffff' : '#f8fafc';
            }
            updateMasterCheckState(prefix);
            recalculateWaiverSummary(prefix);
        }

        function handleRowPctInput(prefix, key, inputEl) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;
            const row = state.rows[key];
            if (!row || row.is_out_of_scope || row.latest_os <= 0) return;

            let rawVal = inputEl.value;
            let val = parseFloat(rawVal);

            if (rawVal === '' || isNaN(val)) {
                row.pct = 0;
                row.amount = 0;
            } else {
                if (val < 0) {
                    val = 0;
                    inputEl.value = '0';
                } else if (val > 100) {
                    val = 100;
                    inputEl.value = '100';
                }
                row.pct = val;
                row.amount = Math.round(row.latest_os * (val / 100) * 100) / 100;
            }

            // Auto-activate row if user entered > 0% and it was inactive
            if (row.pct > 0 && !row.active) {
                row.active = true;
                const chk = document.querySelector(`.${prefix}-row-active-chk[data-key="${key}"]`);
                if (chk) chk.checked = true;
                const tr = document.getElementById(`${prefix}_row_${key}`);
                if (tr) { tr.style.opacity = '1'; tr.style.background = '#ffffff'; }
                updateMasterCheckState(prefix);
            }

            // Immediately update the non-editable row amount text display
            const amtSpan = document.getElementById(`${prefix}_row_amt_${key}`);
            if (amtSpan) {
                amtSpan.innerText = '৳ ' + formatCurrency(row.amount);
            }

            // Immediately auto-calculate footer totals and total waiver label
            recalculateWaiverSummary(prefix);
        }

        function applyBulkPct(prefix) {
            const bulkInput = document.getElementById(`${prefix}_bulk_pct`);
            let val = parseFloat(bulkInput.value);
            if (isNaN(val) || val < 0) val = 0;
            if (val > 100) { val = 100; bulkInput.value = '100'; }

            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;

            Object.values(state.rows).forEach(row => {
                if (!row.is_out_of_scope && row.latest_os > 0) {
                    row.active = true;
                    row.pct = val;
                    row.amount = Math.round(row.latest_os * (val / 100) * 100) / 100;

                    const pctInput = document.getElementById(`${prefix}_row_pct_${row.key}`);
                    if (pctInput) pctInput.value = val > 0 ? val : '';
                    const amtSpan = document.getElementById(`${prefix}_row_amt_${row.key}`);
                    if (amtSpan) amtSpan.innerText = '৳ ' + formatCurrency(row.amount);
                    const chk = document.querySelector(`.${prefix}-row-active-chk[data-key="${row.key}"]`);
                    if (chk) chk.checked = true;
                    const tr = document.getElementById(`${prefix}_row_${row.key}`);
                    if (tr) { tr.style.opacity = '1'; tr.style.background = '#ffffff'; }
                }
            });

            updateMasterCheckState(prefix);
            recalculateWaiverSummary(prefix);
        }

        function recalculateWaiverSummary(prefix) {
            const state = breakdownStates[prefix];
            if (!state || !state.rows) return;

            let selectedOpeningOsTotal = 0;
            let selectedLatestOsTotal = 0;
            let totalWaiverAmount = 0;
            const configuredComponents = {};

            Object.values(state.rows).forEach(row => {
                if (!row.active || row.is_out_of_scope) return;

                selectedOpeningOsTotal += row.opening_os;
                selectedLatestOsTotal += row.latest_os;
                totalWaiverAmount += row.amount;

                if (row.pct > 0 || row.amount > 0) {
                    configuredComponents[row.key] = {
                        key: row.key,
                        modality: row.modality,
                        license: row.license,
                        opening_os: row.opening_os,
                        latest_os: row.latest_os,
                        waiver_pct: row.pct,
                        waiver_amount: row.amount
                    };
                }
            });

            totalWaiverAmount = Math.round(totalWaiverAmount * 100) / 100;
            const effectivePct = selectedLatestOsTotal > 0 ? (totalWaiverAmount / selectedLatestOsTotal * 100) : 0;

            // Update Table Footers (Total sum row at bottom of table)
            const openOsEl = document.getElementById(`${prefix}_selected_opening_os`);
            if (openOsEl) openOsEl.innerText = '৳ ' + formatCurrency(selectedOpeningOsTotal);

            const latestOsEl = document.getElementById(`${prefix}_selected_latest_os`);
            if (latestOsEl) latestOsEl.innerText = '৳ ' + formatCurrency(selectedLatestOsTotal);

            const effPctEl = document.getElementById(`${prefix}_effective_pct`);
            if (effPctEl) effPctEl.innerText = effectivePct.toFixed(2) + '%';

            const totalWaiverCalcEl = document.getElementById(`${prefix}_total_waiver_calc`);
            if (totalWaiverCalcEl) totalWaiverCalcEl.innerText = '৳ ' + formatCurrency(totalWaiverAmount);

            // Update prominent non-editable Total Waiver Amount label card below table
            const totalDisplayEl = document.getElementById(`${prefix}_total_waiver_display`);
            if (totalDisplayEl) totalDisplayEl.innerText = '৳ ' + formatCurrency(totalWaiverAmount);

            // Update hidden input for form submission
            const waiverAmtInput = document.getElementById(`${prefix}_waiver_amount`);
            if (waiverAmtInput) waiverAmtInput.value = totalWaiverAmount.toFixed(2);

            // Save JSON config into hidden input
            const configPayload = {
                components: configuredComponents,
                total_selected_opening_os: Math.round(selectedOpeningOsTotal * 100) / 100,
                total_selected_latest_os: Math.round(selectedLatestOsTotal * 100) / 100,
                total_waiver_amount: totalWaiverAmount,
                effective_pct: Math.round(effectivePct * 100) / 100,
                calculated_at: new Date().toISOString()
            };
            const configInput = document.getElementById(`${prefix}_breakdown_config_json`);
            if (configInput) configInput.value = JSON.stringify(configPayload);
        }

        function changePageSize(size) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', size);
            urlParams.set('page', 1);
            window.location.search = urlParams.toString();
        }

        function openAddWaiverModal() {
            document.getElementById('addWaiverForm').reset();
            $('#create_client_id').val('').trigger('change');
            document.getElementById('add_breakdown_container').style.display = 'none';
            document.getElementById('add_toggle_subtract_os').checked = false;
            document.getElementById('add_breakdown_config_json').value = '';
            document.getElementById('add_waiver_amount').value = '0.00';
            document.getElementById('add_total_waiver_display').innerText = '৳ 0.00';
            const bulkPct = document.getElementById('add_bulk_pct');
            if (bulkPct) bulkPct.value = '';

            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('add_applicable_from_date').value = today;
            checkApplicableDateScope('add');

            document.getElementById('addWaiverModal').style.display = 'flex';
        }

        function closeAddWaiverModal() {
            document.getElementById('addWaiverModal').style.display = 'none';
        }

        function openEditWaiverModal(data) {
            document.getElementById('edit_waiver_id').value = data.id;
            document.getElementById('edit_applicable_from_date').value = data.applicable_from_date;
            document.getElementById('edit_waiver_amount').value = parseFloat(data.waiver_amount || 0).toFixed(2);
            document.getElementById('edit_total_waiver_display').innerText = '৳ ' + formatCurrency(data.waiver_amount || 0);
            document.getElementById('edit_description').value = data.description || '';
            const bulkPct = document.getElementById('edit_bulk_pct');
            if (bulkPct) bulkPct.value = '';

            // Toggle switch
            document.getElementById('edit_toggle_subtract_os').checked = Boolean(data.subtract_from_next_month_os);

            // Attachment notice
            const existingAttach = document.getElementById('edit_existing_attachment');
            if (data.attachment_name) {
                existingAttach.innerText = 'Current File: 📎 ' + data.attachment_name;
            } else {
                existingAttach.innerText = '';
            }

            // Action form URL
            const form = document.getElementById('editWaiverForm');
            form.action = `{{ url('/waivers') }}/${data.id}`;

            // Select client in Select2 and load breakdown
            breakdownStates.edit.clientId = data.client_id;
            $('#edit_client_id').val(data.client_id).trigger('change');
            loadClientOsBreakdown('edit', data.client_id, data.breakdown_config, data.applicable_from_date);

            checkApplicableDateScope('edit');

            document.getElementById('editWaiverModal').style.display = 'flex';
        }

        function closeEditWaiverModal() {
            document.getElementById('editWaiverModal').style.display = 'none';
        }

        async function confirmDeleteWaiver(id, clientName, amount) {
            if (!confirm(`Are you sure you want to delete the waiver of ৳${amount} for "${clientName}"?`)) {
                return;
            }

            try {
                const res = await fetch(`{{ url('/waivers') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alert('Waiver deleted successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Error deleting waiver.');
                }
            } catch (err) {
                alert('An unexpected error occurred.');
            }
        }
    </script>
@endpush
