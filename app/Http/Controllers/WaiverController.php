<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MonthlySummary;
use App\Models\MonthlySummaryDiscontinued;
use App\Models\Waiver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WaiverController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isTeamScoped = $user && !$user->can('view all clients') && !$user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin']);

        $query = Waiver::query()->with('client')->latest('applicable_from_date');

        if ($isTeamScoped) {
            $userId = $user->id;
            $userName = strtolower(trim($user->name));
            $userEmail = $user->email;

            $query->where(function ($q) use ($userId, $userName, $userEmail) {
                $q->where('created_by', $userName)
                  ->orWhere('created_by', $userEmail)
                  ->orWhereHas('client', function ($cq) use ($userId, $userName) {
                      $cq->where('collection_kam_id', $userId)
                        ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                        ->orWhere('sm_kam_id', $userId)
                        ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                        ->orWhere('nttn_billing_kam_id', $userId)
                        ->orWhereRaw('LOWER(nttn_billing_kam) = ?', [$userName])
                        ->orWhere('iig_itc_billing_kam_id', $userId)
                        ->orWhereRaw('LOWER(iig_itc_billing_kam) = ?', [$userName])
                        ->orWhere('collection_supervisor_id', $userId)
                        ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName])
                        ->orWhere('sm_supervisor_id', $userId)
                        ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName]);
                  });
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applicable_from_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applicable_from_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('waiver_amount', 'like', "%{$search}%")
                  ->orWhere('created_by', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $waivers = $query->paginate($perPage)->withQueryString();

        $clientsQuery = Client::query()->orderBy('client_name');
        if ($isTeamScoped) {
            $userId = $user->id;
            $userName = strtolower(trim($user->name));
            $clientsQuery->where(function ($q) use ($userId, $userName) {
                $q->where('collection_kam_id', $userId)
                  ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                  ->orWhere('sm_kam_id', $userId)
                  ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                  ->orWhere('nttn_billing_kam_id', $userId)
                  ->orWhereRaw('LOWER(nttn_billing_kam) = ?', [$userName])
                  ->orWhere('iig_itc_billing_kam_id', $userId)
                  ->orWhereRaw('LOWER(iig_itc_billing_kam) = ?', [$userName])
                  ->orWhere('collection_supervisor_id', $userId)
                  ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName])
                  ->orWhere('sm_supervisor_id', $userId)
                  ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName]);
            });
        }
        $clients = $clientsQuery->get();

        return view('waivers.index', [
            'waivers' => $waivers,
            'clients' => $clients,
            'selectedClientId' => $request->client_id,
            'selectedDateFrom' => $request->date_from,
            'selectedDateTo' => $request->date_to,
            'searchText' => $request->search,
            'perPage' => $perPage,
        ]);
    }

    public function clientBreakdown(Request $request, Client $client): JsonResponse
    {
        $dateParam = $request->query('date') ?? $request->query('applicable_date') ?? $request->query('applicable_from_date');
        $targetCarbon = null;
        if (!empty($dateParam)) {
            try {
                $targetCarbon = Carbon::parse($dateParam);
            } catch (\Exception $e) {
                $targetCarbon = null;
            }
        }

        $activeQuery = MonthlySummary::where('client_id', $client->client_id);
        $discQuery = MonthlySummaryDiscontinued::where('client_id', $client->client_id);

        $isFallback = false;
        if ($targetCarbon) {
            $activeSummary = (clone $activeQuery)
                ->whereYear('summary_month', $targetCarbon->year)
                ->whereMonth('summary_month', $targetCarbon->month)
                ->orderByDesc('summary_month')
                ->first();

            $discSummary = (clone $discQuery)
                ->whereYear('summary_month', $targetCarbon->year)
                ->whereMonth('summary_month', $targetCarbon->month)
                ->orderByDesc('summary_month')
                ->first();

            // If no summary exists for this specific month, check if it's current or future
            if (!$activeSummary && !$discSummary) {
                $targetMonthStart = $targetCarbon->copy()->startOfMonth();
                $currentMonthStart = Carbon::now()->startOfMonth();

                if ($targetMonthStart->greaterThanOrEqualTo($currentMonthStart)) {
                    $activeSummary = (clone $activeQuery)->orderByDesc('summary_month')->first();
                    $discSummary = (clone $discQuery)->orderByDesc('summary_month')->first();
                    $isFallback = true;
                }
            }
        } else {
            $activeSummary = $activeQuery->orderByDesc('summary_month')->first();
            $discSummary = $discQuery->orderByDesc('summary_month')->first();
        }

        $isDiscontinued = false;
        $summary = null;

        if ($activeSummary && $discSummary) {
            if ($discSummary->summary_month >= $activeSummary->summary_month && $client->client_status !== 'Active') {
                $summary = $discSummary;
                $isDiscontinued = true;
            } else {
                $summary = $activeSummary;
            }
        } elseif ($activeSummary) {
            $summary = $activeSummary;
        } elseif ($discSummary) {
            $summary = $discSummary;
            $isDiscontinued = true;
        }

        if (!$summary) {
            $noDataLabel = $targetCarbon ? $targetCarbon->format('F Y') . ' (No Summary)' : 'No Summary Available';
            return response()->json([
                'success' => true,
                'client_id' => $client->client_id,
                'client_name' => $client->client_name,
                'client_status' => $client->client_status ?? 'Unknown',
                'summary_month' => null,
                'summary_month_label' => $noDataLabel,
                'is_active' => $client->client_status === 'Active',
                'components' => [],
                'totals' => [
                    'postpaid_opening' => 0.00,
                    'postpaid_latest' => 0.00,
                    'prepaid_opening' => 0.00,
                    'prepaid_latest' => 0.00,
                    'grand_opening' => 0.00,
                    'grand_latest' => 0.00,
                ],
            ]);
        }

        $summaryMonth = Carbon::parse($summary->summary_month)->format('Y-m-d');
        if ($isFallback && $targetCarbon) {
            $summaryMonthLabel = $targetCarbon->format('F Y') . ' (Latest as of ' . Carbon::parse($summary->summary_month)->format('M Y') . ')';
        } elseif ($targetCarbon && $targetCarbon->format('Y-m') === Carbon::parse($summary->summary_month)->format('Y-m')) {
            $summaryMonthLabel = $targetCarbon->format('F Y');
        } else {
            $summaryMonthLabel = Carbon::parse($summary->summary_month)->format('F Y');
        }

        if ($isDiscontinued) {
            $opening = (float) ($summary->opening_os ?? 0);
            $latest = (float) ($summary->latest_os ?? 0);
            $modality = !empty($client->billing_modality_kpi) ? $client->billing_modality_kpi : 'Postpaid';
            $license = !empty($client->license_billing) ? $client->license_billing : 'Discontinued';

            $components = [
                [
                    'key' => 'discontinued_os',
                    'modality' => $modality,
                    'license' => $license,
                    'opening_os' => $opening,
                    'latest_os' => $latest,
                ]
            ];

            return response()->json([
                'success' => true,
                'client_id' => $client->client_id,
                'client_name' => $client->client_name,
                'client_status' => $client->client_status ?? 'Discontinued',
                'summary_month' => $summaryMonth,
                'summary_month_label' => $summaryMonthLabel,
                'is_active' => false,
                'components' => $components,
                'totals' => [
                    'postpaid_opening' => strcasecmp($modality, 'prepaid') === 0 ? 0.00 : $opening,
                    'postpaid_latest' => strcasecmp($modality, 'prepaid') === 0 ? 0.00 : $latest,
                    'prepaid_opening' => strcasecmp($modality, 'prepaid') === 0 ? $opening : 0.00,
                    'prepaid_latest' => strcasecmp($modality, 'prepaid') === 0 ? $latest : 0.00,
                    'grand_opening' => $opening,
                    'grand_latest' => $latest,
                ],
            ]);
        }

        // Active client standard components
        $componentDefs = [
            ['key' => 'postpaid_nttn', 'modality' => 'Postpaid', 'license' => 'NTTN'],
            ['key' => 'postpaid_iig_nttn', 'modality' => 'Postpaid', 'license' => 'NTTN + IIG'],
            ['key' => 'postpaid_iig', 'modality' => 'Postpaid', 'license' => 'IIG'],
            ['key' => 'postpaid_itc', 'modality' => 'Postpaid', 'license' => 'ITC'],
            ['key' => 'postpaid_nix', 'modality' => 'Postpaid', 'license' => 'NIX'],
            ['key' => 'prepaid_nttn', 'modality' => 'Prepaid', 'license' => 'NTTN'],
            ['key' => 'prepaid_iig_nttn', 'modality' => 'Prepaid', 'license' => 'NTTN + IIG'],
            ['key' => 'prepaid_iig', 'modality' => 'Prepaid', 'license' => 'IIG'],
            ['key' => 'prepaid_itc', 'modality' => 'Prepaid', 'license' => 'ITC'],
            ['key' => 'prepaid_nix', 'modality' => 'Prepaid', 'license' => 'NIX'],
        ];

        $components = [];
        foreach ($componentDefs as $def) {
            $openCol = 'opening_os_' . $def['key'];
            $latestCol = 'latest_os_' . $def['key'];
            $components[] = [
                'key' => $def['key'],
                'modality' => $def['modality'],
                'license' => $def['license'],
                'opening_os' => (float) ($summary->$openCol ?? 0.00),
                'latest_os' => (float) ($summary->$latestCol ?? 0.00),
            ];
        }

        $grandOpening = (float) ($summary->total_opening_os ?? 0.00);
        $grandLatest = (float) ($summary->total_latest_os ?? 0.00);
        $postpaidOpening = (float) ($summary->total_opening_os_postpaid ?? 0.00);
        $postpaidLatest = (float) ($summary->latest_os_balance_postpaid ?? 0.00);
        $prepaidOpening = (float) ($summary->total_opening_os_prepaid ?? 0.00);
        $prepaidLatest = (float) ($summary->latest_os_balance_prepaid ?? 0.00);

        return response()->json([
            'success' => true,
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'client_status' => $client->client_status ?? 'Active',
            'summary_month' => $summaryMonth,
            'summary_month_label' => $summaryMonthLabel,
            'is_active' => true,
            'components' => $components,
            'totals' => [
                'postpaid_opening' => $postpaidOpening,
                'postpaid_latest' => $postpaidLatest,
                'prepaid_opening' => $prepaidOpening,
                'prepaid_latest' => $prepaidLatest,
                'grand_opening' => $grandOpening,
                'grand_latest' => $grandLatest,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:client,client_id'],
            'applicable_from_date' => ['required', 'date'],
            'waiver_amount' => ['required', 'numeric', 'min:0'],
            'subtract_from_next_month_os' => ['nullable'],
            'breakdown_config' => ['nullable'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,doc,docx,xls,xlsx'],
        ]);

        $breakdownConfig = null;
        if (!empty($validated['breakdown_config'])) {
            $breakdownConfig = is_array($validated['breakdown_config'])
                ? $validated['breakdown_config']
                : json_decode($validated['breakdown_config'], true);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $dateClean = Carbon::parse($validated['applicable_from_date'])->format('Ymd');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'waiver' . $validated['client_id'] . $dateClean . ($ext ? '.' . $ext : '');
            $attachmentName = $fileName;
            
            $uploadDir = public_path('uploads/waivers');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }
            
            $file->move($uploadDir, $fileName);
            $attachmentPath = 'uploads/waivers/' . $fileName;
        }

        $creatorName = $request->user()?->name ?? $request->user()?->email ?? 'System';

        $applicableFrom = Carbon::parse($validated['applicable_from_date']);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $isCurrentOrFuture = $applicableFrom->greaterThanOrEqualTo($currentMonthStart);
        $subtractFromNextMonth = $isCurrentOrFuture ? $request->boolean('subtract_from_next_month_os') : false;

        $waiver = Waiver::create([
            'client_id' => $validated['client_id'],
            'applicable_from_date' => $validated['applicable_from_date'],
            'waiver_amount' => $validated['waiver_amount'],
            'subtract_from_next_month_os' => $subtractFromNextMonth,
            'breakdown_config' => $breakdownConfig,
            'description' => $validated['description'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'created_by' => $creatorName,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver entry created successfully.',
                'waiver' => $waiver,
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver entry created successfully.');
    }

    public function update(Request $request, Waiver $waiver): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:client,client_id'],
            'applicable_from_date' => ['required', 'date'],
            'waiver_amount' => ['required', 'numeric', 'min:0'],
            'subtract_from_next_month_os' => ['nullable'],
            'breakdown_config' => ['nullable'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,doc,docx,xls,xlsx'],
        ]);

        $breakdownConfig = $waiver->breakdown_config;
        if ($request->has('breakdown_config')) {
            $val = $request->input('breakdown_config');
            $breakdownConfig = is_array($val) ? $val : json_decode($val, true);
        }

        $attachmentPath = $waiver->attachment_path;
        $attachmentName = $waiver->attachment_name;

        if ($request->hasFile('attachment')) {
            if ($attachmentPath && File::exists(public_path($attachmentPath))) {
                File::delete(public_path($attachmentPath));
            }

            $file = $request->file('attachment');
            $dateClean = Carbon::parse($validated['applicable_from_date'])->format('Ymd');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'waiver' . $validated['client_id'] . $dateClean . ($ext ? '.' . $ext : '');
            $attachmentName = $fileName;
            
            $uploadDir = public_path('uploads/waivers');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }
            
            $file->move($uploadDir, $fileName);
            $attachmentPath = 'uploads/waivers/' . $fileName;
        }

        $applicableFrom = Carbon::parse($validated['applicable_from_date']);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $isCurrentOrFuture = $applicableFrom->greaterThanOrEqualTo($currentMonthStart);
        $subtractFromNextMonth = $isCurrentOrFuture ? $request->boolean('subtract_from_next_month_os') : false;

        $waiver->update([
            'client_id' => $validated['client_id'],
            'applicable_from_date' => $validated['applicable_from_date'],
            'waiver_amount' => $validated['waiver_amount'],
            'subtract_from_next_month_os' => $subtractFromNextMonth,
            'breakdown_config' => $breakdownConfig,
            'description' => $validated['description'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver entry updated successfully.',
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver entry updated successfully.');
    }

    public function destroy(Request $request, Waiver $waiver): RedirectResponse|JsonResponse
    {
        if ($waiver->attachment_path && File::exists(public_path($waiver->attachment_path))) {
            File::delete(public_path($waiver->attachment_path));
        }

        $waiver->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Waiver deleted successfully.',
            ]);
        }

        return redirect()->route('waivers.index')->with('status', 'Waiver deleted successfully.');
    }

    public function showAttachment(Waiver $waiver)
    {
        if (!$waiver->attachment_path || !File::exists(public_path($waiver->attachment_path))) {
            abort(404, 'Attachment file not found.');
        }

        $fullPath = public_path($waiver->attachment_path);
        $mimeType = File::mimeType($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . ($waiver->attachment_name ?? basename($fullPath)) . '"',
        ]);
    }
}
