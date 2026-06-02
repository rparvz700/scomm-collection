<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionEntryController extends Controller
{
    public function index(): View
    {
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
}
