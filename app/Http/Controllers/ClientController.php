<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()->orderBy('client_name')->get();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opus_id' => ['required', 'string', 'max:50', 'unique:client,opus_id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_status' => ['nullable', 'string', 'max:50'],
            'barring_priority' => ['nullable', 'in:P1,P2'],
            'btrc_license_discontinuation_date' => ['nullable', 'date'],
            'legal' => ['boolean'],
            'service_discontinuation_date' => ['nullable', 'date'],
            'billing_modality_kpi' => ['nullable', 'string', 'max:100'],
            'service_type_billing' => ['nullable', 'string', 'max:100'],
            'license_billing' => ['nullable', 'string', 'max:100'],
            'btrc_letter' => ['nullable', 'string', 'max:255'],
            'security_coverage' => ['nullable', 'string', 'max:100'],
            'payment_plan' => ['nullable', 'string', 'max:255'],
            'other_upstream' => ['boolean'],
            'sm_kam' => ['nullable', 'string', 'max:255'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'collection_kam' => ['nullable', 'string', 'max:255'],
            'collection_supervisor' => ['nullable', 'string', 'max:255'],
            'nttn_billing_kam' => ['nullable', 'string', 'max:255'],
            'iig_itc_billing_kam' => ['nullable', 'string', 'max:255'],
            'nttn_billing_commencement_date' => ['nullable', 'date'],
            'iig_itc_billing_commencement_date' => ['nullable', 'date'],
        ]);

        // Default booleans if checkboxes are unchecked
        $data['legal'] = $request->has('legal');
        $data['other_upstream'] = $request->has('other_upstream');

        Client::create($data);

        return redirect()
            ->route('clients.index')
            ->with('status', 'Client created successfully.');
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'opus_id' => ['required', 'string', 'max:50', 'unique:client,opus_id,' . $client->client_id . ',client_id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_status' => ['nullable', 'string', 'max:50'],
            'barring_priority' => ['nullable', 'in:P1,P2'],
            'btrc_license_discontinuation_date' => ['nullable', 'date'],
            'legal' => ['boolean'],
            'service_discontinuation_date' => ['nullable', 'date'],
            'billing_modality_kpi' => ['nullable', 'string', 'max:100'],
            'service_type_billing' => ['nullable', 'string', 'max:100'],
            'license_billing' => ['nullable', 'string', 'max:100'],
            'btrc_letter' => ['nullable', 'string', 'max:255'],
            'security_coverage' => ['nullable', 'string', 'max:100'],
            'payment_plan' => ['nullable', 'string', 'max:255'],
            'other_upstream' => ['boolean'],
            'sm_kam' => ['nullable', 'string', 'max:255'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'collection_kam' => ['nullable', 'string', 'max:255'],
            'collection_supervisor' => ['nullable', 'string', 'max:255'],
            'nttn_billing_kam' => ['nullable', 'string', 'max:255'],
            'iig_itc_billing_kam' => ['nullable', 'string', 'max:255'],
            'nttn_billing_commencement_date' => ['nullable', 'date'],
            'iig_itc_billing_commencement_date' => ['nullable', 'date'],
        ]);

        $data['legal'] = $request->has('legal');
        $data['other_upstream'] = $request->has('other_upstream');

        $client->update($data);

        return redirect()
            ->route('clients.index')
            ->with('status', 'Client updated successfully.');
    }

    public function discontinue(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'service_discontinuation_date' => ['required', 'date'],
        ]);

        $client->update([
            'client_status' => 'Discontinued',
            'service_discontinuation_date' => $request->input('service_discontinuation_date'),
        ]);

        return redirect()
            ->route('clients.index')
            ->with('status', 'Client discontinued successfully.');
    }
}
