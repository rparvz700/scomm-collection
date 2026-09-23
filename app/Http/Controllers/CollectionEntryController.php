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

class CollectionEntryController extends Controller
{
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

        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('collection_supervisor') && !$user->hasRole('collection_hod')) {
                $collectionsQuery->where('created_by', $user->email);
            }
        }

        $collections = $collectionsQuery->take(15)->get();

        return view('collection-entry.index', [
            'clients' => Client::query()->orderBy('client_name')->get(),
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
            'defaultMonthString' => $defaultMonthString,
            'defaultMonthFullDate' => $defaultMonthFullDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureNullableClientId();

        if ($request->has('batch_data')) {
            $batch = json_decode($request->input('batch_data'), true);
            if (is_array($batch) && !empty($batch)) {
                \DB::transaction(function() use ($batch, $request) {
                    foreach ($batch as $entry) {
                        $rawClientId = $entry['client_id'] ?? null;
                        $cId = ($rawClientId === 'untraced' || empty($rawClientId)) ? null : $rawClientId;
                        $entryData = array_merge($entry, ['client_id' => $cId]);

                        $validated = validator($entryData, [
                            'client_id' => ['nullable', 'exists:client,client_id'],
                            'collection_datetime' => ['required', 'date'],
                            'collection_month' => ['required', 'date'],
                            'collection_type' => ['required', 'string'],
                            'collection_amount' => ['required', 'numeric', 'min:0'],
                            'remarks' => ['nullable', 'string'],
                        ])->validate();

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

        $data = validator($requestData, [
            'client_id' => ['nullable', 'exists:client,client_id'],
            'collection_datetime' => ['required', 'date'],
            'collection_month' => ['required', 'date'],
            'collection_type' => ['required', 'string'],
            'collection_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ])->validate();

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

        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('collection_supervisor') && !$user->hasRole('collection_hod')) {
                $query->where('created_by', $user->email);
            }
        }

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
            ];
        });

        return response()->json($collections);
    }

    public function collectionsIndex(Request $request): View
    {
        // Check if there are any filter parameters in request
        $hasFilters = $request->has('client_id') || 
                      $request->has('collection_type') || 
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

        // Apply filters
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

        // Get unique clients and types for filter dropdowns
        $clients = Client::query()->orderBy('client_name')->get();
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
            'selectedClientId' => $request->client_id,
            'selectedType' => $request->collection_type,
            'selectedMonth' => $selectedMonth,
            'selectedDateFrom' => $request->date_from,
            'selectedDateTo' => $request->date_to,
            'searchText' => $request->search,
            'perPage' => $perPage,
        ]);
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
