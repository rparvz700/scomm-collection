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

        $latestMonth = \App\Models\MonthlySummary::max('summary_month');
        $summaries = collect();
        $latestMonthStr = null;

        if ($latestMonth) {
            $latestMonthStr = \Carbon\Carbon::parse($latestMonth)->format('Y-m');
            $summaries = \App\Models\MonthlySummary::whereDate('summary_month', $latestMonth)
                ->get()
                ->keyBy('client_id');
        }

        $crRanges = config('risk.ranges') ?: [
            ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50, 'category' => 'Best'],
            ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00, 'category' => 'Good'],
            ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50, 'category' => 'Moderate'],
            ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99, 'category' => 'Risky'],
            ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49, 'category' => 'High Risky'],
            ['label' => '>= 3.50', 'min' => 3.50, 'max' => null, 'category' => 'Most Risky'],
        ];

        foreach ($clients as $client) {
            $summary = $summaries->get($client->client_id);
            
            $cr = $summary ? (float) $summary->latest_cr : null;
            $client->current_month_cr = $cr !== null ? number_format($cr, 2) : 'N/A';
            
            // Segment Name
            $isIig = str_contains(strtolower((string) $client->service_type_billing), 'iig');
            $client->segment_name = $isIig ? 'IIG Operators' : 'ISP & Other Operators';

            // CR Range and Risk Category
            $rangeLabel = 'N/A';
            $categoryLabel = 'N/A';
            if ($cr !== null) {
                foreach ($crRanges as $range) {
                    if ((float)$range['min'] === 0.0) {
                        if ($cr <= $range['max']) {
                            $rangeLabel = $range['label'];
                            $categoryLabel = $range['category'] ?? 'N/A';
                            break;
                        }
                    } else {
                        if ($cr >= $range['min'] && ($range['max'] === null || $cr <= $range['max'])) {
                            $rangeLabel = $range['label'];
                            $categoryLabel = $range['category'] ?? 'N/A';
                            break;
                        }
                    }
                }
            }
            $client->risk_segment = $categoryLabel;
            $client->cr_range = $rangeLabel;
            $client->summary_month = $latestMonthStr;
        }

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
            'agreement_status' => ['nullable', 'string', 'max:100'],
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
            'agreement_status' => ['nullable', 'string', 'max:100'],
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

    public function logs(Client $client): \Illuminate\Http\JsonResponse
    {
        $logs = \App\Models\ClientLog::query()
            ->where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->created_at->format('d M Y H:i'),
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });

        return response()->json($logs);
    }
}
