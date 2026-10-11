@extends('layouts.app')

@section('title', 'Waivers | SCOMM Collection')

@section('content')
    <section class="page-heading" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1>Waivers</h1>
            <p>Manage client waiver entries, applicable dates, waiver amounts, and supporting attachments.</p>
        </div>
        <button type="button" onclick="openAddWaiverModal()" class="button primary" style="height: 38px; padding: 0 16px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Waiver
        </button>
    </section>

    {{-- FILTERS FORM CARD --}}
    <div class="panel" style="margin-bottom: 24px; padding: 16px 20px;">
        <form method="GET" action="{{ route('waivers.index') }}" style="margin: 0; display: flex; align-items: flex-end; gap: 10px; width: 100%; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px;">
            
            {{-- Client Filter --}}
            <div style="flex: 1.5; min-width: 160px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Client Name</label>
                <select name="client_id" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; cursor: pointer; height: 34px;">
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
                <input type="date" name="date_from" value="{{ $selectedDateFrom }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Date To --}}
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Date To</label>
                <input type="date" name="date_to" value="{{ $selectedDateTo }}" style="width: 100%; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Text Search --}}
            <div style="flex: 2; min-width: 200px;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap;">Search Remarks &amp; User</label>
                <input type="text" name="search" value="{{ $searchText }}" placeholder="Search client, description, amount..." style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px; outline: none; height: 34px;">
            </div>

            {{-- Actions --}}
            <div style="display: flex; gap: 6px; min-width: 80px;">
                @if($selectedClientId || $selectedDateFrom || $selectedDateTo || $searchText)
                    <a href="{{ route('waivers.index') }}" class="button" title="Clear Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #64748b; border: 1px solid #64748b; color: #ffffff; border-radius: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                @endif
                <button type="submit" class="button primary" title="Apply Filters" style="height: 34px; width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: var(--primary); background: var(--primary); color: #ffffff;">
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
            <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--muted);">
                <span>Show</span>
                <select onchange="changePageSize(this.value)" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-weight: bold; cursor: pointer; outline: none; height: 28px;">
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
                        <th style="text-align: right; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 140px;">Waiver Amount</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 250px;">Description</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 140px;">Attachment</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 140px;">Created By</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800; width: 130px;">Actions</th>
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
                                ৳ {{ number_format($w->waiver_amount, 2) }}
                            </td>
                            <td style="padding: 12px 16px; color: var(--muted); font-size: 12.5px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $w->description }}">
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
    <div id="addWaiverModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 550px; box-shadow: var(--shadow); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02);">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--ink);">➕ Add Waiver Entry</h3>
                <button onclick="closeAddWaiverModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            <form id="addWaiverForm" action="{{ route('waivers.store') }}" method="POST" enctype="multipart/form-data" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Select Client</label>
                    <select name="client_id" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                        <option value="">-- Choose Client --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->client_id }}">{{ $c->client_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Applicable From Date</label>
                        <input type="date" name="applicable_from_date" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Waiver Amount (৳)</label>
                        <input type="number" step="0.01" min="0" name="waiver_amount" required placeholder="0.00" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Attachment File (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" style="width: 100%; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 12.5px;">
                    <small style="color: var(--muted); font-size: 11px;">Supported: PDF, PNG, JPG, DOC, XLS (Max 10MB)</small>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Description / Reason</label>
                    <textarea name="description" rows="3" placeholder="Enter details about this waiver..." style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" onclick="closeAddWaiverModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--line); background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;">Save Waiver</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT WAIVER MODAL --}}
    <div id="editWaiverModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 550px; box-shadow: var(--shadow); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.02);">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--ink);">✏️ Edit Waiver Entry</h3>
                <button onclick="closeEditWaiverModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            <form id="editWaiverForm" method="POST" enctype="multipart/form-data" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_waiver_id">

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Select Client</label>
                    <select id="edit_client_id" name="client_id" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                        @foreach($clients as $c)
                            <option value="{{ $c->client_id }}">{{ $c->client_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Applicable From Date</label>
                        <input type="date" id="edit_applicable_from_date" name="applicable_from_date" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">Waiver Amount (৳)</label>
                        <input type="number" step="0.01" min="0" id="edit_waiver_amount" name="waiver_amount" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px;">
                    </div>
                </div>

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

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" onclick="closeEditWaiverModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--line); background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveWaiverEditBtn" style="padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changePageSize(size) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', size);
            urlParams.set('page', 1);
            window.location.search = urlParams.toString();
        }

        function openAddWaiverModal() {
            document.getElementById('addWaiverModal').style.display = 'flex';
        }

        function closeAddWaiverModal() {
            document.getElementById('addWaiverModal').style.display = 'none';
        }

        function openEditWaiverModal(data) {
            document.getElementById('edit_waiver_id').value = data.id;
            document.getElementById('edit_client_id').value = data.client_id;
            document.getElementById('edit_applicable_from_date').value = data.applicable_from_date;
            document.getElementById('edit_waiver_amount').value = data.waiver_amount;
            document.getElementById('edit_description').value = data.description || '';
            
            const existingAttach = document.getElementById('edit_existing_attachment');
            if (data.attachment_name) {
                existingAttach.innerText = 'Current File: 📎 ' + data.attachment_name;
            } else {
                existingAttach.innerText = '';
            }

            const form = document.getElementById('editWaiverForm');
            form.action = `{{ url('/waivers') }}/${data.id}`;

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
@endsection
