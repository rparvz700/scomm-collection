@extends('layouts.app')

@section('title', 'Client Logs | SCOMM Collection')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Client Logs</h1>
            <p>Audit trail of all client master record profile and attribute changes.</p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
            <h2>Activity History</h2>
            <form method="GET" action="{{ route('logs.client') }}" style="display: flex; gap: 8px; margin: 0;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search client, field, user..." style="height: 36px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; min-width: 240px;">
                <button type="submit" class="button primary" style="min-height: 36px; padding: 0 14px; font-weight: 700;">Search</button>
                @if($search)
                    <a href="{{ route('logs.client') }}" class="button" style="min-height: 36px; padding: 0 12px; text-decoration: none;">Clear</a>
                @endif
            </form>
        </div>
        <div class="panel-body" style="padding: 0; overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Client</th>
                        <th>Field Name</th>
                        <th>Previous Value</th>
                        <th>New Value</th>
                        <th>Updated By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="white-space: nowrap; font-weight: 600; font-size: 12.5px; color: var(--muted);">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td>
                                <strong>{{ $log->client->client_name ?? 'N/A' }}</strong>
                                @if($log->client?->opus_id)
                                    <div style="font-size: 11px; color: var(--muted);">OPUS: {{ $log->client->opus_id }}</div>
                                @endif
                            </td>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0f172a;">{{ $log->field_name }}</code></td>
                            <td style="color: #64748b; font-size: 13px;">{{ $log->old_value ?? '—' }}</td>
                            <td style="color: #0f766e; font-weight: 700; font-size: 13px;">{{ $log->new_value ?? '—' }}</td>
                            <td style="font-size: 13px; font-weight: 600;">{{ $log->updated_by ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--muted); padding: 32px;">No client logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--line);">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
