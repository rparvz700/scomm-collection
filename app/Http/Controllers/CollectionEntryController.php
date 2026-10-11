<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Collection;
use App\Models\MonthlySummary;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollectionEntryController extends Controller
{
    public static function getDepositBanks(): array
    {
        return [
            'AB-1000 NTTN',
            'AB-1430 IIG',
            'AB-1431 ITC',
            'BRAC-1 NTTN',
            'BRAC-2 IIG',
            'BRAC-3 ITC',
            'BRAC-4 ICX',
            'Challan',
            'DBBL-4478 ITC',
            'DBBL-4480 ICX',
            'DBBL-4491 IIG',
            'DBBL-894 NTTN',
            'Dhaka-1612 IIG',
            'Dhaka-9962 NTTN',
            'EBL-10 NTTN',
            'EBL-434 NTTN',
            'EBL-87 IIG',
            'IFIC-8001 NTTN',
            'IFIC-8041 IIG',
            'NCC-1037 IIG',
            'NCC-1083 NTTN',
            'One-1365  NTTN',
            'One-1634  ICX',
            'One-1645  ITC',
            'One-1656  IIG',
            'Prime-6628 NTTN',
            'Prime-7196',
            'SJIBL-215 NTTN',
            'SJIBL-900 ICX',
            'SJIBL-901 ITC',
            'SJIBL-902 IIG',
            'Sonali-291 IIG',
            'Sonali-699 NTTN',
            'The City-001-NTTN',
            'Trust-159 ITC',
            'Trust-211 NTTN',
            'Trust-293 IIG',
            'Trust-304 NIX',
            'UCB-554 IIG',
            'UCB-565 ICX',
            'UCB-576 ITC',
            'UCB-79 NTTN',
        ];
    }

    public static function getClientBanks(): array
    {
        return [
            'AB',
            'Agrani',
            'Al-Arafah',
            'Bangladesh Bank',
            'Bangladesh Commerce',
            'Bangladesh Development',
            'Bank Al-Falah',
            'Bank Asia',
            'Bank of Ceylon',
            'BASIC',
            'Bengal Commercial',
            'BRAC',
            'Citibank N.A',
            'Citizens',
            'Community',
            'DBBL',
            'Dhaka',
            'Eastern',
            'EXIM',
            'First Security',
            'Global Islami',
            'Habib Bank',
            'HSBC',
            'ICB Islamic',
            'IFIC',
            'Islami Bank',
            'Jamuna',
            'Janata',
            'Jubilee',
            'Karmashangosthan',
            'Krishi',
            'Meghna',
            'Mercantile',
            'Midland',
            'Modhumoti',
            'Mutual Trust',
            'National',
            'National Bank of Pakistan',
            'NCC',
            'NRB',
            'NRBC',
            'One',
            'Padma',
            'Palli Sanchay',
            'Premier',
            'Prime',
            'Probashi Kollyan',
            'Pubali',
            'Rajshahi Krishi',
            'Rupali',
            'SBAC',
            'SCB',
            'Shahjalal Islami',
            'Shimanto',
            'Social Islami',
            'Sonali',
            'Southeast',
            'Standard',
            'State Bank of India',
            'The City',
            'Trust',
            'UCB',
            'Union',
            'Uttara',
            'Woori',
        ];
    }

    private function applyCollectionScope($query, $user)
    {
        if (!$user) return $query;

        $isFullAccess = $user->can('view all clients') ||
            $user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin']);

        if (!$isFullAccess) {
            $userId = $user->id;
            $userName = strtolower(trim($user->name));
            $userEmail = $user->email;

            $query->where(function ($q) use ($userId, $userName, $userEmail) {
                $q->where('created_by', $userEmail)
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

        return $query;
    }

    private function getValidationRules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:client,client_id'],
            'collection_datetime' => ['required', 'date'],
            'collection_month' => ['required', 'date'],
            'collection_type' => ['required', 'string'],
            'collection_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'client_bank' => ['nullable', 'string', 'max:255'],
            'deposit_bank' => ['nullable', 'string', 'max:255'],
            'cheque_no' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_expiry_date' => ['nullable', 'date'],
            'cheque_placement_date' => ['nullable', 'date'],
            'trace_no' => ['nullable', 'string', 'max:100'],
            'cheque_status' => ['nullable', 'string', 'max:50'],
            'dishonored_date' => ['nullable', 'date'],
            'dishonor_count' => ['nullable', 'integer', 'min:0'],
            'dishonor_slip' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function index(): View
    {
        $this->ensureNullableClientId();

        $latestSummary = MonthlySummary::query()->max('summary_month');
        $defaultMonthDate = $latestSummary ? Carbon::parse($latestSummary) : null;
        $defaultMonthString = $defaultMonthDate ? $defaultMonthDate->format('Y-m') : '';
        $defaultMonthFullDate = $defaultMonthDate ? $defaultMonthDate->format('Y-m-d') : '';

        $collectionsQuery = Collection::query()
            ->with('client')
            ->latest('collection_datetime');

        $user = auth()->user();
        $this->applyCollectionScope($collectionsQuery, $user);

        $collections = $collectionsQuery->take(15)->get();

        $clientsQuery = Client::query()->orderBy('client_name');
        if ($user && !$user->can('view all clients') && !$user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin'])) {
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

        return view('collection-entry.index', [
            'clients' => $clientsQuery->get(),
            'collections' => $collections,
            'collectionTypes' => [
                'postpaid_nttn',
                'postpaid_nttn_iig',
                'postpaid_iig',
                'postpaid_itc',
                'postpaid_nix',
                'prepaid_nttn',
                'prepaid_nttn_iig',
                'prepaid_iig',
                'prepaid_itc',
                'prepaid_nix',
            ],
            'paymentModes' => [
                'cash',
                'cheque',
                'pay_order',
                'bank_transfer',
                'eft',
                'other',
            ],
            'chequeStatuses' => [
                'pending',
                'honored',
                'dishonored',
            ],
            'depositBanks' => self::getDepositBanks(),
            'clientBanks' => self::getClientBanks(),
            'defaultMonthString' => $defaultMonthString,
            'defaultMonthFullDate' => $defaultMonthFullDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureNullableClientId();
        $rules = $this->getValidationRules();

        if ($request->has('batch_data')) {
            $batch = json_decode($request->input('batch_data'), true);
            if (is_array($batch) && !empty($batch)) {
                \DB::transaction(function() use ($batch, $request, $rules) {
                    foreach ($batch as $entry) {
                        $rawClientId = $entry['client_id'] ?? null;
                        $cId = ($rawClientId === 'untraced' || empty($rawClientId)) ? null : $rawClientId;
                        $entryData = array_merge($entry, ['client_id' => $cId]);

                        $validated = validator($entryData, $rules)->validate();
                        $validated['created_by'] = $request->user()?->email;
                        Collection::query()->create($validated);
                    }
                });

                return redirect()
                    ->route('collection-entry.index')
                    ->with('status', count($batch) . ' collection entries saved successfully.');
            }
        }

        $rawClientId = $request->input('client_id');
        $cId = ($rawClientId === 'untraced' || empty($rawClientId)) ? null : $rawClientId;
        $requestData = array_merge($request->all(), ['client_id' => $cId]);

        $data = validator($requestData, $rules)->validate();
        $data['created_by'] = $request->user()?->email;

        Collection::query()->create($data);

        return redirect()
            ->route('collection-entry.index')
            ->with('status', 'Collection entry saved.');
    }

    public function clientMetrics(Request $request): JsonResponse
    {
        $clientId = $request->client_id;
        $monthString = $request->month;

        if (!$clientId || $clientId === 'untraced' || !$monthString) {
            return response()->json([
                'total_latest_os' => 0,
                'total_mrc' => 0
            ]);
        }

        try {
            $date = Carbon::parse($monthString);

            $summary = MonthlySummary::query()
                ->where('client_id', $clientId)
                ->whereMonth('summary_month', $date->month)
                ->whereYear('summary_month', $date->year)
                ->first();

            return response()->json([
                'total_latest_os' => (float) ($summary?->total_latest_os ?? 0),
                'total_mrc' => (float) ($summary?->total_mrc ?? 0),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'total_latest_os' => 0,
                'total_mrc' => 0,
            ]);
        }
    }

    public function recentEntries(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');

        $query = Collection::query()
            ->with('client')
            ->latest('collection_datetime');

        $user = auth()->user();
        $this->applyCollectionScope($query, $user);

        $query->take(15);

        if ($clientId) {
            if ($clientId === 'untraced') {
                $query->whereNull('client_id');
            } else {
                $query->where('client_id', $clientId);
            }
        }

        $collections = $query->get()->map(function ($collection) {
            return [
                'client_name' => $collection->client->client_name ?? 'Untraced Collection',
                'collection_type' => str_replace('_', ' ', $collection->collection_type),
                'collection_amount' => number_format((float) $collection->collection_amount, 2),
                'collection_date' => $collection->collection_datetime ? $collection->collection_datetime->format('d M Y') : 'N/A',
                'payment_mode' => $collection->payment_mode ? str_replace('_', ' ', strtoupper($collection->payment_mode)) : '',
                'cheque_no' => $collection->cheque_no ?? '',
                'cheque_status' => $collection->cheque_status ?? '',
            ];
        });

        return response()->json($collections);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Collection::query()
            ->with('client')
            ->latest('collection_datetime');

        $user = auth()->user();
        $this->applyCollectionScope($query, $user);

        if ($request->filled('client_id')) {
            if ($request->client_id === 'untraced') {
                $query->whereNull('client_id');
            } else {
                $query->where('client_id', $request->client_id);
            }
        }

        if ($request->filled('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('cheque_status')) {
            $query->where('cheque_status', $request->cheque_status);
        }

        if ($request->filled('collection_month')) {
            $query->whereDate('collection_month', Carbon::parse($request->collection_month)->endOfMonth()->format('Y-m-d'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('collection_datetime', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('collection_datetime', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                  ->orWhere('cheque_no', 'like', "%{$search}%")
                  ->orWhere('client_bank', 'like', "%{$search}%")
                  ->orWhere('deposit_bank', 'like', "%{$search}%")
                  ->orWhere('trace_no', 'like', "%{$search}%")
                  ->orWhere('collection_amount', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        $collections = $query->get();

        $filenameMonth = $request->filled('collection_month') 
            ? Carbon::parse($request->collection_month)->format('M_Y') 
            : date('Ymd_His');
        $filename = "collections_export_{$filenameMonth}.csv";

        $response = new StreamedResponse(function () use ($collections) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'Collection ID',
                'Collection Datetime',
                'Collection Month',
                'OPUS Code',
                'Client Name',
                'Collection Type',
                'Payment Mode',
                'Amount (BDT)',
                'Client Bank',
                'Deposit Bank',
                'Cheque No',
                'Cheque Date',
                'Cheque Expiry Date',
                'Cheque Placement Date',
                'Trace No',
                'Cheque Status',
                'Last Dishonored Date',
                'Dishonor Count',
                'Dishonor Slip',
                'Remarks',
                'Created By',
            ]);

            foreach ($collections as $c) {
                fputcsv($handle, [
                    $c->collection_id,
                    $c->collection_datetime ? $c->collection_datetime->format('Y-m-d H:i:s') : '',
                    $c->collection_month ? $c->collection_month->format('F Y') : '',
                    $c->client?->opus_id ?? 'N/A',
                    $c->client?->client_name ?? 'Untraced Collection',
                    str_replace('_', ' ', strtoupper($c->collection_type)),
                    $c->payment_mode ? str_replace('_', ' ', strtoupper($c->payment_mode)) : '',
                    $c->collection_amount,
                    $c->client_bank ?? '',
                    $c->deposit_bank ?? '',
                    $c->cheque_no ?? '',
                    $c->cheque_date ? $c->cheque_date->format('Y-m-d') : '',
                    $c->cheque_expiry_date ? $c->cheque_expiry_date->format('Y-m-d') : '',
                    $c->cheque_placement_date ? $c->cheque_placement_date->format('Y-m-d') : '',
                    $c->trace_no ?? '',
                    $c->cheque_status ? ucfirst($c->cheque_status) : '',
                    $c->dishonored_date ? $c->dishonored_date->format('Y-m-d') : '',
                    $c->dishonor_count ?? 0,
                    $c->dishonor_slip ?? '',
                    $c->remarks ?? '',
                    $c->created_by ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function collectionsIndex(Request $request): View
    {
        $hasFilters = $request->has('client_id') || 
                      $request->has('collection_type') || 
                      $request->has('payment_mode') || 
                      $request->has('cheque_status') || 
                      $request->has('collection_month') || 
                      $request->has('date_from') || 
                      $request->has('date_to') || 
                      $request->has('search');

        $selectedMonth = $request->input('collection_month');

        if (!$hasFilters) {
            $latestMonthDate = Collection::max('collection_month');
            if ($latestMonthDate) {
                $selectedMonth = Carbon::parse($latestMonthDate)->format('Y-m');
            }
        }

        $query = Collection::query()
            ->with('client')
            ->latest('collection_datetime');

        $user = auth()->user();
        $this->applyCollectionScope($query, $user);

        if ($request->filled('client_id')) {
            if ($request->client_id === 'untraced') {
                $query->whereNull('client_id');
            } else {
                $query->where('client_id', $request->client_id);
            }
        }
        if ($request->filled('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }
        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }
        if ($request->filled('cheque_status')) {
            $query->where('cheque_status', $request->cheque_status);
        }
        if ($selectedMonth) {
            $query->whereDate('collection_month', Carbon::parse($selectedMonth)->endOfMonth()->format('Y-m-d'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('collection_datetime', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('collection_datetime', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                  ->orWhere('cheque_no', 'like', "%{$search}%")
                  ->orWhere('client_bank', 'like', "%{$search}%")
                  ->orWhere('deposit_bank', 'like', "%{$search}%")
                  ->orWhere('trace_no', 'like', "%{$search}%")
                  ->orWhere('collection_amount', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%");
                  });
                if (stripos('untraced collection', $search) !== false) {
                    $q->orWhereNull('client_id');
                }
            });
        }

        $perPage = (int)$request->input('per_page', 25);
        $collections = $query->paginate($perPage)->withQueryString();

        $clientsQuery = Client::query()->orderBy('client_name');
        if ($user && !$user->can('view all clients') && !$user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin'])) {
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

        $collectionTypes = [
            'postpaid_nttn',
            'postpaid_nttn_iig',
            'postpaid_iig',
            'postpaid_itc',
            'postpaid_nix',
            'prepaid_nttn',
            'prepaid_nttn_iig',
            'prepaid_iig',
            'prepaid_itc',
            'prepaid_nix',
        ];

        return view('collections.index', [
            'collections' => $collections,
            'clients' => $clients,
            'collectionTypes' => $collectionTypes,
            'paymentModes' => [
                'cash',
                'cheque',
                'pay_order',
                'bank_transfer',
                'eft',
                'other',
            ],
            'chequeStatuses' => [
                'pending',
                'honored',
                'dishonored',
            ],
            'depositBanks' => self::getDepositBanks(),
            'clientBanks' => self::getClientBanks(),
            'selectedClientId' => $request->client_id,
            'selectedType' => $request->collection_type,
            'selectedPaymentMode' => $request->payment_mode,
            'selectedChequeStatus' => $request->cheque_status,
            'selectedMonth' => $selectedMonth,
            'selectedDateFrom' => $request->date_from,
            'selectedDateTo' => $request->date_to,
            'searchText' => $request->search,
            'perPage' => $perPage,
        ]);
    }

    public function canUserEditCollection($user, Collection $collection): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->can('view all clients') || $user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin'])) {
            return true;
        }

        if ($collection->created_by && strtolower(trim($collection->created_by)) === strtolower(trim($user->email))) {
            return true;
        }

        $collection->loadMissing('client');
        if ($collection->client) {
            $client = $collection->client;
            $userId = $user->id;
            $userName = strtolower(trim($user->name));

            $isKamOrSupervisor = (
                $client->collection_kam_id == $userId || strtolower(trim($client->collection_kam ?? '')) === $userName ||
                $client->sm_kam_id == $userId || strtolower(trim($client->sm_kam ?? '')) === $userName ||
                $client->nttn_billing_kam_id == $userId || strtolower(trim($client->nttn_billing_kam ?? '')) === $userName ||
                $client->iig_itc_billing_kam_id == $userId || strtolower(trim($client->iig_itc_billing_kam ?? '')) === $userName ||
                $client->collection_supervisor_id == $userId || strtolower(trim($client->collection_supervisor ?? '')) === $userName ||
                $client->sm_supervisor_id == $userId || strtolower(trim($client->sm_supervisor ?? '')) === $userName
            );

            if ($isKamOrSupervisor) {
                return true;
            }
        }

        return false;
    }

    public function update(Request $request, Collection $collection)
    {
        $user = auth()->user();
        if (!$user || !$this->canUserEditCollection($user, $collection)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $this->ensureNullableClientId();

        $rawClientId = $request->input('client_id');
        $cId = ($rawClientId === 'untraced' || empty($rawClientId)) ? null : $rawClientId;
        $requestData = array_merge($request->all(), ['client_id' => $cId]);

        $validated = validator($requestData, $this->getValidationRules())->validate();

        $oldClientId = $collection->client_id;
        $oldMonth = $collection->collection_month ? \Carbon\Carbon::parse($collection->collection_month)->format('Y-m-d') : null;

        $validated['client_id'] = $cId ? (int)$cId : null;
        $validated['collection_month'] = \Carbon\Carbon::parse($validated['collection_month'])->endOfMonth()->format('Y-m-d');
        $validated['collection_datetime'] = \Carbon\Carbon::parse($validated['collection_datetime'])->format('Y-m-d H:i:s');

        // Track field updates
        foreach ($validated as $field => $newVal) {
            $oldVal = $collection->{$field};
            if ((string)$oldVal !== (string)$newVal) {
                \App\Models\CollectionLog::create([
                    'collection_id' => $collection->collection_id,
                    'client_id' => $validated['client_id'],
                    'action' => 'UPDATED',
                    'field_name' => $field,
                    'old_value' => is_null($oldVal) ? null : (string)$oldVal,
                    'new_value' => is_null($newVal) ? null : (string)$newVal,
                    'performed_by' => $user->email,
                ]);
            }
        }

        $collection->update($validated);

        $newMonth = $validated['collection_month'];
        $newClientId = $validated['client_id'];

        if ($oldClientId && ($oldClientId != $newClientId || $oldMonth != $newMonth)) {
            Collection::recalculateMonthlySummary($oldClientId, $oldMonth);
        }
        if ($newClientId) {
            Collection::recalculateMonthlySummary($newClientId, $newMonth);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection entry updated successfully.',
            ]);
        }

        return redirect()->back()->with('status', 'Collection entry updated successfully.');
    }

    public function destroy(Request $request, Collection $collection)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('collection_hod') && !$user->hasRole('admin'))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $oldClientId = $collection->client_id;
        $oldMonth = $collection->collection_month ? \Carbon\Carbon::parse($collection->collection_month)->format('Y-m-d') : null;

        \App\Models\CollectionLog::create([
            'collection_id' => $collection->collection_id,
            'client_id' => $oldClientId,
            'action' => 'DELETED',
            'field_name' => null,
            'old_value' => json_encode([
                'client_id' => $oldClientId,
                'collection_datetime' => $collection->collection_datetime?->format('Y-m-d H:i:s'),
                'collection_month' => $oldMonth,
                'collection_type' => $collection->collection_type,
                'collection_amount' => (float) $collection->collection_amount,
                'remarks' => $collection->remarks,
            ]),
            'new_value' => null,
            'performed_by' => $user->email,
        ]);

        $collection->delete();

        if ($oldClientId && $oldMonth) {
            Collection::recalculateMonthlySummary($oldClientId, $oldMonth);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection entry deleted successfully.',
            ]);
        }

        return redirect()->back()->with('status', 'Collection entry deleted successfully.');
    }

    private function ensureNullableClientId(): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE collection MODIFY client_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or no permission
        }
    }
}
