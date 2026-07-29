@extends('layouts.app')

@section('title', 'Guidance Logs | SCOMM Collection')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Guidance Logs</h1>
            <p>Historical record of management directives, guidance notes, and escalation actions taken.</p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
            <h2>Management Guidance History</h2>
            <form method="GET" action="{{ route('logs.guidance') }}" style="display: flex; gap: 8px; margin: 0;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search client, guidance, action..." style="height: 36px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; min-width: 240px;">
                <button type="submit" class="button primary" style="min-height: 36px; padding: 0 14px; font-weight: 700;">Search</button>
                @if($search)
                    <a href="{{ route('logs.guidance') }}" class="button" style="min-height: 36px; padding: 0 12px; text-decoration: none;">Clear</a>
                @endif
            </form>
        </div>
        <div class="panel-body" style="padding: 0; overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Client</th>
                        <th>Action Taken</th>
                        <th>Guidance Text</th>
                        <th>Logged By</th>
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
                            <td>
                                <span class="pill" style="background: #eff6ff; color: #1d4ed8;">
                                    {{ $log->action_taken ?? 'General Directive' }}
                                </span>
                            </td>
                            <td style="font-size: 13.5px; line-height: 1.5; color: var(--ink);">{{ $log->guidance_text }}</td>
                            <td style="font-size: 13px; font-weight: 600; white-space: nowrap;">
                                {{ $log->user->name ?? $log->user->email ?? 'Management' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--muted); padding: 32px;">No management guidance logs found.</td>
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
