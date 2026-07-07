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
        $latestSummary = MonthlySummary::query()->max('summary_month');
        $defaultMonthDate = $latestSummary ? Carbon::parse($latestSummary) : null;
        $defaultMonthString = $defaultMonthDate ? $defaultMonthDate->format('Y-m') : '';
        $defaultMonthFullDate = $defaultMonthDate ? $defaultMonthDate->format('Y-m-d') : '';

        return view('collection-entry.index', [
            'clients' => Client::query()->orderBy('client_name')->get(),
            'collections' => Collection::query()
                ->with('client')
                ->latest('collection_datetime')
                ->take(15)
                ->get(),
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
        if ($request->has('batch_data')) {
            $batch = json_decode($request->input('batch_data'), true);
            if (is_array($batch) && !empty($batch)) {
                \DB::transaction(function() use ($batch, $request) {
                    foreach ($batch as $entry) {
                        $validated = validator($entry, [
                            'client_id' => ['required', 'exists:client,client_id'],
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

        $data = $request->validate([
            'client_id' => ['required', 'exists:client,client_id'],
            'collection_datetime' => ['required', 'date'],
            'collection_month' => ['required', 'date'],
            'collection_type' => ['required', 'string'],
            'collection_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

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

        if (!$clientId || !$monthString) {
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
            ->latest('collection_datetime')
            ->take(15);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $collections = $query->get()->map(function ($collection) {
            return [
                'client_name' => $collection->client->client_name ?? 'Unknown client',
                'collection_type' => str_replace('_', ' ', $collection->collection_type),
                'collection_amount' => number_format((float) $collection->collection_amount, 2),
                'collection_date' => $collection->collection_datetime ? $collection->collection_datetime->format('d M Y') : 'N/A',
            ];
        });

        return response()->json($collections);
    }
}
