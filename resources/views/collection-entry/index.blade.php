@extends('layouts.app')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            border: 1px solid var(--line);
            border-radius: 8px;
            height: 44px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            background-color: #ffffff;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            right: 8px;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            color: var(--ink);
            font-size: 14px;
            padding-left: 0;
            font-weight: 500;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--muted);
        }
        .select2-dropdown {
            border-color: var(--line);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary);
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 6px 10px;
            outline: none;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: var(--primary);
        }
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        /* Metrics Progress Bars styling */
        .metrics-visualizer {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 14px;
            background: #f8fafc;
            margin-top: 18px;
        }
        .metrics-visualizer-title {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--primary-dark);
            border-bottom: 1px solid var(--line);
            padding-bottom: 6px;
        }
        .bar-label-container {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .bar-label-title {
            font-weight: 600;
            color: var(--muted);
        }
        .bar-label-value {
            font-weight: 700;
            color: var(--ink);
        }
        .bar-wrapper {
            margin-bottom: 12px;
        }
        .bar-wrapper:last-child {
            margin-bottom: 0;
        }
        .visual-bar-container {
            height: 16px;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
            background: #e2e8f0;
            border: 1px solid var(--line);
            transition: all 0.2s ease;
        }

        @keyframes glowing-bar-primary {
            0% { box-shadow: 0 0 3px rgba(15, 118, 110, 0.4); }
            50% { box-shadow: 0 0 10px rgba(15, 118, 110, 0.8), inset 0 0 4px rgba(255, 255, 255, 0.2); }
            100% { box-shadow: 0 0 3px rgba(15, 118, 110, 0.4); }
        }

        @keyframes glowing-bar-success {
            0% { box-shadow: 0 0 3px rgba(22, 101, 52, 0.4); }
            50% { box-shadow: 0 0 10px rgba(22, 101, 52, 0.8), inset 0 0 4px rgba(255, 255, 255, 0.2); }
            100% { box-shadow: 0 0 3px rgba(22, 101, 52, 0.4); }
        }

        @keyframes glowing-bar-accent {
            0% { box-shadow: 0 0 3px rgba(3, 105, 161, 0.4); }
            50% { box-shadow: 0 0 10px rgba(3, 105, 161, 0.8), inset 0 0 4px rgba(255, 255, 255, 0.2); }
            100% { box-shadow: 0 0 3px rgba(3, 105, 161, 0.4); }
        }

        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: 200px 0; }
        }

        .visual-bar-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
            background-size: 200px 100%;
            background-repeat: repeat;
        }

        .bar-glow-primary {
            animation: glowing-bar-primary 2s infinite, shimmer 2s infinite linear;
            background-image: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.25) 50%, 
                rgba(255, 255, 255, 0) 100%
            );
        }

        .bar-glow-success {
            animation: glowing-bar-success 2s infinite, shimmer 2s infinite linear;
            background-image: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.25) 50%, 
                rgba(255, 255, 255, 0) 100%
            );
        }

        .bar-glow-accent {
            animation: glowing-bar-accent 2s infinite, shimmer 2s infinite linear;
            background-image: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.25) 50%, 
                rgba(255, 255, 255, 0) 100%
            );
        }
    </style>
@endpush

