@extends('layouts.app')

@section('title', 'System Access Logs | SCOMM Collection')

@section('content')
    <div class="page-heading">
        <div>
            <h1>System Access Logs</h1>
            <p>Authentication audit log tracking logins, logouts, and failed access attempts.</p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
            <h2>Authentication Activity History</h2>
            <form method="GET" action="{{ route('logs.system-access') }}" style="display: flex; gap: 8px; margin: 0;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search email, event, status, IP..." style="height: 36px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; min-width: 240px;">
                <button type="submit" class="button primary" style="min-height: 36px; padding: 0 14px; font-weight: 700;">Search</button>
                @if($search)
                    <a href="{{ route('logs.system-access') }}" class="button" style="min-height: 36px; padding: 0 12px; text-decoration: none;">Clear</a>
                @endif
            </form>
        </div>
        <div class="panel-body" style="padding: 0; overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User / Email</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="white-space: nowrap; font-weight: 600; font-size: 12.5px; color: var(--muted);">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td>
                                <strong>{{ $log->user->name ?? $log->email }}</strong>
                                @if($log->user?->email)
                                    <div style="font-size: 11px; color: var(--muted);">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="pill" style="{{ $log->event === 'login' ? 'background: #dcfce7; color: #166534;' : ($log->event === 'logout' ? 'background: #f1f5f9; color: #475569;' : 'background: #fee2e2; color: #991b1b;') }}">
                                    {{ strtoupper(str_replace('_', ' ', $log->event)) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 800; font-size: 12.5px; color: {{ $log->status === 'success' ? '#166534' : '#dc2626' }};">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td style="font-size: 12.5px; font-weight: 600; color: var(--ink);">{{ $log->ip_address ?? '—' }}</td>
                            <td style="font-size: 11.5px; color: var(--muted); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--muted); padding: 32px;">No system access logs recorded yet.</td>
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
