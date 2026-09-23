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
        $clients = Client::query()
            ->with('growthTrend')
            ->orderBy('client_name')
            ->get();

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

        $improvingCount = 0;
        $decliningCount = 0;

        foreach ($clients as $client) {
            $summary = $summaries->get($client->client_id);
            
            $cr = $summary ? (float) $summary->latest_cr : null;
            $client->current_month_cr = $cr !== null ? number_format($cr, 2) : 'N/A';
            
            // Segment Name
            $isIig = str_contains(strtolower((string) $client->license_billing), 'iig');
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

            // Growth Trend attributes from pre-calculated model
            $trend = $client->growthTrend;
            $trendStatus = $trend?->trend_status ?? 'stable';
            $mrcChangePct = (float) ($trend?->mrc_change_pct ?? 0.0);
            $crChangeVal = (float) ($trend?->cr_change_val ?? 0.0);

            $client->unsetRelation('growthTrend');
            $client->growth_trend = $trendStatus;
            $client->growth_trend_status = $trendStatus;
            $client->mrc_change_pct = $mrcChangePct;
            $client->cr_change_val = $crChangeVal;

            if ($trendStatus === 'improving') {
                $improvingCount++;
            } elseif ($trendStatus === 'declining') {
                $decliningCount++;
            }
        }

        return view('clients.index', compact('clients', 'improvingCount', 'decliningCount'));
    }

    public function create(): View
    {
        $users = \App\Models\User::orderBy('name')->get();
        return view('clients.create', compact('users'));
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
            'sm_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'collection_kam' => ['nullable', 'string', 'max:255'],
            'collection_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'collection_supervisor' => ['nullable', 'string', 'max:255'],
            'collection_supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'nttn_billing_kam' => ['nullable', 'string', 'max:255'],
            'nttn_billing_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'iig_itc_billing_kam' => ['nullable', 'string', 'max:255'],
            'iig_itc_billing_kam_id' => ['nullable', 'integer', 'exists:users,id'],
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
        $users = \App\Models\User::orderBy('name')->get();
        return view('clients.edit', compact('client', 'users'));
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
            'sm_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'collection_kam' => ['nullable', 'string', 'max:255'],
            'collection_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'collection_supervisor' => ['nullable', 'string', 'max:255'],
            'collection_supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'nttn_billing_kam' => ['nullable', 'string', 'max:255'],
            'nttn_billing_kam_id' => ['nullable', 'integer', 'exists:users,id'],
            'iig_itc_billing_kam' => ['nullable', 'string', 'max:255'],
            'iig_itc_billing_kam_id' => ['nullable', 'integer', 'exists:users,id'],
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
                $formattedDate = 'N/A';
                if ($log->created_at) {
                    try {
                        $formattedDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                    } catch (\Exception $e) {
                        $formattedDate = 'N/A';
                    }
                }

                return [
                    'date' => $formattedDate,
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });

        return response()->json($logs);
    }

    private function getCombinedMonthlySummaryRows(Client $client)
    {
        $msRows = \App\Models\MonthlySummary::query()
            ->where('client_id', $client->client_id)
            ->get();

        $msdRows = \App\Models\MonthlySummaryDiscontinued::query()
            ->where('client_id', $client->client_id)
            ->get();

        $allRows = $msRows->concat($msdRows);

        $grouped = $allRows->groupBy(function ($row) {
            return $row->summary_month ? $row->summary_month->format('Y-m') : '';
        });

        $merged = collect();

        foreach ($grouped as $monthKey => $rowsInMonth) {
            if (empty($monthKey)) continue;

            if ($rowsInMonth->count() === 1) {
                $merged->push($rowsInMonth->first());
            } else {
                $selected = $rowsInMonth->sortByDesc(function ($r) {
                    $status = strtolower((string) ($r->client_status ?? ''));
                    $isDiscontinued = $status === 'discontinued' || $r instanceof \App\Models\MonthlySummaryDiscontinued;
                    $updatedAt = $r->updated_at ? $r->updated_at->timestamp : 0;
                    return ($isDiscontinued ? 10000000000 : 0) + $updatedAt;
                })->first();

                $merged->push($selected);
            }
        }

        return $merged->sortByDesc(function ($row) {
            return $row->summary_month ? $row->summary_month->format('Y-m-d') : '';
        })->take(12)->sortBy(function ($row) {
            return $row->summary_month ? $row->summary_month->format('Y-m-d') : '';
        })->values();
    }

    public function outstandingSummary(Client $client): \Illuminate\Http\JsonResponse
    {
        $rows = $this->getCombinedMonthlySummaryRows($client);

        $monthlySummaryRows = $rows->map(function ($row) use ($client) {
            $maturedMrc = (float) ($row->total_maturity ?? 0);
            $backlogCommitment = (float) ($row->total_payment_plan ?? 0);
            $totalCommitment = $maturedMrc + $backlogCommitment;
            $totalCollection = (float) ($row->total_collection ?? $row->collection_amount ?? 0);

            $shortfallMaturedMrc = $totalCollection - $maturedMrc;
            $shortfallTotalCommitment = $totalCollection - $totalCommitment;

            $openingOs = (float) ($row->total_opening_os ?? $row->opening_os ?? 0);
            $closingOs = (float) ($row->total_latest_os ?? $row->latest_os ?? 0);

            return [
                'client_status' => $row->client_status ?? $client->client_status ?? 'Active',
                'payment_month' => $row->summary_month ? $row->summary_month->format('M-y') : 'N/A',
                'summary_month_raw' => $row->summary_month ? $row->summary_month->format('Y-m-d') : null,
                'opening_outstanding' => $openingOs,
                'opening_outstanding_formatted' => number_format($openingOs),
                'opening_cr' => (float) ($row->opening_cr ?? 0),
                'opening_cr_formatted' => number_format((float) ($row->opening_cr ?? 0), 1),
                
                'matured_mrc' => $maturedMrc,
                'matured_mrc_formatted' => number_format($maturedMrc),
                
                'backlog_commitment' => $backlogCommitment,
                'backlog_commitment_formatted' => number_format($backlogCommitment),
                
                'total_commitment' => $totalCommitment,
                'total_commitment_formatted' => number_format($totalCommitment),
                
                'total_collection' => $totalCollection,
                'total_collection_formatted' => number_format($totalCollection),
                
                'shortfall_matured_mrc' => $shortfallMaturedMrc,
                'shortfall_matured_mrc_formatted' => $this->formatCurrencyWithParentheses($shortfallMaturedMrc),
                'shortfall_matured_mrc_is_negative' => $shortfallMaturedMrc < 0,
                
                'shortfall_total_commitment' => $shortfallTotalCommitment,
                'shortfall_total_commitment_formatted' => $this->formatCurrencyWithParentheses($shortfallTotalCommitment),
                'shortfall_total_commitment_is_negative' => $shortfallTotalCommitment < 0,
                
                'closing_outstanding' => $closingOs,
                'closing_outstanding_formatted' => number_format($closingOs),
                
                'closing_cr' => (float) ($row->latest_cr ?? 0),
                'closing_cr_formatted' => number_format((float) ($row->latest_cr ?? 0), 1),
                
                'remarks' => $row->current_month_remarks ?? $row->sales_review_remarks ?? $row->visit_remarks ?? '',
            ];
        })->values();

        $securityCoverage = $client->security_coverage ?? 'N/A';
        $creditPeriod = $client->billing_modality_kpi ?? 'N/A';
        $serviceType = strtolower((string) $client->service_type_billing);
        $modality = str_contains($serviceType, 'prepaid') ? 'Pre-paid' : 'Post-paid';

        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'modality' => $modality,
            'header_title' => "Outstanding Summary of {$client->client_name}-{$modality}",
            'credit_period_note' => $creditPeriod ? "*{$creditPeriod}" : '*Standard Credit Period Client',
            'security_coverage' => $securityCoverage,
            'rows' => $monthlySummaryRows,
        ]);
    }

    private function formatCurrencyWithParentheses(float $val): string
    {
        if ($val < 0) {
            return '(' . number_format(abs($val)) . ')';
        }
        return number_format($val);
    }

    public function clientTrend(Client $client): \Illuminate\Http\JsonResponse
    {
        $rows = $this->getCombinedMonthlySummaryRows($client);

        $logs = \App\Models\ClientLog::query()
            ->where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                $formattedDate = 'N/A';
                if ($log->created_at) {
                    try {
                        $formattedDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                    } catch (\Exception $e) {
                        $formattedDate = 'N/A';
                    }
                }

                return [
                    'date' => $formattedDate,
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });

        $snapshots = $rows->map(function ($row) use ($client) {
            return [
                'client_status' => $row->client_status ?? $client->client_status ?? 'Active',
                'month' => $row->summary_month ? $row->summary_month->format('M Y') : 'N/A',
                'opening_rating' => $row->opening_rating_category ?? 'N/A',
                'latest_rating' => $row->latest_rating_category ?? 'N/A',
                'opening_cr' => number_format((float) ($row->opening_cr ?? 0), 2),
                'closing_cr' => number_format((float) ($row->latest_cr ?? 0), 2),
            ];
        })->values();

        return response()->json([
            'labels' => $rows->map(
                fn ($row) => $row->summary_month ? $row->summary_month->format('M y') : ''
            )->values(),
            'opening_cr' => $rows->pluck('opening_cr')->map(fn ($v) => (float) ($v ?? 0))->values(),
            'opening_os' => $rows->map(fn ($row) => (float) ($row->total_opening_os ?? $row->opening_os ?? 0))->values(),
            'closing_cr' => $rows->pluck('latest_cr')->map(fn ($v) => (float) ($v ?? 0))->values(),
            'closing_os' => $rows->map(fn ($row) => (float) ($row->total_latest_os ?? $row->latest_os ?? 0))->values(),
            'mrc' => $rows->map(fn ($row) => (float) ($row->total_mrc ?? $row->mrc ?? 0))->values(),
            'backlog' => $rows->map(fn ($row) => (float) ($row->net_backlog_total ?? $row->backlog ?? 0))->values(),
            'collection' => $rows->map(fn ($row) => (float) ($row->total_collection ?? $row->collection_amount ?? 0))->values(),
            'logs' => $logs,
            'snapshots' => $snapshots,
            'client_status' => $client->client_status ?? 'N/A',
            'service_discontinuation_date' => $client->service_discontinuation_date?->format('Y-m-d') ?? 'N/A',
            'opening_rating_category' => $rows->first()?->opening_rating_category ?? 'N/A',
            'latest_rating_category' => $rows->last()?->latest_rating_category ?? 'N/A',
        ]);
    }

    public function discontinuedClientTrend(Client $client): \Illuminate\Http\JsonResponse
    {
        $rows = $this->getCombinedMonthlySummaryRows($client);

        $logs = \App\Models\ClientLog::query()
            ->where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                $formattedDate = 'N/A';
                if ($log->created_at) {
                    try {
                        $formattedDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                    } catch (\Exception $e) {
                        $formattedDate = 'N/A';
                    }
                }

                return [
                    'date' => $formattedDate,
                    'field' => ucwords(str_replace('_', ' ', $log->field_name)),
                    'old' => $log->old_value ?? 'N/A',
                    'new' => $log->new_value ?? 'N/A',
                    'user' => $log->updated_by ?? 'System',
                ];
            });

        $snapshots = $rows->map(function ($row) use ($client) {
            return [
                'client_status' => $row->client_status ?? $client->client_status ?? 'Discontinued',
                'month' => $row->summary_month ? $row->summary_month->format('M Y') : 'N/A',
                'opening_os' => number_format((float) ($row->opening_os ?? $row->total_opening_os ?? 0), 2),
                'collection' => number_format((float) ($row->collection_amount ?? $row->total_collection ?? 0), 2),
                'latest_os' => number_format((float) ($row->latest_os ?? $row->total_latest_os ?? 0), 2),
                'unbilled_total' => number_format((float) ($row->unbilled_total ?? 0), 2),
            ];
        })->values();

        return response()->json([
            'labels' => $rows->map(
                fn ($row) => $row->summary_month ? $row->summary_month->format('M y') : ''
            )->values(),
            'opening_os' => $rows->map(fn ($row) => (float) ($row->opening_os ?? $row->total_opening_os ?? 0))->values(),
            'closing_os' => $rows->map(fn ($row) => (float) ($row->latest_os ?? $row->total_latest_os ?? 0))->values(),
            'collection' => $rows->map(fn ($row) => (float) ($row->collection_amount ?? $row->total_collection ?? 0))->values(),
            'unbilled_total' => $rows->pluck('unbilled_total')->map(fn ($v) => (float) ($v ?? 0))->values(),
            'logs' => $logs,
            'snapshots' => $snapshots,
            'client_status' => $client->client_status ?? 'N/A',
            'service_discontinuation_date' => $client->service_discontinuation_date?->format('Y-m-d') ?? 'N/A',
            'opening_rating_category' => 'N/A',
            'latest_rating_category' => 'N/A',
        ]);
    }
}