@section('content')
    <section class="page-heading">
        <div>
            <h1>Collection entry</h1>
            <p>Post received customer collections and review the latest transaction entries.</p>
        </div>
    </section>

    <!-- Pending Batch Panel (Visible only when batch has items) -->
    <article class="panel" id="batch_panel" style="display: none; margin-bottom: 22px; width: 100%;">
        <div class="panel-header" style="background: rgba(15, 118, 110, 0.05); border-bottom-color: rgba(15, 118, 110, 0.2); display: flex; justify-content: space-between; align-items: center; padding: 12px 18px;">
            <h2 style="color: var(--primary-dark); display: flex; align-items: center; gap: 8px; margin: 0;">📦 Pending Batch <span id="batch_count_badge" class="pill" style="background: var(--primary); color: #fff; font-size: 11px; padding: 4px 8px;">0 entries</span></h2>
            <form id="batch_submit_form" method="POST" action="{{ route('collection-entry.store') }}" style="margin: 0; display: flex; align-items: center;">
                @csrf
                <input type="hidden" id="batch_data_input" name="batch_data" value="">
                <button class="button success" type="submit" style="min-height: 38px; display: flex; align-items: center; justify-content: center; gap: 8px; background: #166534; color: #fff; font-weight: 800; border: 0; border-radius: 8px; cursor: pointer; padding: 0 16px;">
                    💾 Save Batch (<span id="batch_submit_count">0</span> items)
                </button>
            </form>
        </div>
        <div class="panel-body" style="padding: 0;">
            <div style="max-height: 300px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; margin: 0;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--line); background: #f8fafc;">
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Client</th>
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Actual Datetime</th>
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">For Month</th>
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Type</th>
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Mode</th>
                            <th style="text-align: right; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Amount</th>
                            <th style="text-align: left; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc;">Remarks</th>
                            <th style="text-align: center; padding: 12px; font-size: 12px; color: var(--muted); position: sticky; top: 0; background: #f8fafc; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="batch_tbody">
                        <!-- Dynamic batch rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <section class="grid two">
        <!-- LEFT PANEL: Entry Form -->
        <article class="panel">
            <div class="panel-header">
                <h2>New collection</h2>
                <span>Manual entry</span>
            </div>
            <form class="panel-body" method="POST" action="{{ route('collection-entry.store') }}">
                @csrf

                <div class="field">
                    <label for="client_id">Client</label>
                    <select id="client_id" name="client_id" required>
                        <option value="">Select client</option>
                        <option value="untraced" @selected(old('client_id') === 'untraced')>Untraced Collection</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->client_id }}" @selected(old('client_id') == $client->client_id)>
                                {{ $client->client_name }} - {{ $client->opus_id }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_datetime">Collection actual datetime</label>
                    <input id="collection_datetime" name="collection_datetime" type="datetime-local" value="{{ old('collection_datetime') }}" required>
                    @error('collection_datetime')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_month_select">Collection for month</label>
                    @php
                        $inputVal = old('collection_month') 
                            ? \Carbon\Carbon::parse(old('collection_month'))->format('Y-m') 
                            : ($defaultMonthString ?? '');
                        $hiddenVal = old('collection_month') 
                            ? old('collection_month') 
                            : ($defaultMonthFullDate ?? '');
                    @endphp
                    <input id="collection_month_select" type="month" value="{{ $inputVal }}" required>
                    <input id="collection_month" name="collection_month" type="hidden" value="{{ $hiddenVal }}" required>
                    @error('collection_month')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_type">Collection type</label>
                    <select id="collection_type" name="collection_type" required>
                        <option value="">Select type</option>
                        @foreach ($collectionTypes as $type)
                            <option value="{{ $type }}" @selected(old('collection_type') === $type)>
                                {{ strtoupper(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('collection_type')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_amount">Amount</label>
                    <input id="collection_amount" name="collection_amount" type="number" min="0" step="0.01" value="{{ old('collection_amount') }}" required>
                    @error('collection_amount')<div class="error">{{ $message }}</div>@enderror

                    <!-- Interactive Metrics visualizer bars -->
                    <div class="metrics-visualizer" id="metrics_visualizer" style="display: none;">
                        <div class="metrics-visualizer-title">Collection Impact vs Client Metrics</div>
                        
                        <!-- OS Bar -->
                        <div class="bar-wrapper">
                            <div class="bar-label-container">
                                <span class="bar-label-title">Outstanding OS</span>
                                <span class="bar-label-value" id="os_bar_label">0.00 / 0.00</span>
                            </div>
                            <div class="visual-bar-container bar-container-os" id="os_bar_container">
                                <div class="visual-bar-fill" id="os_bar_fill"></div>
                            </div>
                        </div>

                        <!-- MRC Bar -->
                        <div class="bar-wrapper">
                            <div class="bar-label-container">
                                <span class="bar-label-title">Monthly MRC</span>
                                <span class="bar-label-value" id="mrc_bar_label">0.00 / 0.00</span>
                            </div>
                            <div class="visual-bar-container bar-container-mrc" id="mrc_bar_container">
                                <div class="visual-bar-fill" id="mrc_bar_fill"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banking & Instrument Details (Collapsible Section) -->
                <details open style="border: 1px solid var(--line); border-radius: 8px; padding: 12px; margin-top: 14px; background: #f8fafc;">
                    <summary style="font-weight: 700; color: var(--primary-dark); cursor: pointer; user-select: none;">
                        🏦 Banking & Instrument Tracking
                    </summary>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label for="payment_mode" style="font-size: 11.5px;">Payment Mode</label>
                            <select id="payment_mode" name="payment_mode">
                                <option value="">Select Mode</option>
                                <option value="cash" @selected(old('payment_mode') === 'cash')>Cash</option>
                                <option value="cheque" @selected(old('payment_mode') === 'cheque')>Cheque</option>
                                <option value="pay_order" @selected(old('payment_mode') === 'pay_order')>Pay Order</option>
                                <option value="bank_transfer" @selected(old('payment_mode') === 'bank_transfer')>Bank Transfer</option>
                                <option value="eft" @selected(old('payment_mode') === 'eft')>EFT</option>
                                <option value="other" @selected(old('payment_mode') === 'other')>Other</option>
                            </select>
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="trace_no" style="font-size: 11.5px;">Trace / Ref No</label>
                            <input id="trace_no" name="trace_no" type="text" value="{{ old('trace_no') }}" placeholder="Trace / Ref No">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="client_bank" style="font-size: 11.5px;">Client Bank Name (From Bank)</label>
                            <select id="client_bank" name="client_bank">
                                <option value="">Select From Bank</option>
                                @foreach($clientBanks as $cb)
                                    <option value="{{ $cb }}" @selected(old('client_bank') === $cb)>{{ $cb }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="deposit_bank" style="font-size: 11.5px;">Deposited Bank Name</label>
                            <select id="deposit_bank" name="deposit_bank">
                                <option value="">Select Deposited Bank</option>
                                @foreach($depositBanks as $db)
                                    <option value="{{ $db }}" @selected(old('deposit_bank') === $db)>{{ $db }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="cheque_no" style="font-size: 11.5px;">Cheque No</label>
                            <input id="cheque_no" name="cheque_no" type="text" value="{{ old('cheque_no') }}" placeholder="Cheque No">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="cheque_date" style="font-size: 11.5px;">Cheque Date</label>
                            <input id="cheque_date" name="cheque_date" type="date" value="{{ old('cheque_date') }}">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="cheque_expiry_date" style="font-size: 11.5px;">Cheque Expired Date</label>
                            <input id="cheque_expiry_date" name="cheque_expiry_date" type="date" value="{{ old('cheque_expiry_date') }}">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="cheque_placement_date" style="font-size: 11.5px;">Cheque Placement Date</label>
                            <input id="cheque_placement_date" name="cheque_placement_date" type="date" value="{{ old('cheque_placement_date') }}">
                        </div>
                    </div>
                </details>

                <!-- Dishonored Cheque Details (Collapsible Section) -->
                <details style="border: 1px solid var(--line); border-radius: 8px; padding: 12px; margin-top: 10px; background: #fff5f5;">
                    <summary style="font-weight: 700; color: #991b1b; cursor: pointer; user-select: none;">
                        ⚠️ Dishonored Cheque Management
                    </summary>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label for="cheque_status" style="font-size: 11.5px;">Cheque Honor Status</label>
                            <select id="cheque_status" name="cheque_status">
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('cheque_status') === 'pending')>Pending</option>
                                <option value="honored" @selected(old('cheque_status') === 'honored')>Honored</option>
                                <option value="dishonored" @selected(old('cheque_status') === 'dishonored')>Dishonored</option>
                            </select>
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="dishonored_date" style="font-size: 11.5px;">Honored / Dishonored Date</label>
                            <input id="dishonored_date" name="dishonored_date" type="date" value="{{ old('dishonored_date') }}">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="dishonor_count" style="font-size: 11.5px;">No. of Dishonor</label>
                            <input id="dishonor_count" name="dishonor_count" type="number" min="0" value="{{ old('dishonor_count', 0) }}">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="dishonor_slip" style="font-size: 11.5px;">Dishonor Slip / Ref</label>
                            <input id="dishonor_slip" name="dishonor_slip" type="text" value="{{ old('dishonor_slip') }}" placeholder="Slip No / Ref">
                        </div>
                    </div>
                </details>

                <div class="field" style="margin-top: 14px;">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks">{{ old('remarks') }}</textarea>
                    @error('remarks')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px;">
                    <button class="button secondary" type="button" id="add_to_batch_btn" style="min-height: 44px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        ➕ Add to Batch
                    </button>
                    <button class="button primary" type="submit" id="save_single_btn" style="min-height: 44px;">
                        Save Single Entry
                    </button>
                </div>
            </form>
        </article>


        <!-- RIGHT PANEL: Export Card & Recent Entries -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Export Feature Panel -->
            <article class="panel">
                <div class="panel-header" style="background: rgba(15, 118, 110, 0.04); padding: 12px 18px;">
                    <div>
                        <h2 style="margin: 0; font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 6px; color: var(--primary-dark);">
                            📊 Export Collections
                        </h2>
                        <span style="font-size: 11.5px; color: var(--muted);">Month-wise collection records download</span>
                    </div>
                </div>
                <form class="panel-body" method="GET" action="{{ route('collection-entry.export') }}" target="_blank" style="padding: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="field" style="margin-bottom: 0;">
                            <label for="export_month" style="font-size: 11.5px; font-weight: 700;">Target Month</label>
                            <input id="export_month" name="collection_month" type="month" value="{{ $defaultMonthString }}" style="width: 100%; height: 36px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--line);">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label for="export_payment_mode" style="font-size: 11.5px; font-weight: 700;">Payment Mode</label>
                            <select id="export_payment_mode" name="payment_mode" style="width: 100%; height: 36px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--line);">
                                <option value="">All Modes</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="pay_order">Pay Order</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="eft">EFT</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="field" style="grid-column: span 2; margin-bottom: 0;">
                            <label for="export_client_id" style="font-size: 11.5px; font-weight: 700;">Client Filter</label>
                            <select id="export_client_id" name="client_id" style="width: 100%; height: 36px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--line);">
                                <option value="">All Clients</option>
                                <option value="untraced">Untraced Collections</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="button primary" type="submit" style="width: 100%; margin-top: 14px; min-height: 40px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; background: var(--primary); border: none; border-radius: 8px; cursor: pointer;">
                        📥 Download Collections (CSV / Excel)
                    </button>
                </form>
            </article>

            <!-- Recent Entries Panel -->
            <article class="panel">
                <div class="panel-header">
                    <h2>Recent entries</h2>
                    <span id="recent_entries_subtitle">Latest 15</span>
                </div>

                <div id="recent_entries_container">
                    @if ($collections->isNotEmpty())
                        <table>
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($collections as $collection)
                                    <tr>
                                        <td>{{ $collection->client->client_name ?? 'Untraced Collection' }}</td>
                                        <td><span class="pill">{{ str_replace('_', ' ', $collection->collection_type) }}</span></td>
                                        <td><span class="pill" style="background: #e2e8f0; color: #334155;">{{ $collection->payment_mode ? str_replace('_', ' ', strtoupper($collection->payment_mode)) : '—' }}</span></td>
                                        <td class="amount">{{ number_format((float) $collection->collection_amount, 2) }}</td>
                                        <td>{{ optional($collection->collection_datetime)->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty">No collection entries found.</div>
                    @endif
                </div>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 on Client Dropdown
            $('#client_id').select2({
                placeholder: "Select client",
                allowClear: true
            });

            // Synchronization between Month Selector and Hidden Input
            $('#collection_month_select').on('change', function() {
                const val = $(this).val();
                if (val) {
                    $('#collection_month').val(val + '-01');
                } else {
                    $('#collection_month').val('');
                }
                updateMetrics();
            });

            // Trigger update metrics and recent entries when client selection changes
            $('#client_id').on('change select2:select select2:clear', function() {
                updateMetrics();
                updateRecentEntries();
            });

            // Trigger bar rendering on amount input change
            $('#collection_amount').on('input keyup paste', renderBars);

            // Metrics state
            let currentMetrics = {
                total_latest_os: 0,
                total_mrc: 0
            };

            // Fetch metrics from backend endpoint
            async function updateMetrics() {
                const clientId = $('#client_id').val();
                const month = $('#collection_month_select').val();

                if (!clientId || clientId === 'untraced' || !month) {
                    $('#metrics_visualizer').hide();
                    currentMetrics = { total_latest_os: 0, total_mrc: 0 };
                    return;
                }

                try {
                    const response = await fetch(`{{ route('collection-entry.client-metrics') }}?client_id=${clientId}&month=${month}`);
                    currentMetrics = await response.json();
                    
                    $('#metrics_visualizer').show();
                    renderBars();
                } catch (e) {
                    console.error('Error fetching client metrics:', e);
                }
            }

            // Render bars and calculate progress
            function renderBars() {
                const amount = parseFloat($('#collection_amount').val()) || 0;
                const os = parseFloat(currentMetrics.total_latest_os) || 0;
                const mrc = parseFloat(currentMetrics.total_mrc) || 0;

                // 1. OS Bar calculation
                if (os > 0) {
                    const osPct = Math.min((amount / os) * 100, 100);
                    $('#os_bar_fill').css('width', osPct + '%');
                    
                    const remaining = Math.max(os - amount, 0);
                    
                    $('#os_bar_label').text(
                        `${remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} remaining / ${os.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} total`
                    );

                    if (amount >= os) {
                        $('#os_bar_fill')
                            .css('background-color', 'var(--success)')
                            .removeClass('bar-glow-primary')
                            .addClass('bar-glow-success');
                        $('#os_bar_container').css('background-color', '#dcfce7').css('border-color', '#bbf7d0');
                    } else {
                        $('#os_bar_fill')
                            .css('background-color', 'var(--primary)')
                            .removeClass('bar-glow-success')
                            .addClass('bar-glow-primary');
                        $('#os_bar_container').css('background-color', '#fee2e2').css('border-color', '#fecaca');
                    }
                } else {
                    $('#os_bar_fill')
                        .css('width', '0%')
                        .removeClass('bar-glow-primary bar-glow-success');
                    $('#os_bar_label').text('0.00 / 0.00');
                    $('#os_bar_container').css('background-color', '#e2e8f0').css('border-color', 'var(--line)');
                }

                // 2. MRC Bar calculation
                if (mrc > 0) {
                    const mrcPct = Math.min((amount / mrc) * 100, 100);
                    $('#mrc_bar_fill').css('width', mrcPct + '%');
                    
                    $('#mrc_bar_label').text(
                        `${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} collected / ${mrc.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} total (${Math.round(mrcPct)}%)`
                    );

                    if (amount >= mrc) {
                        $('#mrc_bar_fill')
                            .css('background-color', 'var(--success)')
                            .removeClass('bar-glow-accent')
                            .addClass('bar-glow-success');
                        $('#mrc_bar_container').css('background-color', '#dcfce7').css('border-color', '#bbf7d0');
                    } else {
                        $('#mrc_bar_fill')
                            .css('background-color', 'var(--accent)')
                            .removeClass('bar-glow-success')
                            .addClass('bar-glow-accent');
                        $('#mrc_bar_container').css('background-color', '#f8fafc').css('border-color', 'var(--line)');
                    }
                } else {
                    $('#mrc_bar_fill')
                        .css('width', '0%')
                        .removeClass('bar-glow-accent bar-glow-success');
                    $('#mrc_bar_label').text('0.00 / 0.00');
                    $('#mrc_bar_container').css('background-color', '#e2e8f0').css('border-color', 'var(--line)');
                }
            }

            // Fetch recent collection entries
            async function updateRecentEntries() {
                const clientId = $('#client_id').val();
                const container = $('#recent_entries_container');
                const subtitle = $('#recent_entries_subtitle');

                if (clientId && clientId !== 'untraced') {
                    subtitle.text('Selected Client');
                } else if (clientId === 'untraced') {
                    subtitle.text('Untraced Collections');
                } else {
                    subtitle.text('Latest 15');
                }

                try {
                    const url = `{{ route('collection-entry.recent-entries') }}?client_id=${clientId || ''}`;
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data.length === 0) {
                        container.html('<div class="empty">No collection entries found.</div>');
                        return;
                    }

                    let tbodyHtml = '';
                    data.forEach(item => {
                        const modeBadge = item.payment_mode ? `<span class="pill" style="background: #e2e8f0; color: #334155;">${escapeHtml(item.payment_mode)}</span>` : '—';
                        tbodyHtml += `
                            <tr>
                                <td>${escapeHtml(item.client_name)}</td>
                                <td><span class="pill">${escapeHtml(item.collection_type)}</span></td>
                                <td>${modeBadge}</td>
                                <td class="amount">${item.collection_amount}</td>
                                <td>${item.collection_date}</td>
                            </tr>
                        `;
                    });

                    container.html(`
                        <table>
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tbodyHtml}
                            </tbody>
                        </table>
                    `);
                } catch (e) {
                    console.error('Error fetching recent entries:', e);
                }
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

            // Batch entries state
            let batchEntries = [];

            // Add to Batch click handler
            $('#add_to_batch_btn').on('click', function() {
                const clientId = $('#client_id').val();
                const clientText = $('#client_id option:selected').text().trim();
                const dateTime = $('#collection_datetime').val();
                const monthHidden = $('#collection_month').val();
                const colType = $('#collection_type').val();
                const amount = $('#collection_amount').val();
                const remarks = $('#remarks').val();

                // Banking & dishonor fields
                const paymentMode = $('#payment_mode').val();
                const traceNo = $('#trace_no').val();
                const clientBank = $('#client_bank').val();
                const depositBank = $('#deposit_bank').val();
                const chequeNo = $('#cheque_no').val();
                const chequeDate = $('#cheque_date').val();
                const chequeExpiryDate = $('#cheque_expiry_date').val();
                const chequePlacementDate = $('#cheque_placement_date').val();

                const chequeStatus = $('#cheque_status').val();
                const dishonoredDate = $('#dishonored_date').val();
                const dishonorCount = $('#dishonor_count').val();
                const dishonorSlip = $('#dishonor_slip').val();

                // Validate fields
                if (!clientId) {
                    alert('Please select a client.');
                    return;
                }
                if (!dateTime) {
                    alert('Please select a collection datetime.');
                    return;
                }
                if (!monthHidden) {
                    alert('Please select a collection month.');
                    return;
                }
                if (!colType) {
                    alert('Please select a collection type.');
                    return;
                }
                if (!amount || parseFloat(amount) <= 0) {
                    alert('Please enter a valid positive amount.');
                    return;
                }

                // Add to array
                batchEntries.push({
                    client_id: clientId,
                    client_name: clientText,
                    collection_datetime: dateTime,
                    collection_month: monthHidden,
                    collection_type: colType,
                    collection_amount: parseFloat(amount),
                    remarks: remarks,
                    payment_mode: paymentMode,
                    trace_no: traceNo,
                    client_bank: clientBank,
                    deposit_bank: depositBank,
                    cheque_no: chequeNo,
                    cheque_date: chequeDate,
                    cheque_expiry_date: chequeExpiryDate,
                    cheque_placement_date: chequePlacementDate,
                    cheque_status: chequeStatus,
                    dishonored_date: dishonoredDate,
                    dishonor_count: dishonorCount ? parseInt(dishonorCount) : 0,
                    dishonor_slip: dishonorSlip
                });

                // Clear amount, remarks, banking & dishonor inputs
                $('#collection_amount').val('');
                $('#remarks').val('');
                $('#trace_no').val('');
                $('#client_bank').val('');
                $('#deposit_bank').val('');
                $('#cheque_no').val('');
                $('#cheque_date').val('');
                $('#cheque_expiry_date').val('');
                $('#cheque_placement_date').val('');
                $('#dishonored_date').val('');
                $('#dishonor_slip').val('');
                renderBars();

                // Update batch UI
                refreshBatchUI();

                // Smooth scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            function refreshBatchUI() {
                if (batchEntries.length === 0) {
                    $('#batch_panel').hide();
                    $('#save_single_btn').text('Save Single Entry');
                    return;
                }

                $('#batch_panel').show();
                $('#batch_count_badge').text(batchEntries.length + ' entries');
                $('#batch_submit_count').text(batchEntries.length);
                $('#batch_data_input').val(JSON.stringify(batchEntries));

                // Render rows
                let rowsHtml = '';
                batchEntries.forEach((entry, index) => {
                    const cleanType = entry.collection_type.replace(/_/g, ' ').toUpperCase();
                    const cleanMode = entry.payment_mode ? entry.payment_mode.replace(/_/g, ' ').toUpperCase() : '—';
                    
                    let displayDt = entry.collection_datetime;
                    try {
                        const dt = new Date(entry.collection_datetime);
                        if (!isNaN(dt.getTime())) {
                            displayDt = dt.toLocaleString(undefined, {
                                year: 'numeric', month: 'short', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            });
                        }
                    } catch(e) {}

                    let displayMonth = entry.collection_month;
                    try {
                        const parts = entry.collection_month.split('-');
                        if (parts.length >= 2) {
                            const date = new Date(parts[0], parts[1] - 1);
                            displayMonth = date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
                        }
                    } catch(e) {}

                    rowsHtml += `
                        <tr style="border-bottom: 1px solid var(--line); font-size: 13px;">
                            <td style="padding: 12px; font-weight: 600;">${escapeHtml(entry.client_name)}</td>
                            <td style="padding: 12px; color: var(--ink);">${escapeHtml(displayDt)}</td>
                            <td style="padding: 12px; color: var(--ink); font-weight: 500;">${escapeHtml(displayMonth)}</td>
                            <td style="padding: 12px;"><span class="pill">${escapeHtml(cleanType)}</span></td>
                            <td style="padding: 12px;"><span class="pill" style="background: #e2e8f0; color: #334155;">${escapeHtml(cleanMode)}</span></td>
                            <td style="padding: 12px; text-align: right; font-weight: 700; color: var(--primary-dark);">${entry.collection_amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td style="padding: 12px; color: var(--muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(entry.remarks || '')}">${escapeHtml(entry.remarks || '—')}</td>
                            <td style="padding: 12px; text-align: center;">
                                <button type="button" class="btn-small danger remove-batch-item" data-index="${index}" style="padding: 4px 8px; font-size: 11px;">Remove</button>
                            </td>
                        </tr>
                    `;
                });
                $('#batch_tbody').html(rowsHtml);
            }

            // Remove item from batch
            $(document).on('click', '.remove-batch-item', function() {
                const idx = parseInt($(this).data('index'));
                batchEntries.splice(idx, 1);
                refreshBatchUI();
            });

            if ($('#client_id').val()) {
                updateRecentEntries();
            }
            if ($('#client_id').val() && $('#collection_month_select').val()) {
                updateMetrics();
            }
        });
    </script>
@endpush
