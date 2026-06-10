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
        /* Glowing animation for the bar fill */
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

    <section class="grid two">
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

                <div class="field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks">{{ old('remarks') }}</textarea>
                    @error('remarks')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button class="button primary" type="submit">Save collection</button>
            </form>
        </article>

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
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($collections as $collection)
                                <tr>
                                    <td>{{ $collection->client->client_name ?? 'Unknown client' }}</td>
                                    <td><span class="pill">{{ str_replace('_', ' ', $collection->collection_type) }}</span></td>
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

            // Trigger update metrics and recent entries when client selection changes (Select2 triggers change event)
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

                console.log('updateMetrics triggered. Client ID:', clientId, 'Month:', month);

                if (!clientId || !month) {
                    $('#metrics_visualizer').hide();
                    currentMetrics = { total_latest_os: 0, total_mrc: 0 };
                    return;
                }

                try {
                    const response = await fetch(`{{ route('collection-entry.client-metrics') }}?client_id=${clientId}&month=${month}`);
                    currentMetrics = await response.json();
                    console.log('Fetched Metrics Response:', currentMetrics);
                    
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

                console.log('renderBars called. Entered amount:', amount, 'OS:', os, 'MRC:', mrc);

                // 1. OS Bar calculation:
                // Red background represents unpaid outstanding, green fill represents paid/covered portion
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
                        // Light red container background representing unpaid OS
                        $('#os_bar_container').css('background-color', '#fee2e2').css('border-color', '#fecaca');
                    }
                } else {
                    $('#os_bar_fill')
                        .css('width', '0%')
                        .removeClass('bar-glow-primary bar-glow-success');
                    $('#os_bar_label').text('0.00 / 0.00');
                    $('#os_bar_container').css('background-color', '#e2e8f0').css('border-color', 'var(--line)');
                }

                // 2. MRC Bar calculation:
                // Filled portion (teal) shows how much of the monthly recurring charge is covered
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

            // Fetch recent collection entries for selected client (or all clients) via AJAX
            async function updateRecentEntries() {
                const clientId = $('#client_id').val();
                const container = $('#recent_entries_container');
                const subtitle = $('#recent_entries_subtitle');

                if (clientId) {
                    subtitle.text('Selected Client');
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
                        tbodyHtml += `
                            <tr>
                                <td>${escapeHtml(item.client_name)}</td>
                                <td><span class="pill">${escapeHtml(item.collection_type)}</span></td>
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

            // Trigger initial metric fetch if client/month already filled (e.g. from old input)
            if ($('#client_id').val()) {
                updateRecentEntries();
            }
            if ($('#client_id').val() && $('#collection_month_select').val()) {
                updateMetrics();
            }
        });
    </script>
@endpush
