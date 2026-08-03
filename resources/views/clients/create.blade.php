@extends('layouts.app')

@section('title', 'Add Client | SCOMM Collection')

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
    <section class="page-heading">
        <div>
            <h1>Add New Client</h1>
            <p>Register a new client profile into the master database.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="button">Back to list</a>
    </section>

    <form method="POST" action="{{ route('clients.store') }}">
        @csrf

        <div class="form-grid">
            <!-- Section 1: Customer Profile -->
            <div class="form-section">
                <h2>Customer Profile</h2>

                <div class="field">
                    <label for="opus_id">OPUS ID <span style="color: var(--danger)">*</span></label>
                    <input id="opus_id" name="opus_id" type="text" placeholder="e.g. C10092" value="{{ old('opus_id') }}" required>
                    @error('opus_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="client_name">Client Name <span style="color: var(--danger)">*</span></label>
                    <input id="client_name" name="client_name" type="text" placeholder="e.g. Summit Communications" value="{{ old('client_name') }}" required>
                    @error('client_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="client_status">Client Status</label>
                    <select id="client_status" name="client_status">
                        <option value="Active" @selected(old('client_status', 'Active') === 'Active')>Active</option>
                        <option value="Inactive" disabled @selected(old('client_status') === 'Inactive')>Inactive</option>
                        <option value="Barred" disabled @selected(old('client_status') === 'Barred')>Barred</option>
                        <option value="Discontinued" disabled @selected(old('client_status') === 'Discontinued')>Discontinued</option>
                    </select>
                    @error('client_status')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="agreement_status">Agreement Status</label>
                    <select id="agreement_status" name="agreement_status">
                        <option value="" @selected(old('agreement_status') === '')>Select Status</option>
                        <option value="Active" @selected(old('agreement_status') === 'Active')>Active</option>
                        <option value="Expired" @selected(old('agreement_status') === 'Expired')>Expired</option>
                        <option value="Pending Renewal" @selected(old('agreement_status') === 'Pending Renewal')>Pending Renewal</option>
                        <option value="Under Negotiation" @selected(old('agreement_status') === 'Under Negotiation')>Under Negotiation</option>
                    </select>
                    @error('agreement_status')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="barring_priority">Barring Priority</label>
                    <select id="barring_priority" name="barring_priority">
                        <option value="" @selected(old('barring_priority') === '')>Select Priority</option>
                        <option value="P1" @selected(old('barring_priority') === 'P1')>P1</option>
                        <option value="P2" @selected(old('barring_priority') === 'P2')>P2</option>
                    </select>
                    @error('barring_priority')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 2: Billing Classification -->
            <div class="form-section">
                <h2>Billing Details</h2>

                <div class="field">
                    <label for="service_type_billing">Service Type Billing</label>
                    <input id="service_type_billing" name="service_type_billing" type="text" placeholder="e.g. IIG / NTTN / ITC" value="{{ old('service_type_billing') }}">
                    @error('service_type_billing')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="license_billing">License Billing</label>
                    <input id="license_billing" name="license_billing" type="text" placeholder="e.g. ISP Nationwide" value="{{ old('license_billing') }}">
                    @error('license_billing')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="billing_modality_kpi">Billing Modality KPI</label>
                    <input id="billing_modality_kpi" name="billing_modality_kpi" type="text" placeholder="e.g. Monthly Advance / Postpaid" value="{{ old('billing_modality_kpi') }}">
                    @error('billing_modality_kpi')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="security_coverage">Security Coverage</label>
                    <input id="security_coverage" name="security_coverage" type="text" placeholder="e.g. BG Coverage / Security Cheque" value="{{ old('security_coverage') }}">
                    @error('security_coverage')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 3: Operations & Compliance -->
            <div class="form-section">
                <h2>Compliance & Policy</h2>

                <div class="field">
                    <label for="btrc_letter">BTRC Letter / Notice</label>
                    <input id="btrc_letter" name="btrc_letter" type="text" placeholder="BTRC Letter Reference No" value="{{ old('btrc_letter') }}">
                    @error('btrc_letter')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="payment_plan">Payment Plan Summary</label>
                    <input id="payment_plan" name="payment_plan" type="text" placeholder="e.g. Instalments of MRC clearance" value="{{ old('payment_plan') }}">
                    @error('payment_plan')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field checkbox-field">
                    <input id="legal" name="legal" type="checkbox" value="1" @checked(old('legal'))>
                    <label for="legal">Escalate Legal Action Flag</label>
                    @error('legal')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field checkbox-field">
                    <input id="other_upstream" name="other_upstream" type="checkbox" value="1" @checked(old('other_upstream'))>
                    <label for="other_upstream">Has Upstream Other Than SComm</label>
                    @error('other_upstream')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Section 4: Operational Assignments -->
            <div class="form-section">
                <h2>Team & KAM Assignments</h2>

                <div class="field">
                    <label for="team_name">Assigned Team Name</label>
                    <input id="team_name" name="team_name" type="text" placeholder="e.g. Corporate Accounts" value="{{ old('team_name') }}">
                    @error('team_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="sm_kam">Sales & Marketing KAM (Name)</label>
                    <input id="sm_kam" name="sm_kam" type="text" placeholder="KAM Name" value="{{ old('sm_kam') }}">
                    @error('sm_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="sm_kam_id">Sales & Marketing KAM (User Mapping)</label>
                    <select id="sm_kam_id" name="sm_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('sm_kam_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('sm_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_kam">Collection KAM (Name)</label>
                    <input id="collection_kam" name="collection_kam" type="text" placeholder="KAM Name" value="{{ old('collection_kam') }}">
                    @error('collection_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_kam_id">Collection KAM (User Mapping)</label>
                    <select id="collection_kam_id" name="collection_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('collection_kam_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('collection_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_supervisor">Collection Supervisor (Name)</label>
                    <input id="collection_supervisor" name="collection_supervisor" type="text" placeholder="Supervisor Name" value="{{ old('collection_supervisor') }}">
                    @error('collection_supervisor')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_supervisor_id">Collection Supervisor (User Mapping)</label>
                    <select id="collection_supervisor_id" name="collection_supervisor_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('collection_supervisor_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('collection_supervisor_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nttn_billing_kam">NTTN Billing KAM (Name)</label>
                    <input id="nttn_billing_kam" name="nttn_billing_kam" type="text" placeholder="KAM Name" value="{{ old('nttn_billing_kam') }}">
                    @error('nttn_billing_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nttn_billing_kam_id">NTTN Billing KAM (User Mapping)</label>
                    <select id="nttn_billing_kam_id" name="nttn_billing_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('nttn_billing_kam_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('nttn_billing_kam_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="iig_itc_billing_kam">IIG/ITC Billing KAM (Name)</label>
                    <input id="iig_itc_billing_kam" name="iig_itc_billing_kam" type="text" placeholder="KAM Name" value="{{ old('iig_itc_billing_kam') }}">
                    @error('iig_itc_billing_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="iig_itc_billing_kam_id">IIG/ITC Billing KAM (User Mapping)</label>
                    <select id="iig_itc_billing_kam_id" name="iig_itc_billing_kam_id" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; outline: none; cursor: pointer; height: 38px;">
                        <option value="">Select User...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('iig_itc_billing_kam_id') == $user->id ? 'selected' : '' }}>
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
                        <input id="nttn_billing_commencement_date" name="nttn_billing_commencement_date" type="date" value="{{ old('nttn_billing_commencement_date') }}">
                        @error('nttn_billing_commencement_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="iig_itc_billing_commencement_date">IIG/ITC Billing Commencement Date</label>
                        <input id="iig_itc_billing_commencement_date" name="iig_itc_billing_commencement_date" type="date" value="{{ old('iig_itc_billing_commencement_date') }}">
                        @error('iig_itc_billing_commencement_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="btrc_license_discontinuation_date">BTRC License Discontinuation Date</label>
                        <input id="btrc_license_discontinuation_date" name="btrc_license_discontinuation_date" type="date" value="{{ old('btrc_license_discontinuation_date') }}">
                        @error('btrc_license_discontinuation_date')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="service_discontinuation_date">Service Discontinuation Date</label>
                        <input id="service_discontinuation_date" name="service_discontinuation_date" type="date" value="{{ old('service_discontinuation_date') }}">
                        @error('service_discontinuation_date')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="button-bar">
            <a href="{{ route('clients.index') }}" class="button">Cancel</a>
            <button type="submit" class="button primary">Save Client</button>
        </div>
    </form>
@endsection
