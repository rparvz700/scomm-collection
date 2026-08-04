@extends('layouts.app')

@section('title', 'Monthly Summary | SCOMM Collection')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/handsontable.full.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Select2 overrides */
        .select2-container {
            width: 100% !important;
            min-width: 200px;
        }
        .select2-container--default .select2-selection--single {
            border: 1px solid var(--line);
            border-radius: 6px;
            height: 36px;
            display: flex;
            align-items: center;
            padding: 0 4px;
            background: #ffffff;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            color: var(--ink);
            font-size: 11.5px !important;
            font-weight: 600;
        }
        .select2-dropdown {
            border-color: var(--line);
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            width: auto !important;
            min-width: 340px !important;
            max-width: 550px !important;
            font-size: 11.5px !important;
        }
        .select2-results__option {
            font-size: 11.5px !important;
            padding: 5px 8px !important;
        }
        .select2-results__group {
            font-size: 11px !important;
            font-weight: 800 !important;
            color: var(--muted) !important;
            background: #f8fafc;
            padding: 4px 8px !important;
        }
        .select2-search__field {
            font-size: 11.5px !important;
            padding: 4px 6px !important;
        }
        #rowSearch {
            font-size: 11.5px !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary);
        }
        .sheet-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .sheet-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Handsontable Sticky Column & Tooltip Overrides */
        .handsontable td, .handsontable th {
            max-width: 200px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* Custom Top Scrollbar Styling */
        #topScrollContainer {
            height: 18px !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
        }
        #topScrollContainer::-webkit-scrollbar {
            height: 10px;
        }
        #topScrollContainer::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        #topScrollContainer::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        #topScrollContainer::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sheet-status {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .sheet-wrap {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        #monthlySummaryGrid {
            width: 100%;
            height: calc(100vh - 270px);
            min-height: 520px;
        }

        .htInvalid {
            background: #fee2e2 !important;
            color: #7f1d1d !important;
        }

        .manual-save-note {
            margin-top: 12px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
@endpush

@section('content')
    <section class="page-heading">
        <div>
            <h1>Monthly summary</h1>
            <p>View monthly snapshots in a read-only Excel-style grid with filters, sorting, search, and CSV export.</p>
        </div>
    </section>

    @if(($pendingActiveCount ?? 0) > 0)
        <div id="pendingClientNotification" style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 12px 18px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #1e40af; font-size: 13.5px; font-weight: 600; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px; line-height: 1;">🔔</span>
                <span><strong>Action Required:</strong> <span id="pendingCountText">{{ $pendingActiveCount }} new active client(s)</span> pending to be added into this month's summary.</span>
            </div>
            <button id="addPendingRowBtn" class="button primary" style="background: #2563eb; border-color: #2563eb; color: #ffffff; padding: 6px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;" type="button">
                <span>➕</span> Add {{ $pendingActiveCount }} Pending Client Rows
            </button>
        </div>
    @endif

    <section class="sheet-toolbar" style="flex-wrap: wrap; gap: 20px;">
        <div class="sheet-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button id="saveSheet" class="button" type="button" style="display: inline-flex; background: #166534; border-color: #166534; color: #ffffff; font-weight: 700;">💾 Save Changes</button>
            <button id="exportCsv" class="button" type="button">Export CSV</button>
            
            <div style="display: flex; align-items: center; gap: 8px; margin-left: 8px;">
                <label for="monthSelect" style="margin-bottom: 0; font-weight: 800; white-space: nowrap; font-size: 13px; color: var(--muted);">Selected Month:</label>
                <input id="monthSelect" type="month" value="{{ $selectedMonth }}" style="width: 150px; height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 0 12px; font-size: 14px; outline: none; background: #ffffff; cursor: pointer; font-weight: 800; color: var(--ink);">
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
            <!-- Column Finder -->
            <div style="display: flex; align-items: center; gap: 6px; min-width: 240px;">
                <label for="columnSearch" style="margin-bottom: 0; font-weight: 700; white-space: nowrap; font-size: 12px;">Find Column:</label>
                <select id="columnSearch" style="width: 60%;">
                    <option value="">Select column...</option>
                </select>
            </div>
            
            <!-- Global Row Filter -->
            <div style="display: flex; align-items: center; gap: 6px; min-width: 220px;">
                <label for="rowSearch" style="margin-bottom: 0; font-weight: 700; white-space: nowrap; font-size: 12px;">Search Rows:</label>
                <input id="rowSearch" type="text" placeholder="Type to filter..." style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 10px; font-size: 11.5px; outline: none; background: #ffffff;">
            </div>
        </div>

        <div id="sheetStatus" class="sheet-status">Ready</div>
    </section>

    <section class="sheet-wrap" style="position: relative;">
        <div id="topScrollContainer" style="overflow-x: auto; overflow-y: hidden; height: 18px; width: 100%; margin-bottom: 4px; display: none; background: rgba(0,0,0,0.02); border-radius: 4px;">
            <div id="topScrollContent" style="height: 1px;"></div>
        </div>
        <div id="monthlySummaryGrid" style="width: 100%; height: 100%;"></div>
        
        <div id="gridLoader" style="display: flex; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.7); z-index: 1000; align-items: center; justify-content: center; transition: opacity 0.3s ease; border-radius: 8px;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #166534; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                <div style="font-weight: 700; color: #166534; font-size: 14px;">Loading sheet data...</div>
            </div>
        </div>
    </section>

    <p class="manual-save-note">
        This table is editable for your assigned clients (marked in green). Use the notification banner above to insert new active client rows.
    </p>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/handsontable.full.min.js') }}"></script>
    <script>
        const clients = @json($clients);
        const serverColumns = @json($columns);
        const currentUser = {!! json_encode(auth()->user() ? [
            'id' => auth()->user()->id,
            'is_admin' => auth()->user()->hasRole('admin'),
            'is_supervisor' => auth()->user()->hasRole('collection_supervisor'),
            'is_hod' => auth()->user()->hasRole('collection_hod'),
            'is_collection_kam' => auth()->user()->hasRole('collection_kam'),
            'is_nttn_billing_kam' => auth()->user()->hasRole('nttn_billing_kam'),
            'is_iig_itc_billing_kam' => auth()->user()->hasRole('iig_itc_billing_kam'),
            'is_sm_kam' => auth()->user()->hasRole('sm_kam'),
        ] : null) !!};

        const initialRows = @json($summaries->map(function ($summary) {
            $row = $summary->toArray();
            $row['opus_id'] = $summary->client?->opus_id;
            $row['client_name'] = $summary->client?->client_name;
            $row['collection_kam_id'] = $summary->client?->collection_kam_id;
            $row['nttn_billing_kam_id'] = $summary->client?->nttn_billing_kam_id;
            $row['iig_itc_billing_kam_id'] = $summary->client?->iig_itc_billing_kam_id;
            $row['sm_kam_id'] = $summary->client?->sm_kam_id;
            $row['summary_month'] = optional($summary->summary_month)->format('Y-m-d');
            $row['client_payment_commitment_date'] = optional($summary->client_payment_commitment_date)->format('Y-m-d');

            return $row;
        })->values());

        let loaderTimeout = null;

        const showLoader = (text = 'Loading sheet data...') => {
            const loader = document.getElementById('gridLoader');
            if (loader) {
                if (loaderTimeout) {
                    clearTimeout(loaderTimeout);
                    loaderTimeout = null;
                }
                const textEl = loader.querySelector('div > div:last-child');
                if (textEl) textEl.textContent = text;
                loader.style.display = 'flex';
                loader.offsetHeight; // force reflow
                loader.style.opacity = '1';
            }
        };

        const hideLoader = () => {
            const loader = document.getElementById('gridLoader');
            if (loader) {
                loader.style.opacity = '0';
                if (loaderTimeout) {
                    clearTimeout(loaderTimeout);
                }
                loaderTimeout = setTimeout(() => {
                    loader.style.display = 'none';
                    loaderTimeout = null;
                }, 300);
            }
        };

        window.addEventListener('load', hideLoader);

        function isRowEditable(rowData) {
            if (!currentUser) return false;
            // Admin, Supervisor, and HOD can edit anything
            if (currentUser.is_admin || currentUser.is_supervisor || currentUser.is_hod) {
                return true;
            }

            // Completely untagged clients can be edited by any KAM
            const isUntagged = !rowData.collection_kam_id && 
                               !rowData.nttn_billing_kam_id && 
                               !rowData.iig_itc_billing_kam_id && 
                               !rowData.sm_kam_id;
            if (isUntagged) {
                return true;
            }

            // Otherwise, only editable if mapped specifically to this user
            let isTagged = false;
            if (currentUser.is_collection_kam && rowData.collection_kam_id == currentUser.id) {
                isTagged = true;
            }
            if (currentUser.is_nttn_billing_kam && rowData.nttn_billing_kam_id == currentUser.id) {
                isTagged = true;
            }
            if (currentUser.is_iig_itc_billing_kam && rowData.iig_itc_billing_kam_id == currentUser.id) {
                isTagged = true;
            }
            if (currentUser.is_sm_kam && rowData.sm_kam_id == currentUser.id) {
                isTagged = true;
            }

            return isTagged;
        }

        const sheetDebug = false;

        const clientMap = Object.fromEntries(clients.map((client) => [
            String(client.client_id),
            `${client.client_name} - ${client.opus_id}`,
        ]));

        const clientNameById = Object.fromEntries(clients.map((client) => [
            String(client.client_id),
            client.client_name,
        ]));

        const requiredColumns = ['client_id', 'summary_month'];
        const invalidCells = new Map();

        const isEmptyCellValue = (value) => value === null || value === undefined || value === '';

        const visualRowValues = (instance, rowIndex) => Object.fromEntries(
            serverColumns.map((column) => [column.key, instance.getDataAtRowProp(rowIndex, column.key)])
        );

        const isBlankSheetRow = (instance, rowIndex) => {
            const row = visualRowValues(instance, rowIndex);
            const editableEntries = Object.entries(row)
                .filter(([key]) => !['monthly_summary_id', 'client_id', 'client_name', 'summary_month'].includes(key));
            const result = editableEntries.every(([, value]) => isEmptyCellValue(value));

            if (sheetDebug && rowIndex >= initialRows.length) {
                console.log('[MonthlySummary blank check]', {
                    rowIndex,
                    visualRow: rowIndex + 1,
                    row,
                    editableEntries,
                    result,
                });
            }

            return result;
        };

        const isRealSheetRow = (instance, rowIndex) => {
            const row = visualRowValues(instance, rowIndex);
            const requiredHasValue = requiredColumns.some((key) => !isEmptyCellValue(row[key]));
            const otherHasValue = Object.entries(row)
                .filter(([key]) => !['monthly_summary_id', 'client_id', 'client_name', 'summary_month'].includes(key))
                .some(([, value]) => !isEmptyCellValue(value));
            const result = requiredHasValue || otherHasValue;

            if (sheetDebug && rowIndex >= initialRows.length) {
                console.log('[MonthlySummary real row check]', {
                    rowIndex,
                    visualRow: rowIndex + 1,
                    requiredValues: Object.fromEntries(requiredColumns.map((key) => [key, row[key]])),
                    requiredHasValue,
                    otherHasValue,
                    row,
                    result,
                });
            }

            return result;
        };

        function moneyValidator(value, callback) {
            if (value === null || value === '') {
                callback(true);
                return;
            }
            const num = Number(value);
            if (Number.isNaN(num)) {
                callback(false);
                return;
            }
            callback(true);
        }

        function requiredDateValidator(value, callback) {
            if (!isRealSheetRow(this.instance, this.row)) {
                if (sheetDebug) {
                    console.log('[MonthlySummary required date skipped]', {
                        row: this.row + 1,
                        prop: this.prop,
                        value,
                        sourceRow: this.instance.getSourceDataAtRow(this.row),
                    });
                }
                callback(true);
                return;
            }

            const valid = typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);

            if (sheetDebug && !valid) {
                console.log('[MonthlySummary required date failed]', {
                    row: this.row + 1,
                    prop: this.prop,
                    value,
                    sourceRow: this.instance.getSourceDataAtRow(this.row),
                });
            }

            callback(valid);
        }

        const optionalDateValidator = (value, callback) => {
            callback(value === null || value === '' || (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)));
        };

        function requiredClientValidator(value, callback) {
            if (!isRealSheetRow(this.instance, this.row)) {
                if (sheetDebug) {
                    console.log('[MonthlySummary required client skipped]', {
                        row: this.row + 1,
                        prop: this.prop,
                        value,
                        sourceRow: this.instance.getSourceDataAtRow(this.row),
                    });
                }
                callback(true);
                return;
            }

            const valid = Boolean(clientMap[String(value)]);

            if (sheetDebug && !valid) {
                console.log('[MonthlySummary required client failed]', {
                    row: this.row + 1,
                    prop: this.prop,
                    value,
                    sourceRow: this.instance.getSourceDataAtRow(this.row),
                    clientMap,
                });
            }

            callback(valid);
        }

        const columnSettings = serverColumns.map((column) => {
            const base = {
                data: column.key,
                title: column.label,
                readOnly: column.readOnly ?? false,
                allowInvalid: false,
            };

            if (column.type === 'money' || column.type === 'rating') {
                return {
                    ...base,
                    type: 'numeric',
                    numericFormat: { pattern: '0,0.00' },
                    validator: moneyValidator,
                };
            }

            if (column.type === 'date') {
                return {
                    ...base,
                    type: 'date',
                    dateFormat: 'YYYY-MM-DD',
                    correctFormat: true,
                    validator: column.required ? requiredDateValidator : optionalDateValidator,
                };
            }

            return base;
        });

        const container = document.getElementById('monthlySummaryGrid');
        const status = document.getElementById('sheetStatus');
        let dirty = false;
        const dirtyRows = new Set();
        const dirtyCells = new Set();
        const savedCells = new Set();

        const setStatus = (message, tone = 'muted') => {
            status.textContent = message;
            status.style.color = tone === 'error' ? '#b42318' : tone === 'success' ? '#166534' : 'var(--muted)';
        };

        const initialRowsLength = initialRows.length;
        const pendingActiveClients = @json($pendingActiveClients);

        const hot = new Handsontable(container, {
            data: initialRows,
            columns: columnSettings,
            colHeaders: serverColumns.map((column) => column.label),
            rowHeaders: true,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: 2,
            maxHeaderWidth: 200,
            minSpareRows: 0,
            cells(row, col, prop) {
                const cellProperties = {};
                if (prop === 'opus_id' || prop === 'client_name') {
                    cellProperties.readOnly = true;
                } else {
                    const rowData = this.instance.getSourceDataAtRow(row);
                    if (rowData && !isRowEditable(rowData)) {
                        cellProperties.readOnly = true;
                    }
                }
                cellProperties.renderer = function(instance, td, r, c, p, value, cellProps) {
                    if (cellProps.type === 'numeric') {
                        Handsontable.renderers.NumericRenderer.apply(this, arguments);
                    } else if (cellProps.type === 'dropdown' || cellProps.type === 'autocomplete') {
                        Handsontable.renderers.AutocompleteRenderer.apply(this, arguments);
                    } else if (cellProps.type === 'date') {
                        Handsontable.renderers.DateRenderer.apply(this, arguments);
                    } else {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                    }

                    td.style.maxWidth = '200px';
                    td.style.overflow = 'hidden';
                    td.style.textOverflow = 'ellipsis';
                    td.style.whiteSpace = 'nowrap';

                    const cellKey = `${r}:${p}`;
                    const numValue = Number(value);

                    if (value !== null && value !== undefined && value !== '' && !Number.isNaN(numValue) && numValue < 0) {
                        // Negative values are colored in RED
                        td.style.backgroundColor = '#fee2e2';
                        td.style.color = '#991b1b';
                        td.style.fontWeight = '700';
                    } else if (dirtyCells.has(cellKey)) {
                        // Edited cells are colored in BLUE (deeper)
                        td.style.backgroundColor = '#bfdbfe';
                        td.style.color = '#1e3a8a';
                        td.style.fontWeight = '700';
                    } else if (savedCells.has(cellKey)) {
                        // Saved cells are colored in GREEN
                        td.style.backgroundColor = '#dcfce7';
                        td.style.color = '#166534';
                        td.style.fontWeight = '700';
                    } else if (!cellProps.readOnly) {
                        // Editable cells (default state - soft yellow-cream)
                        td.style.backgroundColor = '#fffbeb';
                        td.style.color = '#111827';
                        td.style.fontWeight = '500';
                    } else {
                        // Read-only cells
                        td.style.backgroundColor = '';
                        td.style.color = '';
                        td.style.fontWeight = '';
                    }

                    if (value !== null && value !== undefined && value !== '') {
                        td.setAttribute('title', String(value));
                    } else {
                        td.removeAttribute('title');
                    }
                };
                return cellProperties;
            },
            afterGetColHeader(col, TH) {
                if (TH) {
                    const headerNode = TH.querySelector('.colHeader');
                    if (headerNode) {
                        TH.setAttribute('title', headerNode.textContent);
                    }
                }
            },
            manualColumnResize: true,
            manualRowResize: true,
            manualColumnMove: true,
            manualRowMove: true,
            columnSorting: true,
            multiColumnSorting: true,
            filters: true,
            dropdownMenu: true,
            contextMenu: false,
            copyPaste: true,
            fillHandle: false,
            comments: true,
            hiddenColumns: { columns: [], indicators: true },
            hiddenRows: { rows: [], indicators: true },
            autoWrapRow: true,
            autoWrapCol: true,
            wordWrap: false,
            persistentState: true,
            search: true,
            exportFile: true,
            id: 'monthly-summary-sheet',
            outsideClickDeselects: false,
            invalidCellClassName: 'htInvalid',
            afterChange(changes, source) {
                if (!changes || source === 'loadData') {
                    return;
                }

                if (source === 'formulaSync' || source === 'clientNameSync') {
                    changes.forEach(([row, prop, oldValue, newValue]) => {
                        if (oldValue !== newValue) {
                            dirtyRows.add(row);
                            const key = `${row}:${prop}`;
                            dirtyCells.add(key);
                            savedCells.delete(key);
                        }
                    });
                    return;
                }

                const components = [
                    'postpaid_nttn', 'postpaid_iig_nttn', 'postpaid_iig', 'postpaid_itc', 'postpaid_nix',
                    'prepaid_nttn', 'prepaid_iig_nttn', 'prepaid_iig', 'prepaid_itc', 'prepaid_nix'
                ];

                const rowsToRecalculate = new Set();
                const statusUpdates = [];
                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue !== newValue) {
                        if (prop === 'client_id') {
                            hot.setDataAtRowProp(row, 'client_name', clientNameById[String(newValue)] ?? null, 'clientNameSync');
                        }
                        dirtyRows.add(row);
                        
                        const key = `${row}:${prop}`;
                        dirtyCells.add(key);
                        savedCells.delete(key);
                        rowsToRecalculate.add(row);

                        if (prop === 'sales_review_remarks' && newValue && String(newValue).trim() !== '') {
                            const currentStatus = hot.getDataAtRowProp(row, 'sales_review_status');
                            if (!currentStatus || currentStatus === 'Pending') {
                                statusUpdates.push([row, 'sales_review_status', 'Completed']);
                            }
                        }
                    }
                });

                if (rowsToRecalculate.size > 0) {
                    const formulaUpdates = [];

                    rowsToRecalculate.forEach(row => {
                        // 1. Recalculate component-wise Opening OS = MRC + Backlog
                        components.forEach(comp => {
                            const mrcCol = 'mrc_' + comp;
                            const backlogCol = 'backlog_' + comp;
                            const openingCol = 'opening_os_' + comp;

                            const mrcVal = parseFloat(hot.getDataAtRowProp(row, mrcCol)) || 0;
                            const backlogVal = parseFloat(hot.getDataAtRowProp(row, backlogCol)) || 0;
                            const newOpeningVal = mrcVal + backlogVal;

                            const oldOpeningVal = parseFloat(hot.getDataAtRowProp(row, openingCol)) || 0;
                            if (Math.abs(newOpeningVal - oldOpeningVal) > 0.005) {
                                formulaUpdates.push([row, openingCol, newOpeningVal]);
                            }

                            // 2. Recalculate component-wise Latest OS = Opening OS - Collection
                            const collectionCol = 'collection_' + comp;
                            const latestCol = 'latest_os_' + comp;

                            const collectionVal = parseFloat(hot.getDataAtRowProp(row, collectionCol)) || 0;
                            const newLatestVal = newOpeningVal - collectionVal;

                            const oldLatestVal = parseFloat(hot.getDataAtRowProp(row, latestCol)) || 0;
                            if (Math.abs(newLatestVal - oldLatestVal) > 0.005) {
                                formulaUpdates.push([row, latestCol, newLatestVal]);
                            }
                        });

                        const getCurrentVal = (propName) => {
                            const update = formulaUpdates.find(u => u[0] === row && u[1] === propName);
                            if (update) return update[2];
                            return parseFloat(hot.getDataAtRowProp(row, propName)) || 0;
                        };

                        const sumCurrentVals = (prefix, suffixes) => {
                            return suffixes.reduce((sum, s) => sum + getCurrentVal(prefix + s), 0);
                        };

                        const postpaidSuffixes = ['postpaid_nttn', 'postpaid_iig_nttn', 'postpaid_iig', 'postpaid_itc', 'postpaid_nix'];
                        const prepaidSuffixes = ['prepaid_nttn', 'prepaid_iig_nttn', 'prepaid_iig', 'prepaid_itc', 'prepaid_nix'];

                        // Calculate Total Opening OS postpaid/prepaid/total
                        const totalOpeningOsPostpaid = sumCurrentVals('opening_os_', postpaidSuffixes);
                        const totalOpeningOsPrepaid = sumCurrentVals('opening_os_', prepaidSuffixes);
                        const totalOpeningOs = totalOpeningOsPostpaid + totalOpeningOsPrepaid;

                        formulaUpdates.push([row, 'total_opening_os_postpaid', totalOpeningOsPostpaid]);
                        formulaUpdates.push([row, 'total_opening_os_prepaid', totalOpeningOsPrepaid]);
                        formulaUpdates.push([row, 'total_opening_os', totalOpeningOs]);

                        // Calculate Total MRC postpaid/prepaid/total
                        const totalMrcPostpaid = sumCurrentVals('mrc_', postpaidSuffixes);
                        const totalMrcPrepaid = sumCurrentVals('mrc_', prepaidSuffixes);
                        const totalMrc = totalMrcPostpaid + totalMrcPrepaid;

                        formulaUpdates.push([row, 'total_mrc_postpaid', totalMrcPostpaid]);
                        formulaUpdates.push([row, 'total_mrc_prepaid', totalMrcPrepaid]);
                        formulaUpdates.push([row, 'total_mrc', totalMrc]);

                        // Calculate Net Backlog postpaid/prepaid/total
                        const netBacklogPostpaid = sumCurrentVals('backlog_', postpaidSuffixes);
                        const netBacklogPrepaid = sumCurrentVals('backlog_', prepaidSuffixes);
                        const netBacklogTotal = netBacklogPostpaid + netBacklogPrepaid;

                        formulaUpdates.push([row, 'net_backlog_postpaid', netBacklogPostpaid]);
                        formulaUpdates.push([row, 'net_backlog_prepaid', netBacklogPrepaid]);
                        formulaUpdates.push([row, 'net_backlog_total', netBacklogTotal]);

                        // Recalculate shortfall/backlog collection allocations (LIFO)
                        const totalCollection = parseFloat(hot.getDataAtRowProp(row, 'total_collection')) || 0;
                        const collectionMrc = Math.min(totalCollection, totalMrc);
                        const collectionBacklog = Math.max(0.00, totalCollection - totalMrc);
                        const mrcShortfall = Math.max(0.00, totalMrc - collectionMrc);
                        const backlogShortfall = Math.max(0.00, netBacklogTotal - collectionBacklog);

                        formulaUpdates.push([row, 'collection_mrc', collectionMrc]);
                        formulaUpdates.push([row, 'collection_backlog', collectionBacklog]);
                        formulaUpdates.push([row, 'mrc_shortfall', mrcShortfall]);
                        formulaUpdates.push([row, 'backlog_shortfall', backlogShortfall]);
                        formulaUpdates.push([row, 'total_shortfall_maturity', mrcShortfall]);

                        // Calculate Total Latest OS postpaid/prepaid/total
                        const latestOsPostpaid = sumCurrentVals('latest_os_', postpaidSuffixes);
                        const latestOsPrepaid = sumCurrentVals('latest_os_', prepaidSuffixes);
                        const totalLatestOs = latestOsPostpaid + latestOsPrepaid;

                        formulaUpdates.push([row, 'latest_os_balance_postpaid', latestOsPostpaid]);
                        formulaUpdates.push([row, 'latest_os_balance_prepaid', latestOsPrepaid]);
                        formulaUpdates.push([row, 'total_latest_os', totalLatestOs]);

                        // 4. Calculate Latest CR = Total Latest OS / Total MRC
                        const latestCr = totalMrc > 0 ? (totalLatestOs / totalMrc) : 0.00;
                        formulaUpdates.push([row, 'latest_cr', parseFloat(latestCr.toFixed(2))]);

                        // 5. Calculate Latest Rating Category
                        let ratingCategory = 'Unknown';
                        const cr = latestCr;
                        if (cr <= 1.50) ratingCategory = 'Best';
                        else if (cr <= 2.00) ratingCategory = 'Good';
                        else if (cr <= 2.50) ratingCategory = 'Moderate';
                        else if (cr <= 2.99) ratingCategory = 'Risky';
                        else if (cr <= 3.49) ratingCategory = 'High Risky';
                        else ratingCategory = 'Most Risky';

                        formulaUpdates.push([row, 'latest_rating_category', ratingCategory]);
                    });

                    // Add status updates
                    statusUpdates.forEach(update => {
                        formulaUpdates.push(update);
                    });

                    if (formulaUpdates.length > 0) {
                        hot.setDataAtRowProp(formulaUpdates, 'formulaSync');
                    }
                }

                if (source !== 'clientNameSync' && source !== 'formulaSync') {
                    dirty = true;
                    setStatus('Unsaved changes');
                }
                hot.render();
            },
            afterValidate(isValid, value, row, prop) {
                const key = `${row}:${prop}`;

                if (isValid) {
                    invalidCells.delete(key);
                } else {
                    const colConfig = serverColumns.find((c) => c.key === prop);
                    const label = colConfig?.label ?? prop;
                    let reason = "invalid value";
                    
                    if (colConfig) {
                        if (colConfig.type === 'money' || colConfig.type === 'numeric') {
                            if (value === undefined || value === null || String(value).trim() === '') {
                                reason = "cannot be empty";
                            } else if (Number.isNaN(Number(value))) {
                                reason = `must be a valid number (found "${value}")`;
                            }
                        } else if (colConfig.type === 'date') {
                            if (colConfig.required && (value === undefined || value === null || String(value).trim() === '')) {
                                reason = "cannot be empty";
                            } else {
                                reason = `must be a valid date in YYYY-MM-DD format (found "${value}")`;
                            }
                        }
                    }

                    invalidCells.set(key, {
                        row: row + 1,
                        prop,
                        label,
                        reason,
                    });
                }

                if (invalidCells.size > 0) {
                    const first = [...invalidCells.values()][0];
                    setStatus(`Validation failed at row ${first.row}, ${first.label}: ${first.reason}.`, 'error');
                } else {
                    if (dirty) {
                        setStatus('Unsaved changes');
                    } else {
                        setStatus('Ready');
                    }
                }
            },
        });

        $(document).ready(function() {
            // Populate Column Finder select options (Editable columns grouped at top)
            const colSelect = $('#columnSearch');
            colSelect.empty().append('<option value=""></option>');

            const editableGroup = $('<optgroup label="⚡ Editable Columns"></optgroup>');
            const readOnlyGroup = $('<optgroup label="🔒 Read-Only Columns"></optgroup>');

            serverColumns.forEach((col) => {
                const isEditable = !col.readOnly;
                const option = new Option((isEditable ? '✏️ ' : '') + col.label, col.key);
                if (isEditable) {
                    editableGroup.append(option);
                } else {
                    readOnlyGroup.append(option);
                }
            });

            if (editableGroup.children().length > 0) {
                colSelect.append(editableGroup);
            }
            if (readOnlyGroup.children().length > 0) {
                colSelect.append(readOnlyGroup);
            }

            // Initialize Select2 on Column Finder
            colSelect.select2({
                placeholder: "Search column...",
                allowClear: true,
                dropdownAutoWidth: true,
                width: '100%'
            });

            // Scroll to column on select
            colSelect.on('change select2:select', function() {
                const colKey = $(this).val();
                if (colKey) {
                    const colIndex = hot.propToCol(colKey);
                    if (colIndex >= 0) {
                        // Scroll to column and highlight first cell
                        hot.selectCell(0, colIndex);
                        hot.scrollViewportTo(0, colIndex);
                    }
                }
            });

            // Global Row Filter using hiddenRows plugin
            const rowSearchInput = document.getElementById('rowSearch');
            rowSearchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const hiddenRowsPlugin = hot.getPlugin('hiddenRows');
                
                if (!query) {
                    // Show all rows
                    const count = hot.countRows();
                    const allRows = Array.from({ length: count }, (_, i) => i);
                    hiddenRowsPlugin.showRows(allRows);
                    hot.render();
                    return;
                }

                const rowsToHide = [];
                const count = hot.countRows();

                for (let r = 0; r < count; r++) {
                    const rowData = hot.getSourceDataAtRow(r);
                    if (!rowData) continue;

                    // Check if any value matches query
                    const matches = Object.values(rowData).some((val) => {
                        if (val === null || val === undefined) return false;
                        return String(val).toLowerCase().includes(query);
                    });

                    if (!matches) {
                        rowsToHide.push(r);
                    }
                }

                // Show all first to reset, then hide non-matching
                const allRows = Array.from({ length: count }, (_, i) => i);
                hiddenRowsPlugin.showRows(allRows);
                hiddenRowsPlugin.hideRows(rowsToHide);
                hot.render();
            });
        });

        const validateGrid = () => new Promise((resolve) => {
            if (dirtyRows.size === 0) {
                resolve(true);
                return;
            }
            hot.validateRows([...dirtyRows], (valid) => resolve(valid));
        });

        const rowsForSave = () => hot.getSourceData()
            .map((row, index) => ({ ...row, __hotRow: index }))
            .filter((row) => isRealSheetRow(hot, row.__hotRow) && dirtyRows.has(row.__hotRow));

        const showServerErrors = (errors = {}, payloadRows = []) => {
            const firstKey = Object.keys(errors)[0];

            if (!firstKey) {
                setStatus('Save failed. Check validation errors.', 'error');
                return;
            }

            const match = firstKey.match(/^rows\.(\d+)\.([^.]+)$/);

            if (!match) {
                setStatus(errors[firstKey][0] ?? 'Save failed. Check validation errors.', 'error');
                return;
            }

            const payloadIndex = Number(match[1]);
            const prop = match[2];
            const visualRow = payloadRows[payloadIndex]?.__hotRow ?? payloadIndex;
            const visualCol = hot.propToCol(prop);
            const label = serverColumns.find((column) => column.key === prop)?.label ?? prop;

            if (visualCol >= 0) {
                hot.setCellMeta(visualRow, visualCol, 'className', 'htInvalid');
                hot.render();
                hot.selectCell(visualRow, visualCol);
            }

            setStatus(`Save failed at row ${visualRow + 1}, ${label}: ${errors[firstKey][0]}`, 'error');
        };

        const validateSheetBtn = document.getElementById('validateSheet');
        if (validateSheetBtn) {
            validateSheetBtn.addEventListener('click', async () => {
                showLoader('Validating cells...');
                const valid = await validateGrid();
                hideLoader();
                if (valid) {
                    invalidCells.clear();
                    setStatus('Grid validation passed.', 'success');
                    return;
                }

                const first = [...invalidCells.values()][0];
                setStatus(first ? `Validation failed at row ${first.row}, ${first.label}: ${first.reason}.` : 'Validation failed. Fix red cells before saving.', 'error');
            });
        }

        const monthSelectEl = document.getElementById('monthSelect');
        if (monthSelectEl) {
            monthSelectEl.addEventListener('change', function() {
                const selectedMonth = this.value;
                if (selectedMonth) {
                    showLoader('Loading month data...');
                    window.location.href = "{{ route('monthly-summary.index') }}?month=" + selectedMonth;
                }
            });
        }

        const triggerAddRow = (prefillData = null) => {
            const targetRow = hot.countRows();
            hot.alter('insert_row_below', targetRow - 1, 1);
            
            const yearMonth = '{{ $selectedMonth }}';
            const parts = yearMonth.split('-');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const lastDay = new Date(year, month, 0).getDate();
            const defaultDate = `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
            
            hot.setDataAtRowProp(targetRow, 'summary_month', defaultDate);

            if (prefillData) {
                if (prefillData.opus_id) hot.setDataAtRowProp(targetRow, 'opus_id', prefillData.opus_id);
                if (prefillData.client_id) hot.setDataAtRowProp(targetRow, 'client_id', prefillData.client_id);
                if (prefillData.client_name) hot.setDataAtRowProp(targetRow, 'client_name', prefillData.client_name);
            }

            dirtyRows.add(targetRow);
            dirty = true;
            setStatus('New editable row added');
            const saveBtn = document.getElementById('saveSheet');
            if (saveBtn) saveBtn.style.display = 'inline-flex';

            hot.scrollViewportTo(targetRow, 0);
            hot.selectCell(targetRow, 0);
        };

        const addPendingRowBtn = document.getElementById('addPendingRowBtn');
        if (addPendingRowBtn) {
            addPendingRowBtn.addEventListener('click', () => {
                if (pendingActiveClients && pendingActiveClients.length > 0) {
                    pendingActiveClients.forEach((pendingClient) => {
                        triggerAddRow(pendingClient);
                    });
                    setStatus(`${pendingActiveClients.length} pending client row(s) added (Editable).`, 'success');
                } else {
                    triggerAddRow();
                }
                const notification = document.getElementById('pendingClientNotification');
                if (notification) {
                    notification.style.opacity = '0.5';
                    notification.style.pointerEvents = 'none';
                }
            });
        }

        const exportCsvBtn = document.getElementById('exportCsv');
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', () => {
                hot.getPlugin('exportFile').downloadFile('csv', {
                    bom: true,
                    columnHeaders: true,
                    rowHeaders: false,
                    filename: 'monthly-summary-[YYYY]-[MM]-[DD]',
                });
            });
        }

        const saveSheetBtn = document.getElementById('saveSheet');
        if (saveSheetBtn) {
            saveSheetBtn.addEventListener('click', () => {
                // Show loader immediately
                saveSheetBtn.disabled = true;
                saveSheetBtn.innerHTML = '<span class="spinner" style="display: inline-block; width: 12px; height: 12px; border: 2px solid #ffffff; border-top-color: transparent; border-radius: 50%; margin-right: 6px; animation: spin 0.8s linear infinite;"></span> Saving...';
                showLoader('Validating and saving changes...');

                // Yield thread to allow browser to render loading UI
                setTimeout(async () => {
                    const valid = await validateGrid();

                    if (!valid) {
                        hideLoader();
                        saveSheetBtn.disabled = false;
                        saveSheetBtn.innerHTML = '💾 Save Changes';

                        const first = [...invalidCells.values()][0];
                        setStatus(first ? `Validation failed at row ${first.row}, ${first.label}: ${first.reason}.` : 'Validation failed. Fix red cells before saving.', 'error');
                        return;
                    }

                    setStatus('Saving...');
                    const payloadRows = rowsForSave();

                    fetch('{{ route('monthly-summary.bulk-update') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            rows: payloadRows,
                            month: '{{ $selectedMonth }}'
                        }),
                    })
                        .then(async (response) => {
                            const payload = await response.json();

                            if (!response.ok) {
                                throw payload;
                            }

                            // Mark only successfully saved cells as saved (green), keep skipped cells as unsaved (blue)
                            const savedClientIds = new Set((payload.saved_client_ids ?? []).map(String));
                            dirtyCells.forEach(key => {
                                const [rowStr, prop] = key.split(':');
                                const rowIndex = Number(rowStr);
                                const rowData = hot.getSourceDataAtRow(rowIndex);
                                if (rowData && savedClientIds.has(String(rowData.client_id))) {
                                    savedCells.add(key);
                                    dirtyCells.delete(key);
                                }
                            });
                            
                            hot.loadData(payload.data ?? rowsForSave());
                            
                            // Re-calculate dirtyRows based on remaining dirtyCells
                            dirtyRows.clear();
                            dirtyCells.forEach(key => {
                                const [rowStr] = key.split(':');
                                dirtyRows.add(Number(rowStr));
                            });
                            
                            dirty = dirtyCells.size > 0;
                            const tone = (payload.skipped_count > 0) ? 'error' : 'success';
                            setStatus(payload.message ?? 'Monthly summary saved.', tone);
                        })
                        .catch((error) => {
                            showServerErrors(error.errors, payloadRows);
                            console.error(error);
                        })
                        .finally(() => {
                            hideLoader();
                            saveSheetBtn.disabled = false;
                            saveSheetBtn.innerHTML = '💾 Save Changes';
                        });
                }, 50);
            });
        }

        window.addEventListener('beforeunload', (event) => {
            if (!dirty) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });

        (function syncTopScrollbar() {
            const topScroll = document.getElementById('topScrollContainer');
            const topContent = document.getElementById('topScrollContent');
            const gridContainer = document.getElementById('monthlySummaryGrid');
            if (!topScroll || !topContent || !gridContainer) return;

            let isSyncingTop = false;
            let isSyncingBottom = false;

            function updateTopScrollbarWidth() {
                const wtHolder = gridContainer.querySelector('.wtHolder');
                if (wtHolder) {
                    const scrollWidth = wtHolder.scrollWidth;
                    const clientWidth = wtHolder.clientWidth;
                    topContent.style.width = scrollWidth + 'px';
                    if (scrollWidth > clientWidth + 2) {
                        topScroll.style.display = 'block';
                    } else {
                        topScroll.style.display = 'none';
                    }
                }
            }

            [50, 150, 300, 600, 1200, 2000].forEach((delay) => {
                setTimeout(updateTopScrollbarWidth, delay);
            });

            topScroll.addEventListener('scroll', function() {
                const wtHolder = gridContainer.querySelector('.wtHolder');
                if (!wtHolder || isSyncingTop) return;
                isSyncingBottom = true;
                wtHolder.scrollLeft = topScroll.scrollLeft;
                requestAnimationFrame(() => isSyncingBottom = false);
            });

            gridContainer.addEventListener('scroll', function() {
                const wtHolder = gridContainer.querySelector('.wtHolder');
                if (!wtHolder || isSyncingBottom) return;
                isSyncingTop = true;
                topScroll.scrollLeft = wtHolder.scrollLeft;
                requestAnimationFrame(() => isSyncingTop = false);
            }, true);

            window.addEventListener('resize', updateTopScrollbarWidth);
            window.addEventListener('load', updateTopScrollbarWidth);
            if (typeof hot !== 'undefined') {
                hot.addHook('afterRender', updateTopScrollbarWidth);
                hot.addHook('afterInit', updateTopScrollbarWidth);
            }
        })();
    </script>
@endpush
