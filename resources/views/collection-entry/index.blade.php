@extends('layouts.app')

@section('title', 'Collection Entry | SCOMM Collection')

@section('content')
    <section class="page-heading">
        <div>
            <h1>Collection entry</h1>
            <p>Post received customer collections and review the latest transaction entries.</p>
        </div>
    </section>

    <section class="grid two">
        <article class="panel">
            <div class="panel-header">
                <h2>New collection</h2>
                <span>Manual entry</span>
            </div>
            <form class="panel-body" method="POST" action="{{ route('collection-entry.store') }}">
                @csrf

                <div class="field">
                    <label for="client_id">Client</label>
                    <select id="client_id" name="client_id" required>
                        <option value="">Select client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->client_id }}" @selected(old('client_id') == $client->client_id)>
                                {{ $client->client_name }} - {{ $client->opus_id }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_datetime">Collection datetime</label>
                    <input id="collection_datetime" name="collection_datetime" type="datetime-local" value="{{ old('collection_datetime') }}" required>
                    @error('collection_datetime')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_month">Collection month</label>
                    <input id="collection_month" name="collection_month" type="date" value="{{ old('collection_month') }}" required>
                    @error('collection_month')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_type">Collection type</label>
                    <select id="collection_type" name="collection_type" required>
                        <option value="">Select type</option>
                        @foreach ($collectionTypes as $type)
                            <option value="{{ $type }}" @selected(old('collection_type') === $type)>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('collection_type')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="collection_amount">Amount</label>
                    <input id="collection_amount" name="collection_amount" type="number" min="0" step="0.01" value="{{ old('collection_amount') }}" required>
                    @error('collection_amount')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks">{{ old('remarks') }}</textarea>
                    @error('remarks')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button class="button primary" type="submit">Save collection</button>
            </form>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>Recent entries</h2>
                <span>Latest 15</span>
            </div>

            @if ($collections->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($collections as $collection)
                            <tr>
                                <td>{{ $collection->client->client_name ?? 'Unknown client' }}</td>
                                <td><span class="pill">{{ str_replace('_', ' ', $collection->collection_type) }}</span></td>
                                <td class="amount">{{ number_format((float) $collection->collection_amount, 2) }}</td>
                                <td>{{ optional($collection->collection_datetime)->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">No collection entries found.</div>
            @endif
        </article>
    </section>
@endsection
