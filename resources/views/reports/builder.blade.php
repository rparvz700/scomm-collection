@extends('layouts.app')

@section('title', 'Custom Report Builder | SCOMM Collection')

@push('styles')
    <style>
        .builder-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 22px;
            align-items: start;
        }

        .sidebar-panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            box-shadow: var(--shadow);
            max-height: 80vh;
            overflow-y: auto;
        }

        .main-panel {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--line);
            background: #f8fafc;
        }

        .card-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: var(--primary-dark);
            text-transform: uppercase;
        }

        .card-body {
            padding: 20px;
        }

        .dictionary-table-group {
            margin-bottom: 12px;
        }

        .dictionary-table-header {
            font-size: 13px;
            font-weight: 800;
            color: #334155;
            padding: 8px 10px;
            background: #f1f5f9;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .dictionary-columns-list {
            padding-left: 10px;
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .column-node {
            padding: 6px 10px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 4px;
            font-size: 12px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .column-node:hover {
            border-color: var(--primary);
            background: #f0fdf4;
            box-shadow: 0 4px 8px rgba(15, 118, 110, 0.1);
        }

        /* Drop Zones */
        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            color: var(--muted);
            font-size: 14px;
            transition: all 0.2s ease;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .drop-zone.dragover {
            border-color: var(--primary);
            background: #e6f4f2;
            color: var(--primary-dark);
        }

        /* Selected Columns List */
        .selected-columns-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .selected-column-card {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 40px;
            gap: 12px;
            align-items: center;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        /* Condition Builder Tree */
        .condition-group {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 14px;
            margin-top: 10px;
            position: relative;
        }

        .condition-group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .condition-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1.5fr 40px;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
            background: #fff;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid var(--line);
        }

        .condition-subgroups {
            margin-left: 20px;
            border-left: 2px solid #cbd5e1;
            padding-left: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-small {
            min-height: 28px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 800;
            border-radius: 6px;
            border: 1px solid var(--line);
            background: #fff;
            cursor: pointer;
        }

        .btn-small.danger {
            background: #fee2e2;
            color: var(--danger);
            border-color: #fca5a5;
        }

        .btn-small.primary {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .sql-preview-box {
            font-family: 'Courier New', Courier, monospace;
            background: #0f172a;
            color: #38bdf8;
            padding: 14px;
            border-radius: 6px;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            max-height: 120px;
        }

        .preview-table-wrap {
            max-height: 380px;
            overflow-y: auto;
            border: 1px solid var(--line);
            border-radius: 6px;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
        }

        .preview-table th, .preview-table td {
            padding: 8px 12px;
            font-size: 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .preview-table th {
            background: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Modal styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .template-list-item {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .template-list-item:hover {
            border-color: var(--primary);
            background: #f0fdf4;
        }
    </style>
@endpush

@section('content')
<div class="page-heading">
    <div>
        <h1>Report Builder & Analytics</h1>
        <p>Drag and drop dictionary tables, build custom nested filter statements, and export reports directly to CSV.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="button" id="loadTemplateBtn">📁 Load Template</button>
        <button class="button" id="saveTemplateBtn">💾 Save Template</button>
    </div>
</div>

<div class="builder-layout">

    <!-- Left Panel: Data Dictionary Columns Tree -->
    <aside class="sidebar-panel">
        <h3 style="margin-top: 0; font-size: 13px; text-transform: uppercase; color: var(--muted); margin-bottom: 14px;">Data Dictionary Fields</h3>
        
        <div style="margin-bottom: 14px;">
            <input type="text" id="dictionarySearchInput" oninput="filterDictionaryFields(this.value)" placeholder="🔍 Search fields..." style="min-height: 36px; padding: 8px 12px; font-size: 12px; border-radius: 6px; border: 1px solid var(--line); width: 100%;">
            <p style="margin: 6px 0 0; font-size: 11px; color: var(--muted);">💡 <em>Hover on a field to view its business definition.</em></p>
        </div>

        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach ($dictionary as $tableName => $fields)
                <div class="dictionary-table-group" data-table="{{ $tableName }}">
                    <div class="dictionary-table-header" onclick="toggleTableColumns('{{ $tableName }}')">
                        <span>🗂️ {{ strtoupper($tableName) }}</span>
                        <span id="arrow-{{ $tableName }}">▼</span>
                    </div>
                    
                    <div class="dictionary-columns-list" id="list-{{ $tableName }}" style="display: none;">
                        @foreach ($fields as $field)
                            <div class="column-node" 
                                 draggable="true" 
                                 ondragstart="onDragStart(event)"
                                 data-table="{{ $tableName }}"
                                 data-column="{{ $field->column_name }}"
                                 data-label="{{ $field->business_name }}"
                                 title="{{ $field->business_definition }}">
                                <span>🔹</span>
                                <span style="font-weight: 500;">{{ $field->business_name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </aside>

    <!-- Main Builder Panels -->
    <main class="main-panel">

        <!-- Panel 1: Columns Selection Dropzone -->
        <section class="card">
            <div class="card-header">
                <h3>1. Select columns</h3>
                <span class="muted">Drag dictionary columns and drop them here</span>
            </div>
            <div class="card-body">
                <div class="drop-zone" id="columnsDropZone" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onColumnDrop(event)">
                    <div id="columnsPlaceholder">
                        <p style="margin: 0; font-size: 28px;">📥</p>
                        <p style="margin: 6px 0 0; font-weight: 600;">Drag and drop columns here</p>
                    </div>
                    <div class="selected-columns-grid" id="selectedColumnsGrid" style="display: none;"></div>
                </div>
            </div>
        </section>

        <!-- Panel 2: Logical Filters condition builder -->
        <section class="card">
            <div class="card-header">
                <h3>2. Filter Criteria</h3>
                <span class="muted">Build structured logical query statement</span>
            </div>
            <div class="card-body">
                <div id="filterTreeContainer">
                    <!-- Dynamic condition tree root -->
                </div>
            </div>
        </section>

        <!-- Control Action Bar -->
        <div style="display: flex; gap: 14px;">
            <button class="button primary" style="flex:1; min-height: 48px; font-weight: 800; font-size: 15px;" id="runQueryBtn">⚡ Preview Custom Report</button>
            <button class="button" style="min-height: 48px; padding: 0 24px;" id="exportCsvBtn">📥 Export CSV</button>
        </div>

        <!-- Panel 3: Results Preview and Generated SQL -->
        <section class="card" id="resultsCard" style="display: none;">
            <div class="card-header">
                <h3>Query Preview & Generated SQL</h3>
                <span class="muted" id="previewRowCount">Showing first 50 rows</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <h4 style="margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: var(--muted);">Generated SQL Query (Read-Only)</h4>
                    <div class="sql-preview-box" id="sqlPreviewText">SELECT * FROM client;</div>
                </div>

                <div class="preview-table-wrap">
                    <table class="preview-table">
                        <thead id="previewTableHead"></thead>
                        <tbody id="previewTableBody"></tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

</div>

<!-- Modal: Save Template -->
<div class="modal" id="saveTemplateModal">
    <div class="modal-content">
        <div class="card-header">
            <h3>Save Report Template</h3>
            <span style="cursor:pointer;" onclick="closeModal('saveTemplateModal')">❌</span>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
            <input type="hidden" id="saveTemplateId">
            <div>
                <label for="templateName">Template Name</label>
                <input type="text" id="templateName" placeholder="e.g. NTTN Collections shortfall">
            </div>
            <div>
                <label for="templateDesc">Description</label>
                <textarea id="templateDesc" placeholder="Describe the purpose of this custom report"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button class="button" onclick="closeModal('saveTemplateModal')">Cancel</button>
                <button class="button primary" id="confirmSaveTemplateBtn">Save Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Load Template -->
<div class="modal" id="loadTemplateModal">
    <div class="modal-content">
        <div class="card-header">
            <h3>Saved Templates</h3>
            <span style="cursor:pointer;" onclick="closeModal('loadTemplateModal')">❌</span>
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <div id="templatesListContainer">
                @if ($templates->isEmpty())
                    <div class="empty">No saved templates found. Create one by clicking "Save Template" above.</div>
                @else
                    @foreach ($templates as $t)
                        <div class="template-list-item" onclick="loadTemplateById({{ $t->id }})">
                            <div>
                                <strong style="color: var(--primary-dark);">{{ $t->name }}</strong>
                                <p style="margin: 4px 0 0; font-size:11px; color: var(--muted);">{{ $t->description ?: 'No description' }} · By {{ $t->creator->name ?? 'System' }}</p>
                            </div>
                            <button class="btn-small danger" onclick="event.stopPropagation(); deleteTemplate({{ $t->id }})">Delete</button>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const savedTemplates = @json($templates);

    // State representation of the Query AST
    let queryState = {
        selected_fields: [],
        filters: {
            logical_operator: 'AND',
            conditions: []
        }
    };
    
    let loadedTemplateId = null;

    // Toggle tree tables
    function toggleTableColumns(tableName) {
        const list = document.getElementById(`list-${tableName}`);
        const arrow = document.getElementById(`arrow-${tableName}`);
        if (list.style.display === 'none') {
            list.style.display = 'flex';
            arrow.textContent = '▲';
        } else {
            list.style.display = 'none';
            arrow.textContent = '▼';
        }
    }

    // Drag-and-drop actions
    function onDragStart(e) {
        e.dataTransfer.setData('text/plain', JSON.stringify({
            table: e.currentTarget.dataset.table,
            column: e.currentTarget.dataset.column,
            label: e.currentTarget.dataset.label
        }));
    }

    function onDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('dragover');
    }

    function onDragLeave(e) {
        e.currentTarget.classList.remove('dragover');
    }

    function onColumnDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            addColumn(data.table, data.column, data.label);
        } catch (err) {}
    }

    // Add selected column logic
    function addColumn(table, column, label) {
        const exists = queryState.selected_fields.some(f => f.table === table && f.column === column);
        if (exists) return;

        queryState.selected_fields.push({
            table: table,
            column: column,
            alias: label,
            aggregate: ''
        });

        renderSelectedColumns();
    }

    function removeColumn(index) {
        queryState.selected_fields.splice(index, 1);
        renderSelectedColumns();
    }

    function updateColumnAggregate(index, aggregate) {
        queryState.selected_fields[index].aggregate = aggregate;
    }

    function updateColumnAlias(index, alias) {
        queryState.selected_fields[index].alias = alias;
    }

    function renderSelectedColumns() {
        const grid = document.getElementById('selectedColumnsGrid');
        const placeholder = document.getElementById('columnsPlaceholder');

        if (queryState.selected_fields.length === 0) {
            grid.style.display = 'none';
            placeholder.style.display = 'block';
            return;
        }

        placeholder.style.display = 'none';
        grid.style.display = 'flex';
        grid.innerHTML = '';

        queryState.selected_fields.forEach((f, idx) => {
            const div = document.createElement('div');
            div.className = 'selected-column-card';
            div.innerHTML = `
                <div style="text-align: left;">
                    <strong style="color:var(--ink); font-size:13px;">${f.alias}</strong>
                    <p style="margin:2px 0 0; font-size:10px; color:var(--muted);">${f.table}.${f.column}</p>
                </div>
                <div>
                    <select onchange="updateColumnAggregate(${idx}, this.value)" style="min-height:32px; padding:4px; font-size:11px;">
                        <option value="" ${f.aggregate === '' ? 'selected' : ''}>No Aggregate</option>
                        <option value="sum" ${f.aggregate === 'sum' ? 'selected' : ''}>SUM</option>
                        <option value="avg" ${f.aggregate === 'avg' ? 'selected' : ''}>AVG</option>
                        <option value="count" ${f.aggregate === 'count' ? 'selected' : ''}>COUNT</option>
                        <option value="max" ${f.aggregate === 'max' ? 'selected' : ''}>MAX</option>
                        <option value="min" ${f.aggregate === 'min' ? 'selected' : ''}>MIN</option>
                    </select>
                </div>
                <div>
                    <input type="text" value="${f.alias}" onchange="updateColumnAlias(${idx}, this.value)" style="min-height:32px; padding:4px 8px; font-size:11px;" placeholder="Alias label">
                </div>
                <button class="btn-small danger" style="padding:0; height:32px; display:grid; place-items:center;" onclick="removeColumn(${idx})">❌</button>
            `;
            grid.appendChild(div);
        });
    }

    // Filters condition tree logic
    function initFilterTree() {
        const container = document.getElementById('filterTreeContainer');
        container.innerHTML = '';
        container.appendChild(createFilterGroupEl(queryState.filters));
    }

    function getColumnLabel(table, column) {
        const node = document.querySelector(`.column-node[data-table="${table}"][data-column="${column}"]`);
        return node ? node.dataset.label : `${table}.${column}`;
    }

    function filterDictionaryFields(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.dictionary-table-group').forEach(group => {
            const tableName = group.dataset.table.toLowerCase();
            const list = document.getElementById(`list-${group.dataset.table}`);
            const arrow = document.getElementById(`arrow-${group.dataset.table}`);
            let matchCount = 0;

            group.querySelectorAll('.column-node').forEach(node => {
                const label = node.dataset.label.toLowerCase();
                const col = node.dataset.column.toLowerCase();
                const isMatch = label.includes(q) || col.includes(q) || tableName.includes(q);
                
                if (isMatch) {
                    node.style.display = 'flex';
                    matchCount++;
                } else {
                    node.style.display = 'none';
                }
            });

            if (q === '') {
                list.style.display = 'none';
                arrow.textContent = '▼';
                group.style.display = 'block';
            } else {
                if (matchCount > 0) {
                    group.style.display = 'block';
                    list.style.display = 'flex';
                    arrow.textContent = '▲';
                } else {
                    group.style.display = 'none';
                }
            }
        });
    }

    function createFilterGroupEl(groupData) {
        const div = document.createElement('div');
        div.className = 'condition-group';

        const header = document.createElement('div');
        header.className = 'condition-group-header';
        
        const selectOp = document.createElement('select');
        selectOp.style.width = '100px';
        selectOp.style.minHeight = '32px';
        selectOp.innerHTML = `
            <option value="AND" ${groupData.logical_operator === 'AND' ? 'selected' : ''}>AND</option>
            <option value="OR" ${groupData.logical_operator === 'OR' ? 'selected' : ''}>OR</option>
        `;
        selectOp.onchange = (e) => { groupData.logical_operator = e.target.value; };
        header.appendChild(selectOp);

        const addGroupBtn = document.createElement('button');
        addGroupBtn.className = 'btn-small';
        addGroupBtn.textContent = '📁 Add Nested Group';
        addGroupBtn.onclick = () => {
            groupData.conditions.push({
                logical_operator: 'AND',
                conditions: []
            });
            refreshFilterTree();
        };
        header.appendChild(addGroupBtn);

        div.appendChild(header);

        // Sub-elements list
        const listDiv = document.createElement('div');
        listDiv.className = 'condition-subgroups';

        groupData.conditions.forEach((c, idx) => {
            if (c.logical_operator) {
                // Nested group element
                const subGroupEl = createFilterGroupEl(c);
                
                // Remove group button
                const removeGroupBtn = document.createElement('button');
                removeGroupBtn.className = 'btn-small danger';
                removeGroupBtn.textContent = 'Remove Group';
                removeGroupBtn.style.position = 'absolute';
                removeGroupBtn.style.top = '10px';
                removeGroupBtn.style.right = '10px';
                removeGroupBtn.onclick = () => {
                    groupData.conditions.splice(idx, 1);
                    refreshFilterTree();
                };
                subGroupEl.appendChild(removeGroupBtn);

                listDiv.appendChild(subGroupEl);
            } else {
                // Standard condition row
                const row = document.createElement('div');
                row.className = 'condition-row';

                // Static column label
                const labelDiv = document.createElement('div');
                labelDiv.style.textAlign = 'left';
                labelDiv.style.fontSize = '12px';
                labelDiv.style.fontWeight = 'bold';
                labelDiv.style.color = 'var(--primary-dark)';
                const fLabel = c.label || getColumnLabel(c.table, c.column);
                labelDiv.innerHTML = `
                    <span>${fLabel}</span>
                    <p style="margin:2px 0 0; font-size:10px; color:var(--muted);">${c.table}.${c.column}</p>
                `;

                const selectOp = document.createElement('select');
                selectOp.style.minHeight = '32px';
                const operators = [
                    { val: '=', lbl: 'Equals (=)' },
                    { val: '!=', lbl: 'Not Equals (!=)' },
                    { val: '>', lbl: 'Greater Than (>)' },
                    { val: '<', lbl: 'Less Than (<)' },
                    { val: '>=', lbl: 'Greater or Equal (>=)' },
                    { val: '<=', lbl: 'Less or Equal (<=)' },
                    { val: 'contains', lbl: 'Contains' },
                    { val: 'starts_with', lbl: 'Starts With' },
                    { val: 'in', lbl: 'In List' },
                    { val: 'is_null', lbl: 'Is Null' },
                    { val: 'is_not_null', lbl: 'Is Not Null' }
                ];
                selectOp.innerHTML = '';
                operators.forEach(op => {
                    selectOp.innerHTML += `<option value="${op.val}" ${c.operator === op.val ? 'selected' : ''}>${op.lbl}</option>`;
                });
                selectOp.onchange = (e) => { c.operator = e.target.value; };

                const valInput = document.createElement('input');
                valInput.type = 'text';
                valInput.placeholder = 'Value';
                valInput.style.minHeight = '32px';
                valInput.value = c.value || '';
                valInput.onchange = (e) => { c.value = e.target.value; };

                const delBtn = document.createElement('button');
                delBtn.className = 'btn-small danger';
                delBtn.textContent = '❌';
                delBtn.style.padding = '0';
                delBtn.style.height = '32px';
                delBtn.style.display = 'grid';
                delBtn.style.placeItems = 'center';
                delBtn.onclick = () => {
                    groupData.conditions.splice(idx, 1);
                    refreshFilterTree();
                };

                row.appendChild(labelDiv);
                row.appendChild(selectOp);
                row.appendChild(valInput);
                row.appendChild(delBtn);

                listDiv.appendChild(row);
            }
        });

        div.appendChild(listDiv);

        // Filter group drop target zone
        const dropZone = document.createElement('div');
        dropZone.className = 'drop-zone';
        dropZone.style.padding = '12px';
        dropZone.style.minHeight = '48px';
        dropZone.style.marginTop = '10px';
        dropZone.style.borderStyle = 'dashed';
        dropZone.style.fontSize = '12px';
        dropZone.innerHTML = '📥 Drag & drop field here to add filter condition';
        
        dropZone.ondragover = (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        };
        dropZone.ondragleave = () => {
            dropZone.classList.remove('dragover');
        };
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            try {
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                groupData.conditions.push({
                    table: data.table,
                    column: data.column,
                    label: data.label,
                    operator: '=',
                    value: ''
                });
                refreshFilterTree();
            } catch (err) {}
        };
        
        div.appendChild(dropZone);

        return div;
    }

    function refreshFilterTree() {
        initFilterTree();
    }

    // Modal Control actions
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // REST AJAX queries
    document.getElementById('runQueryBtn').addEventListener('click', function() {
        if (queryState.selected_fields.length === 0) {
            alert('Please select at least one column.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.textContent = '⚡ Running Query...';

        fetch('{{ route('reports.preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ query_config: queryState })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '⚡ Preview Custom Report';

            if (!data.success) {
                alert('Error: ' + data.message);
                return;
            }

            renderPreview(data);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = '⚡ Preview Custom Report';
            alert('A network error occurred.');
        });
    });

    function renderPreview(data) {
        document.getElementById('resultsCard').style.display = 'block';
        document.getElementById('sqlPreviewText').textContent = data.sql;

        const head = document.getElementById('previewTableHead');
        const body = document.getElementById('previewTableBody');

        head.innerHTML = '';
        body.innerHTML = '';

        const headers = data.headers;
        const rows = data.rows;

        // Render Head
        const trHead = document.createElement('tr');
        Object.values(headers).forEach(lbl => {
            const th = document.createElement('th');
            th.textContent = lbl;
            trHead.appendChild(th);
        });
        head.appendChild(trHead);

        // Render Rows
        if (rows.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="${Object.keys(headers).length}" class="empty">No matching records found.</td>`;
            body.appendChild(tr);
            return;
        }

        rows.forEach(row => {
            const tr = document.createElement('tr');
            Object.keys(headers).forEach(key => {
                const td = document.createElement('td');
                const val = row[key];
                
                // Format numbers cleanly
                if (val !== null && !isNaN(val) && val !== '') {
                    td.textContent = Number(val).toLocaleString();
                    td.style.textAlign = 'right';
                } else {
                    td.textContent = val ?? '';
                }
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });

        document.getElementById('resultsCard').scrollIntoView({ behavior: 'smooth' });
    }

    // Save Template action
    document.getElementById('saveTemplateBtn').addEventListener('click', () => {
        if (queryState.selected_fields.length === 0) {
            alert('Define a query configuration before saving.');
            return;
        }
        document.getElementById('saveTemplateId').value = loadedTemplateId || '';
        openModal('saveTemplateModal');
    });

    document.getElementById('confirmSaveTemplateBtn').addEventListener('click', () => {
        const name = document.getElementById('templateName').value;
        const description = document.getElementById('templateDesc').value;
        const id = document.getElementById('saveTemplateId').value;

        if (!name) {
            alert('Template name is required.');
            return;
        }

        fetch('{{ route('reports.save-template') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                id: id ? parseInt(id) : null,
                name: name,
                description: description,
                query_config: queryState
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Template saved successfully!');
                closeModal('saveTemplateModal');
                location.reload(); // Reload to refresh templates list
            } else {
                alert('Save failed.');
            }
        });
    });

    // Load Template actions
    document.getElementById('loadTemplateBtn').addEventListener('click', () => {
        openModal('loadTemplateModal');
    });

    function loadTemplateById(id) {
        const template = savedTemplates.find(t => t.id == id);
        if (!template) return;

        queryState = template.query_config;
        loadedTemplateId = template.id;
        
        document.getElementById('templateName').value = template.name;
        document.getElementById('templateDesc').value = template.description || '';

        renderSelectedColumns();
        refreshFilterTree();
        closeModal('loadTemplateModal');
        
        alert(`Loaded template: "${template.name}"`);
    }

    function deleteTemplate(id) {
        if (!confirm('Are you sure you want to delete this template?')) return;

        fetch('{{ url('reports/delete-template') }}/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Template deleted.');
                location.reload();
            } else {
                alert('Delete failed.');
            }
        });
    }

    // Export CSV
    document.getElementById('exportCsvBtn').addEventListener('click', () => {
        if (queryState.selected_fields.length === 0) {
            alert('Select columns before exporting.');
            return;
        }

        // Post parameters via a dynamic form to trigger native browser file download stream
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('reports.export') }}';
        form.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.name = '_token';
        tokenInput.value = '{{ csrf_token() }}';
        form.appendChild(tokenInput);

        const configInput = document.createElement('input');
        configInput.name = 'query_config';
        configInput.value = JSON.stringify(queryState);
        form.appendChild(configInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // Init onload
    document.addEventListener('DOMContentLoaded', () => {
        renderSelectedColumns();
        initFilterTree();
    });
</script>
@endpush
