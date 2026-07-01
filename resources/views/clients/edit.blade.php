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
                        <option value="Active" @selected(old('client_status', $client->client_status) === 'Active')>Active</option>
                        <option value="Inactive" @selected(old('client_status', $client->client_status) === 'Inactive')>Inactive</option>
                        <option value="Barred" @selected(old('client_status', $client->client_status) === 'Barred')>Barred</option>
                        <option value="Discontinued" @selected(old('client_status', $client->client_status) === 'Discontinued')>Discontinued</option>
                    </select>
                    @error('client_status')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="agreement_status">Agreement Status</label>
                    <select id="agreement_status" name="agreement_status">
                        <option value="" @selected(old('agreement_status', $client->agreement_status) === '')>Select Status</option>
                        <option value="Active" @selected(old('agreement_status', $client->agreement_status) === 'Active')>Active</option>
                        <option value="Expired" @selected(old('agreement_status', $client->agreement_status) === 'Expired')>Expired</option>
                        <option value="Pending Renewal" @selected(old('agreement_status', $client->agreement_status) === 'Pending Renewal')>Pending Renewal</option>
                        <option value="Under Negotiation" @selected(old('agreement_status', $client->agreement_status) === 'Under Negotiation')>Under Negotiation</option>
                    </select>
                    @error('agreement_status')<div class="error">{{ $message }}</div>@enderror
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
            </div>

            <!-- Section 2: Billing Classification -->
            <div class="form-section">
                <h2>Billing Details</h2>

                <div class="field">
                    <label for="service_type_billing">Service Type Billing</label>
                    <input id="service_type_billing" name="service_type_billing" type="text" placeholder="e.g. IIG / NTTN / ITC" value="{{ old('service_type_billing', $client->service_type_billing) }}">
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
                    <label for="security_coverage">Security Coverage</label>
                    <input id="security_coverage" name="security_coverage" type="text" placeholder="e.g. BG Coverage / Security Cheque" value="{{ old('security_coverage', $client->security_coverage) }}">
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

                <div class="field checkbox-field">
                    <input id="legal" name="legal" type="checkbox" value="1" @checked(old('legal', $client->legal))>
                    <label for="legal">Escalate Legal Action Flag</label>
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
                    <label for="team_name">Assigned Team Name</label>
                    <input id="team_name" name="team_name" type="text" placeholder="e.g. Corporate Accounts" value="{{ old('team_name', $client->team_name) }}">
                    @error('team_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="sm_kam">Sales & Marketing KAM</label>
                    <input id="sm_kam" name="sm_kam" type="text" placeholder="KAM Name" value="{{ old('sm_kam', $client->sm_kam) }}">
                    @error('sm_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_kam">Collection KAM</label>
                    <input id="collection_kam" name="collection_kam" type="text" placeholder="KAM Name" value="{{ old('collection_kam', $client->collection_kam) }}">
                    @error('collection_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_supervisor">Collection Supervisor</label>
                    <input id="collection_supervisor" name="collection_supervisor" type="text" placeholder="Supervisor Name" value="{{ old('collection_supervisor', $client->collection_supervisor) }}">
                    @error('collection_supervisor')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nttn_billing_kam">NTTN Billing KAM</label>
                    <input id="nttn_billing_kam" name="nttn_billing_kam" type="text" placeholder="KAM Name" value="{{ old('nttn_billing_kam', $client->nttn_billing_kam) }}">
                    @error('nttn_billing_kam')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="iig_itc_billing_kam">IIG/ITC Billing KAM</label>
                    <input id="iig_itc_billing_kam" name="iig_itc_billing_kam" type="text" placeholder="KAM Name" value="{{ old('iig_itc_billing_kam', $client->iig_itc_billing_kam) }}">
                    @error('iig_itc_billing_kam')<div class="error">{{ $message }}</div>@enderror
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
