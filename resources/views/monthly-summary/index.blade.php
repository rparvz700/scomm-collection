@extends('layouts.app')

@section('title', 'Monthly Summary | SCOMM Collection')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.6.0/dist/handsontable.full.min.css">
    <style>
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
            <p>Edit monthly snapshots in an Excel-style grid with typed cells, validation, filters, sorting, copy/paste, fill handle, and CSV export.</p>
        </div>
    </section>

    <section class="sheet-toolbar">
        <div class="sheet-actions">
            <button id="saveSheet" class="button primary" type="button">Save changes</button>
            <button id="validateSheet" class="button" type="button">Validate</button>
            <button id="addRow" class="button" type="button">Add row</button>
            <button id="exportCsv" class="button" type="button">Export CSV</button>
        </div>
        <div id="sheetStatus" class="sheet-status">Ready</div>
    </section>

    <section class="sheet-wrap">
        <div id="monthlySummaryGrid"></div>
    </section>

    <p class="manual-save-note">
        Required fields are Client ID and Summary Month. Numeric and date cells are validated in the grid and again on save.
    </p>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.0/dist/handsontable.full.min.js"></script>
    <script>
        const clients = @json($clients);
        const serverColumns = @json($columns);
        const initialRows = @json($summaries->map(function ($summary) {
            $row = $summary->toArray();
            $row['client_name'] = $summary->client?->client_name;
            $row['summary_month'] = optional($summary->summary_month)->format('Y-m-d');
            $row['client_payment_commitment_date'] = optional($summary->client_payment_commitment_date)->format('Y-m-d');

            return $row;
        })->values());
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

        const moneyValidator = (value, callback) => {
            callback(value === null || value === '' || (!Number.isNaN(Number(value)) && Number(value) >= 0));
        };

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
                readOnly: Boolean(column.readOnly),
                allowInvalid: false,
            };

            if (column.key === 'client_id') {
                return {
                    ...base,
                    type: 'dropdown',
                    source: clients.map((client) => String(client.client_id)),
                    strict: true,
                    validator: requiredClientValidator,
                    renderer(instance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                        td.textContent = value ? `${value} - ${clientMap[String(value)] ?? 'Unknown client'}` : '';
                    },
                };
            }

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

        const setStatus = (message, tone = 'muted') => {
            status.textContent = message;
            status.style.color = tone === 'error' ? '#b42318' : tone === 'success' ? '#166534' : 'var(--muted)';
        };

        const hot = new Handsontable(container, {
            data: initialRows,
            columns: columnSettings,
            colHeaders: serverColumns.map((column) => column.label),
            rowHeaders: true,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            licenseKey: 'non-commercial-and-evaluation',
            minSpareRows: 1,
            manualColumnResize: true,
            manualRowResize: true,
            manualColumnMove: true,
            manualRowMove: true,
            columnSorting: true,
            multiColumnSorting: true,
            filters: true,
            dropdownMenu: true,
            contextMenu: true,
            copyPaste: true,
            fillHandle: true,
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

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (prop === 'client_id' && oldValue !== newValue) {
                        hot.setDataAtRowProp(row, 'client_name', clientNameById[String(newValue)] ?? null, 'clientNameSync');
                    }
                });

                if (source !== 'clientNameSync') {
                    dirty = true;
                    setStatus('Unsaved changes');
                }
            },
            afterValidate(isValid, value, row, prop) {
                const key = `${row}:${prop}`;

                if (isValid) {
                    invalidCells.delete(key);
                } else {
                    invalidCells.set(key, {
                        row: row + 1,
                        prop,
                        label: serverColumns.find((column) => column.key === prop)?.label ?? prop,
                    });
                }

                if (!isValid) {
                    const first = [...invalidCells.values()][0];
                    setStatus(`Validation failed at row ${first.row}, ${first.label}.`, 'error');
                }
            },
        });

        const validateGrid = () => new Promise((resolve) => {
            hot.validateCells((valid) => resolve(valid));
        });

        const rowsForSave = () => hot.getSourceData()
            .map((row, index) => ({ ...row, __hotRow: index }))
            .filter((row) => isRealSheetRow(hot, row.__hotRow));

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

        document.getElementById('validateSheet').addEventListener('click', async () => {
            const valid = await validateGrid();
            if (valid) {
                invalidCells.clear();
                setStatus('Grid validation passed.', 'success');
                return;
            }

            const first = [...invalidCells.values()][0];
            setStatus(first ? `Validation failed at row ${first.row}, ${first.label}.` : 'Validation failed. Fix red cells before saving.', 'error');
        });

        document.getElementById('addRow').addEventListener('click', () => {
            hot.alter('insert_row_below', hot.countRows() - 1, 1);
            dirty = true;
            setStatus('New row added');
        });

        document.getElementById('exportCsv').addEventListener('click', () => {
            hot.getPlugin('exportFile').downloadFile('csv', {
                bom: true,
                columnHeaders: true,
                rowHeaders: false,
                filename: 'monthly-summary-[YYYY]-[MM]-[DD]',
            });
        });

        document.getElementById('saveSheet').addEventListener('click', async () => {
            const valid = await validateGrid();

            if (!valid) {
                const first = [...invalidCells.values()][0];
                setStatus(first ? `Validation failed at row ${first.row}, ${first.label}.` : 'Validation failed. Fix red cells before saving.', 'error');
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
                body: JSON.stringify({ rows: payloadRows }),
            })
                .then(async (response) => {
                    const payload = await response.json();

                    if (!response.ok) {
                        throw payload;
                    }

                    hot.loadData(payload.data ?? rowsForSave());
                    dirty = false;
                    setStatus(payload.message ?? 'Monthly summary saved.', 'success');
                })
                .catch((error) => {
                    showServerErrors(error.errors, payloadRows);
                    console.error(error);
                });
        });

        window.addEventListener('beforeunload', (event) => {
            if (!dirty) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });
    </script>
@endpush
