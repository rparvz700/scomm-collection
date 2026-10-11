@extends('layouts.app')

@section('title', 'Edit Client | SCOMM Collection')

@push('styles')
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-section {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 20px;
            background: #ffffff;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .form-section h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--primary-dark);
            border-bottom: 1px solid var(--line);
            padding-bottom: 8px;
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-field input {
            width: 20px;
            height: 20px;
            min-height: 20px;
            cursor: pointer;
        }

        .checkbox-field label {
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }

        .button-bar {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 10px;
        }

        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div id="clientStatusNeonIndicator" style="position: fixed; top: 72px; left: 0; width: 8px; height: calc(100vh - 72px); z-index: 15; transition: background-color 0.3s ease, box-shadow 0.3s ease; cursor: pointer; border-top-right-radius: 4px; border-bottom-right-radius: 4px;" title="Client Status Indicator"></div>

    <section class="page-heading">
        <div>
            <h1>Edit Client Details</h1>
            <p>Update properties for customer master profile: <strong>{{ $client->client_name }}</strong></p>
        </div>
        <a href="{{ route('clients.index') }}" class="button">Back to list</a>
    </section>

    <form method="POST" action="{{ route('clients.update', $client->client_id) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <!-- Section 1: Customer Profile -->
            <div class="form-section">
                <h2>Customer Profile</h2>

                <div class="field">
                    <label for="opus_id">OPUS ID <span style="color: var(--danger)">*</span></label>
                    <input id="opus_id" name="opus_id" type="text" placeholder="e.g. C10092" value="{{ old('opus_id', $client->opus_id) }}" required>
                    @error('opus_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="client_name">Client Name <span style="color: var(--danger)">*</span></label>
                    <input id="client_name" name="client_name" type="text" placeholder="e.g. Summit Communications" value="{{ old('client_name', $client->client_name) }}" required>
                    @error('client_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="client_status">Client Status</label>
                    <select id="client_status" name="client_status">
                        <option value="Active" @selected(in_array(old('client_status', $client->client_status), ['Active', 'Inactive', 'Barred', '']))>Active</option>
                        <option value="Discontinued" @selected(old('client_status', $client->client_status) === 'Discontinued')>Discontinued</option>
                    </select>
                    @error('client_status')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="barring_priority">Barring Priority</label>
                    <select id="barring_priority" name="barring_priority">
                        <option value="" @selected(old('barring_priority', $client->barring_priority) === '')>Select Priority</option>
                        <option value="P1" @selected(old('barring_priority', $client->barring_priority) === 'P1')>P1</option>
                        <option value="P2" @selected(old('barring_priority', $client->barring_priority) === 'P2')>P2</option>
                    </select>
                    @error('barring_priority')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="barring_percentage">Barring Percentage (%)</label>
                    <input id="barring_percentage" name="barring_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00 - 100.00" value="{{ old('barring_percentage', $client->barring_percentage) }}">
                    @error('barring_percentage')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="barring_workflow_status">Barring Workflow Status</label>
                    <input id="barring_workflow_status" name="barring_workflow_status" type="text" placeholder="e.g. Partial Barred / Pending" value="{{ old('barring_workflow_status', $client->barring_workflow_status) }}">
                    @error('barring_workflow_status')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section: Service Agreements Repeater -->
            <div class="form-section" style="grid-column: span 2;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h2 style="margin: 0;">📜 Agreement Information</h2>
                    <button type="button" onclick="addAgreementRow()" style="height: 32px; padding: 0 12px; font-size: 12px; border-radius: 6px; border: 1px solid var(--primary); background: var(--primary); color: #ffffff; font-weight: 700; cursor: pointer;">+ Add Agreement Row</button>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 650px;" id="agreementsTable">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--line);">
                                <th style="padding: 8px; font-size: 11.5px; text-align: left; width: 160px;">License / Service</th>
                                <th style="padding: 8px; font-size: 11.5px; text-align: left; width: 140px;">Signing Date</th>
                                <th style="padding: 8px; font-size: 11.5px; text-align: left; width: 140px;">Expiry Date</th>
                                <th style="padding: 8px; font-size: 11.5px; text-align: left;">Agreement Status</th>
                                <th style="padding: 8px; font-size: 11.5px; width: 50px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="agreementsContainer">
                            {{-- JS Injected Rows --}}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2: Billing Classification -->
            <div class="form-section">
                <h2>Billing Details</h2>

                <div class="field">
                    <label for="service_type_billing" style="font-weight: 700; margin-bottom: 6px; display: block;">Service Type Billing</label>
                    
                    <div id="stb-badge-container" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 38px; padding: 6px 10px; border: 1px solid var(--line); border-radius: 6px; background: var(--panel); align-items: center; margin-bottom: 6px;">
                        <!-- Injected Badges -->
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                        <span style="font-size: 11px; color: var(--muted); font-weight: 600;">Click to select / toggle:</span>
                        @php
                            $availableStbOptions = ['NTTN', 'IIG', 'ITC', 'NIX'];
                        @endphp
                        @foreach($availableStbOptions as $opt)
                            <button type="button" class="stb-option-btn" data-value="{{ $opt }}" onclick="toggleStbOption('{{ $opt }}')" style="padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 600; cursor: pointer; border: 1px solid var(--line); background: #f1f5f9; color: var(--ink); transition: all 0.2s;">
                                + {{ $opt }}
                            </button>
                        @endforeach
                    </div>

                    <div id="stb-hidden-inputs"></div>
                    @error('service_type_billing')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="license_billing">License Billing</label>
                    <input id="license_billing" name="license_billing" type="text" placeholder="e.g. ISP Nationwide" value="{{ old('license_billing', $client->license_billing) }}">
                    @error('license_billing')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="billing_modality_kpi">Billing Modality KPI</label>
                    <input id="billing_modality_kpi" name="billing_modality_kpi" type="text" placeholder="e.g. Monthly Advance / Postpaid" value="{{ old('billing_modality_kpi', $client->billing_modality_kpi) }}">
                    @error('billing_modality_kpi')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="security_coverage">Security Coverage (%)</label>
                    <input id="security_coverage" name="security_coverage" type="text" placeholder="e.g. 100% or 50%" value="{{ old('security_coverage', $client->formatted_security_coverage) }}">
                    @error('security_coverage')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 3: Operations & Compliance -->
            <div class="form-section">
                <h2>Compliance & Policy</h2>

                <div class="field">
                    <label for="btrc_letter">BTRC Letter / Notice</label>
                    <input id="btrc_letter" name="btrc_letter" type="text" placeholder="BTRC Letter Reference No" value="{{ old('btrc_letter', $client->btrc_letter) }}">
                    @error('btrc_letter')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="payment_plan">Payment Plan Summary</label>
                    <input id="payment_plan" name="payment_plan" type="text" placeholder="e.g. Instalments of MRC clearance" value="{{ old('payment_plan', $client->payment_plan) }}">
                    @error('payment_plan')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="deferred_period">Deferred Period (Days)</label>
                    <input id="deferred_period" name="deferred_period" type="number" min="0" max="365" placeholder="0 (Default: 0 Days)" value="{{ old('deferred_period', $client->deferred_period ?? 0) }}">
                    @error('deferred_period')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="legal">Escalate Legal Action / Notes</label>
                    <textarea id="legal" name="legal" rows="3" placeholder="e.g. Notice Sent / Pending">{{ old('legal', $client->legal) }}</textarea>
                    @error('legal')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field checkbox-field">
                    <input id="other_upstream" name="other_upstream" type="checkbox" value="1" @checked(old('other_upstream', $client->other_upstream))>
                    <label for="other_upstream">Has Upstream Other Than SComm</label>
                    @error('other_upstream')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 4: Operational Assignments -->
            <div class="form-section">
                <h2>Team & KAM Assignments</h2>

                <div class="field">
                    <label for="collection_team_name">Collection Team Name</label>
                    <input id="collection_team_name" name="collection_team_name" type="text" placeholder="e.g. Corporate Collection" value="{{ old('collection_team_name', $client->collection_team_name) }}">
                    @error('collection_team_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_kam_id">Collection KAM</label>
                    <select id="collection_kam_id" name="collection_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('collection_kam_id', $client->collection_kam_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('collection_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_supervisor_id">Collection Supervisor</label>
                    <select id="collection_supervisor_id" name="collection_supervisor_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('collection_supervisor_id', $client->collection_supervisor_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('collection_supervisor_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="team_name">SM Team Name</label>
                    <input id="team_name" name="team_name" type="text" placeholder="e.g. Corporate Accounts" value="{{ old('team_name', $client->team_name) }}">
                    @error('team_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="sm_supervisor_id">SM Supervisor</label>
                    <select id="sm_supervisor_id" name="sm_supervisor_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('sm_supervisor_id', $client->sm_supervisor_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('sm_supervisor_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="sm_kam_id">SM KAM</label>
                    <select id="sm_kam_id" name="sm_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('sm_kam_id', $client->sm_kam_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('sm_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nttn_billing_kam_id">NTTN Billing KAM</label>
                    <select id="nttn_billing_kam_id" name="nttn_billing_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('nttn_billing_kam_id', $client->nttn_billing_kam_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('nttn_billing_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="iig_itc_billing_kam_id">IIG/ITC Billing KAM</label>
                    <select id="iig_itc_billing_kam_id" name="iig_itc_billing_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('iig_itc_billing_kam_id', $client->iig_itc_billing_kam_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('iig_itc_billing_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 5: Key Dates & Milestones -->
            <div class="form-section" style="grid-column: span 2;">
                <h2>Dates & Milestone Commencements</h2>
                
                <div class="form-grid">
                    <div class="field">
                        <label for="nttn_billing_commencement_date">NTTN Billing Commencement Date</label>
                        <input id="nttn_billing_commencement_date" name="nttn_billing_commencement_date" type="date" value="{{ old('nttn_billing_commencement_date', $client->nttn_billing_commencement_date?->format('Y-m-d')) }}">
                        @error('nttn_billing_commencement_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="iig_itc_billing_commencement_date">IIG/ITC Billing Commencement Date</label>
                        <input id="iig_itc_billing_commencement_date" name="iig_itc_billing_commencement_date" type="date" value="{{ old('iig_itc_billing_commencement_date', $client->iig_itc_billing_commencement_date?->format('Y-m-d')) }}">
                        @error('iig_itc_billing_commencement_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="btrc_license_discontinuation_date">BTRC License Discontinuation Date</label>
                        <input id="btrc_license_discontinuation_date" name="btrc_license_discontinuation_date" type="date" value="{{ old('btrc_license_discontinuation_date', $client->btrc_license_discontinuation_date?->format('Y-m-d')) }}">
                        @error('btrc_license_discontinuation_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="service_discontinuation_date">Service Discontinuation Date</label>
                        <input id="service_discontinuation_date" name="service_discontinuation_date" type="date" value="{{ old('service_discontinuation_date', $client->service_discontinuation_date?->format('Y-m-d')) }}">
                        @error('service_discontinuation_date')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="button-bar">
            <a href="{{ route('clients.index') }}" class="button">Cancel</a>
            <button type="submit" class="button primary">Update Client</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // --- STB (Service Type Billing) Multi-Select Badge Component ---
    @php
        $rawStb = old('service_type_billing', $client->service_type_billing ?? []);
        $initialStbList = is_array($rawStb) ? $rawStb : array_filter(array_map('trim', explode(',', (string)$rawStb)));
    @endphp
    let selectedStb = @json(array_values($initialStbList));

    function renderStbComponent() {
        const badgeContainer = document.getElementById('stb-badge-container');
        const hiddenInputs = document.getElementById('stb-hidden-inputs');
        if (!badgeContainer || !hiddenInputs) return;

        badgeContainer.innerHTML = '';
        hiddenInputs.innerHTML = '';

        if (selectedStb.length === 0) {
            badgeContainer.innerHTML = '<span style="color: var(--muted); font-size: 12px; font-style: italic;">No service types selected</span>';
        } else {
            selectedStb.forEach(val => {
                const badge = document.createElement('span');
                badge.style.cssText = 'display: inline-flex; align-items: center; gap: 6px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 14px; font-size: 12px; font-weight: 700;';
                badge.innerHTML = `<span>${val}</span> <span onclick="removeStbOption('${val}')" style="cursor: pointer; font-size: 14px; color: #0284c7; line-height: 1; font-weight: bold;" title="Remove">&times;</span>`;
                badgeContainer.appendChild(badge);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'service_type_billing[]';
                hidden.value = val;
                hiddenInputs.appendChild(hidden);
            });
        }

        document.querySelectorAll('.stb-option-btn').forEach(btn => {
            const val = btn.getAttribute('data-value');
            if (selectedStb.includes(val)) {
                btn.style.background = 'var(--primary)';
                btn.style.color = '#ffffff';
                btn.style.borderColor = 'var(--primary)';
                btn.innerText = `✓ ${val}`;
            } else {
                btn.style.background = '#f1f5f9';
                btn.style.color = 'var(--ink)';
                btn.style.borderColor = 'var(--line)';
                btn.innerText = `+ ${val}`;
            }
        });
    }

    function toggleStbOption(val) {
        const idx = selectedStb.indexOf(val);
        if (idx > -1) {
            selectedStb.splice(idx, 1);
        } else {
            selectedStb.push(val);
        }
        renderStbComponent();
    }

    function removeStbOption(val) {
        const idx = selectedStb.indexOf(val);
        if (idx > -1) {
            selectedStb.splice(idx, 1);
        }
        renderStbComponent();
    }

    // --- Service Agreements Repeater ---
    let agreementIndex = 0;
    @php
        $rawAgreements = old('agreement_status', $client->agreement_status ?? []);
        if (is_string($rawAgreements)) {
            $rawAgreements = json_decode($rawAgreements, true) ?: [];
        }
    @endphp
    const existingAgreements = @json($rawAgreements);

    function addAgreementRow(data = {}) {
        const container = document.getElementById('agreementsContainer');
        if (!container) return;

        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid var(--line)';

        const licenseName = data.license_name || '';
        const signingDate = data.signing_date || '';
        const expiryDate = data.expiry_date || '';
        const status = data.status || data.ref_status || data.ref_no || '';

        tr.innerHTML = `
            <td style="padding: 6px;">
                <select name="agreement_status[${agreementIndex}][license_name]" required style="width: 100%; height: 34px; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
                    <option value="">Select License</option>
                    <option value="NTTN" ${licenseName === 'NTTN' ? 'selected' : ''}>NTTN</option>
                    <option value="IIG" ${licenseName === 'IIG' || licenseName === 'IIG/ITC' ? 'selected' : ''}>IIG</option>
                    <option value="ITC" ${licenseName === 'ITC' ? 'selected' : ''}>ITC</option>
                    <option value="NIX" ${licenseName === 'NIX' ? 'selected' : ''}>NIX</option>
                </select>
            </td>
            <td style="padding: 6px;">
                <input type="date" name="agreement_status[${agreementIndex}][signing_date]" value="${signingDate}" style="width: 100%; height: 34px; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
            </td>
            <td style="padding: 6px;">
                <input type="date" name="agreement_status[${agreementIndex}][expiry_date]" value="${expiryDate}" style="width: 100%; height: 34px; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
            </td>
            <td style="padding: 6px;">
                <input type="text" name="agreement_status[${agreementIndex}][status]" value="${status}" placeholder="e.g. Done / Expired" style="width: 100%; height: 34px; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
            </td>
            <td style="padding: 6px; text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; border-radius: 6px; width: 28px; height: 28px; cursor: pointer; font-weight: bold; line-height: 1;">&times;</button>
            </td>
        `;

        container.appendChild(tr);
        agreementIndex++;
    }

    function updateClientStatusNeonIndicator() {
        const statusEl = document.getElementById('client_status');
        const barringPctEl = document.getElementById('barring_percentage');
        const indicatorEl = document.getElementById('clientStatusNeonIndicator');
        if (!indicatorEl) return;

        const statusVal = statusEl ? (statusEl.value || 'Active').trim().toLowerCase() : 'active';
        const barringPct = barringPctEl ? parseFloat(barringPctEl.value || '0') : 0;

        let color = '#22c55e'; // Green (Active & barring <= 0)
        let label = 'Active Client';

        if (statusVal.includes('discontinued') || statusVal.includes('discontinue')) {
            color = '#ef4444'; // Red (Discontinued)
            label = 'Discontinued Client';
        } else if (barringPct > 0 || statusVal.includes('barred') || statusVal.includes('barring')) {
            color = '#f97316'; // Orange (Barred / barring_percentage > 0%)
            label = `Barred Client (${barringPct}%)`;
        }

        indicatorEl.style.backgroundColor = color;
        indicatorEl.style.boxShadow = `0 0 12px ${color}, 0 0 25px ${color}, 0 0 35px ${color}`;
        indicatorEl.title = `Status Indicator: ${label}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderStbComponent();

        const statusSelect = document.getElementById('client_status');
        const barringInput = document.getElementById('barring_percentage');

        if (statusSelect) {
            statusSelect.addEventListener('change', updateClientStatusNeonIndicator);
        }
        if (barringInput) {
            barringInput.addEventListener('input', updateClientStatusNeonIndicator);
            barringInput.addEventListener('change', updateClientStatusNeonIndicator);
        }
        updateClientStatusNeonIndicator();

        let list = existingAgreements;
        if (typeof list === 'string') {
            try { list = JSON.parse(list); } catch(e) {}
        }
        if (typeof list === 'string') {
            try { list = JSON.parse(list); } catch(e) {}
        }
        if (list && typeof list === 'object' && !Array.isArray(list)) {
            list = Object.values(list);
        }

        if (list && Array.isArray(list) && list.length > 0) {
            list.forEach(ag => addAgreementRow(ag));
        } else {
            addAgreementRow();
        }
    });
</script>
@endpush
