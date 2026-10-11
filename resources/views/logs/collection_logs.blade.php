@extends('layouts.app')

@section('title', 'Collection Change Log | SCOMM Collection')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Collection Change Log</h1>
            <p>Audit trail of all collection entry updates and deletions.</p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: nowrap; gap: 14px;">
            <h2 style="margin: 0; white-space: nowrap;">Activity History</h2>
            <form method="GET" action="{{ route('logs.collection') }}" style="display: flex; gap: 8px; margin: 0; flex-wrap: nowrap; align-items: center;">
                <select name="action" onchange="this.form.submit()" style="height: 36px; padding: 0 10px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                    <option value="">All Actions</option>
                    <option value="CREATED" {{ $action === 'CREATED' ? 'selected' : '' }}>CREATED</option>
                    <option value="UPDATED" {{ $action === 'UPDATED' ? 'selected' : '' }}>UPDATED</option>
                    <option value="DELETED" {{ $action === 'DELETED' ? 'selected' : '' }}>DELETED</option>
                </select>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search client, field, user..." style="height: 36px; padding: 0 12px; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; min-width: 240px; background: var(--panel); color: var(--ink);">
                <button type="submit" class="button primary" style="min-height: 36px; padding: 0 14px; font-weight: 700; white-space: nowrap;">Search</button>
                @if($search || $action)
                    <a href="{{ route('logs.collection') }}" class="button" style="min-height: 36px; padding: 0 12px; text-decoration: none; display: inline-flex; align-items: center; white-space: nowrap;">Clear</a>
                @endif
            </form>
        </div>
        <div class="panel-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--line); background: #f8fafc;">
                        <th style="padding: 12px 16px; text-align: left;">Date &amp; Time</th>
                        <th style="padding: 12px 16px; text-align: left;">Client Name</th>
                        <th style="padding: 12px 16px; text-align: center;">Collection ID</th>
                        <th style="padding: 12px 16px; text-align: center;">Action</th>
                        <th style="padding: 12px 16px; text-align: left;">Field Changed</th>
                        <th style="padding: 12px 16px; text-align: left;">Old Value</th>
                        <th style="padding: 12px 16px; text-align: left;">New Value</th>
                        <th style="padding: 12px 16px; text-align: left;">Performed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr style="border-bottom: 1px solid var(--line);">
                            <td style="padding: 12px 16px; white-space: nowrap; font-weight: 600; font-size: 12.5px; color: var(--muted);">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--ink);">
                                {{ $log->client->client_name ?? 'Untraced Collection' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 700; font-size: 12px; color: var(--muted);">
                                #{{ $log->collection_id ?? 'N/A' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if($log->action === 'CREATED')
                                    <span class="pill" style="background: #dcfce7; color: #15803d; font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px;">CREATED</span>
                                @elseif($log->action === 'UPDATED')
                                    <span class="pill" style="background: #e0f2fe; color: #0369a1; font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px;">UPDATED</span>
                                @elseif($log->action === 'DELETED')
                                    <span class="pill" style="background: #ffe4e6; color: #be123c; font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px;">DELETED</span>
                                @else
                                    <span class="pill" style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px;">{{ $log->action }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;">
                                @if($log->field_name)
                                    <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0f172a; font-weight: 700;">{{ $log->field_name }}</code>
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">Full Snapshot</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; color: #64748b; font-size: 12.5px; max-width: 250px; word-break: break-word;">
                                @php
                                    $oldDecoded = is_string($log->old_value) && str_starts_with(trim($log->old_value), '{') ? json_decode($log->old_value, true) : null;
                                @endphp
                                @if(is_array($oldDecoded))
                                    <div style="font-size: 11.5px; line-height: 1.4; background: #f8fafc; padding: 6px 8px; border-radius: 6px; border: 1px solid var(--line);">
                                        @foreach($oldDecoded as $k => $v)
                                            <div><strong>{{ ucwords(str_replace('_', ' ', $k)) }}:</strong> {{ is_null($v) ? 'null' : (is_numeric($v) && str_contains($k, 'amount') ? '৳' . number_format($v, 2) : $v) }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $log->old_value ?? '—' }}
                                @endif
                            </td>
                            <td style="padding: 12px 16px; color: #0f766e; font-weight: 600; font-size: 12.5px; max-width: 250px; word-break: break-word;">
                                @php
                                    $newDecoded = is_string($log->new_value) && str_starts_with(trim($log->new_value), '{') ? json_decode($log->new_value, true) : null;
                                @endphp
                                @if(is_array($newDecoded))
                                    <div style="font-size: 11.5px; line-height: 1.4; background: #f0fdf4; padding: 6px 8px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                        @foreach($newDecoded as $k => $v)
                                            <div><strong>{{ ucwords(str_replace('_', ' ', $k)) }}:</strong> {{ is_null($v) ? 'null' : (is_numeric($v) && str_contains($k, 'amount') ? '৳' . number_format($v, 2) : $v) }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $log->new_value ?? '—' }}
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: 12.5px; font-weight: 600; color: var(--ink);">
                                {{ $log->performed_by ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--muted); padding: 32px;">No collection change logs found matching your filter criteria.</td>
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
