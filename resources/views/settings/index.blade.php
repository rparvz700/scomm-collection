@extends('layouts.app')

@section('title', 'Settings | SCOMM Collection')

@section('content')
    <section class="page-heading">
        <div>
            <h1>Settings</h1>
            <p>Manage the access structure and review the role-permission setup for the collection system.</p>
        </div>
    </section>

    <section class="grid four">
        <article class="metric">
            <span>Users</span>
            <strong>{{ number_format($userCount) }}</strong>
        </article>
        <article class="metric">
            <span>Roles</span>
            <strong>{{ number_format($roles->count()) }}</strong>
        </article>
        <article class="metric">
            <span>Permissions</span>
            <strong>{{ number_format($permissionCount) }}</strong>
        </article>
        <article class="metric">
            <span>Guard</span>
            <strong>web</strong>
        </article>
    </section>

    <section class="panel" style="margin-top: 18px;">
        <div class="panel-header">
            <h2>Roles</h2>
            <span>Spatie permission</span>
        </div>

        @if ($roles->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Guard</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $role->name)) }}</td>
                            <td><span class="pill">{{ $role->guard_name }}</span></td>
                            <td class="amount">{{ number_format($role->permissions_count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No roles found. Run the role permission seeder.</div>
        @endif
    </section>
@endsection
