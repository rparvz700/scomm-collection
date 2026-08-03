@extends('layouts.app')

@section('title', 'System Settings | SCOMM Collection')

@section('content')
    <section class="page-heading" style="margin-bottom: 24px;">
        <div>
            <h1>System Settings</h1>
            <p>Manage users, assign roles and permissions, change passwords, and configure system operations.</p>
        </div>
    </section>

    {{-- SUCCESS & ERROR ALERTS --}}
    @if (session('success'))
        <div class="alert success" style="margin-bottom: 20px; padding: 12px 16px; background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert danger" style="margin-bottom: 20px; padding: 12px 16px; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert danger" style="margin-bottom: 20px; padding: 12px 16px; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; font-weight: 500; font-size: 14px; display: flex; flex-direction: column; gap: 4px;">
            <div style="font-weight: 700;">Please correct the following errors:</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- METRIC SUMMARY CARDS --}}
    <section class="grid four" style="margin-bottom: 24px;">
        <article class="metric">
            <span>Total Users</span>
            <strong>{{ number_format($userCount) }}</strong>
        </article>
        <article class="metric">
            <span>Active Roles</span>
            <strong>{{ number_format($roles->count()) }}</strong>
        </article>
        <article class="metric">
            <span>Permissions Defined</span>
            <strong>{{ number_format($permissionCount) }}</strong>
        </article>
        <article class="metric">
            <span>System Month</span>
            <strong style="font-size: 20px; color: var(--primary);">{{ $currentSystemMonthLabel }}</strong>
        </article>
    </section>

    {{-- SETTINGS TAB LAYOUT --}}
    <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden;">
        <div class="tabs-header" style="display: flex; border-bottom: 1px solid var(--line); background: rgba(0, 0, 0, 0.01); padding: 0 16px;">
            <button class="tab-btn active" data-tab="users" style="padding: 16px 20px; font-size: 14px; font-weight: 800; border: none; background: none; border-bottom: 3px solid var(--primary); color: var(--primary); cursor: pointer; transition: all 0.2s;">
                👥 User Management
            </button>
            <button class="tab-btn" data-tab="roles" style="padding: 16px 20px; font-size: 14px; font-weight: 800; border: none; background: none; border-bottom: 3px solid transparent; color: var(--muted); cursor: pointer; transition: all 0.2s;">
                🛡️ Role Permissions Matrix
            </button>
            <button class="tab-btn" data-tab="permissions" style="padding: 16px 20px; font-size: 14px; font-weight: 800; border: none; background: none; border-bottom: 3px solid transparent; color: var(--muted); cursor: pointer; transition: all 0.2s;">
                🔑 Permission Registry
            </button>
            <button class="tab-btn" data-tab="system" style="padding: 16px 20px; font-size: 14px; font-weight: 800; border: none; background: none; border-bottom: 3px solid transparent; color: var(--muted); cursor: pointer; transition: all 0.2s;">
                ⚙️ System Operations
            </button>
        </div>

        <div class="tabs-content" style="padding: 24px;">
            
            {{-- TAB 1: USER MANAGEMENT --}}
            <div id="tab-users" class="tab-pane active" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 14px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px; width: 320px;">
                        <input type="text" id="userSearch" placeholder="Search users by name or email..." style="width: 100%; padding: 8px 14px; border: 1px solid var(--line); border-radius: 6px; font-size: 13.5px; outline: none; background: var(--panel); color: var(--ink);">
                    </div>
                    <button class="button primary" onclick="openCreateUserModal()" title="Create New User" style="height: 38px; width: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    </button>
                </div>

                <div class="table-responsive" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--line);">
                                <th style="text-align: left; padding: 12px 16px; color: var(--muted); font-size: 13px;">Name</th>
                                <th style="text-align: left; padding: 12px 16px; color: var(--muted); font-size: 13px;">Email</th>
                                <th style="text-align: left; padding: 12px 16px; color: var(--muted); font-size: 13px;">Roles</th>
                                <th style="text-align: center; padding: 12px 16px; color: var(--muted); font-size: 13px;">Status</th>
                                <th style="text-align: center; padding: 12px 16px; color: var(--muted); font-size: 13px; width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            @foreach ($users as $u)
                                <tr class="user-row" data-name="{{ strtolower($u->name) }}" data-email="{{ strtolower($u->email) }}" style="border-bottom: 1px solid var(--line); transition: background 0.15s;">
                                    <td style="padding: 14px 16px; font-weight: 700; color: var(--ink);">{{ $u->name }}</td>
                                    <td style="padding: 14px 16px; color: var(--muted);">{{ $u->email }}</td>
                                    <td style="padding: 14px 16px;">
                                        @foreach ($u->roles as $role)
                                            <span class="pill" style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; margin-right: 4px;">
                                                {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        @endforeach
                                        @if ($u->roles->isEmpty())
                                            <span style="color: var(--muted); font-style: italic; font-size: 12.5px;">No Role</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        @if ($u->is_active)
                                            <span class="badge active" style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800; display: inline-block;">Active</span>
                                        @else
                                            <span class="badge inactive" style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800; display: inline-block;">Inactive</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center;">
                                            <button class="button" onclick="openEditUserModal({{ json_encode($u) }}, {{ json_encode($u->roles->pluck('name')) }})" title="Edit User" style="padding: 0; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; min-height: unset; font-size: 14px; background: #0284c7; border: 1px solid #0284c7; color: #ffffff;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path></svg>
                                            </button>
                                            <button class="button" onclick="openResetPasswordModal({{ json_encode($u) }})" title="Reset Password" style="padding: 0; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; min-height: unset; font-size: 14px; background: #4f46e5; border: 1px solid #4f46e5; color: #ffffff;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                                            </button>
                                            @if ($u->id !== Auth::id())
                                                <form action="{{ route('settings.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')" style="margin: 0; display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="button primary" title="Delete User" style="padding: 0; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; min-height: unset; font-size: 14px; border-color: var(--danger); background: var(--danger); color: #ffffff;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: ROLE PERMISSIONS MATRIX --}}
            <div id="tab-roles" class="tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 14px; flex-wrap: wrap;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 16px; color: var(--ink);">Role-Permission Mapping</h3>
                        <p style="margin: 0; font-size: 13px; color: var(--muted);">Directly configure active system permissions assigned to each security role.</p>
                    </div>
                    <button class="button primary" onclick="openCreateRoleModal()" title="Create New Role" style="height: 38px; width: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    </button>
                </div>

                <div class="table-responsive" style="overflow-x: auto; max-height: 600px; border: 1px solid var(--line); border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 900px; position: relative;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--line); background: #f8fafc;">
                                <th style="text-align: left; padding: 14px 16px; color: var(--ink); font-size: 13px; font-weight: 800; position: sticky; left: 0; background: #f8fafc; z-index: 12; border-right: 1px solid var(--line); width: 260px;">Permission</th>
                                @foreach ($roles as $role)
                                    <th style="text-align: center; padding: 14px 10px; color: var(--ink); font-size: 13px; font-weight: 800; min-width: 110px; border-right: 1px solid var(--line);">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                            <span>{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                                            
                                            {{-- Landing Page Dropdown --}}
                                            <select class="landing-page-select" 
                                                    data-role-id="{{ $role->id }}" 
                                                    title="Select Default Landing Page"
                                                    style="padding: 2px 4px; font-size: 10.5px; border-radius: 4px; border: 1px solid var(--line); outline: none; background: #ffffff; color: var(--ink); font-weight: 700; cursor: pointer; width: 100%; max-width: 100px;">
                                                <option value="" {{ empty($role->landing_page) ? 'selected' : '' }}>None</option>
                                                <option value="dashboard.optimized" {{ $role->landing_page === 'dashboard.optimized' ? 'selected' : '' }}>Dashboard</option>
                                                <option value="collection-entry.index" {{ $role->landing_page === 'collection-entry.index' ? 'selected' : '' }}>Entry</option>
                                                <option value="collections.index" {{ $role->landing_page === 'collections.index' ? 'selected' : '' }}>List</option>
                                                <option value="monthly-summary.index" {{ $role->landing_page === 'monthly-summary.index' ? 'selected' : '' }}>Summary (Active)</option>
                                                <option value="monthly-summary-discontinued.index" {{ $role->landing_page === 'monthly-summary-discontinued.index' ? 'selected' : '' }}>Summary (Disc.)</option>
                                                <option value="clients.index" {{ $role->landing_page === 'clients.index' ? 'selected' : '' }}>Clients</option>
                                                <option value="settings.index" {{ $role->landing_page === 'settings.index' ? 'selected' : '' }}>Settings</option>
                                            </select>

                                            @if ($role->name !== 'admin')
                                                <form action="{{ route('settings.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?')" style="margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete Role" style="background: none; border: none; color: var(--danger); font-size: 14px; cursor: pointer; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </button>
                                                </form>
                                            @else
                                                <span style="font-size: 11px; color: var(--muted); font-style: italic;">system admin</span>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $p)
                                <tr style="border-bottom: 1px solid var(--line); transition: background 0.1s;">
                                    <td style="padding: 12px 16px; font-weight: 700; font-size: 13px; color: var(--ink); position: sticky; left: 0; background: #ffffff; z-index: 10; border-right: 1px solid var(--line);">
                                        <code>{{ $p->name }}</code>
                                    </td>
                                    @foreach ($roles as $role)
                                        <td style="text-align: center; padding: 12px 10px; border-right: 1px solid var(--line); background: {{ $role->name === 'admin' ? '#fafafa' : '#ffffff' }}">
                                            @if ($role->name === 'admin')
                                                <input type="checkbox" checked disabled style="width: 16px; height: 16px; accent-color: var(--primary);">
                                            @else
                                                <input type="checkbox" 
                                                       class="matrix-checkbox" 
                                                       data-role-id="{{ $role->id }}" 
                                                       data-role-name="{{ $role->name }}" 
                                                       data-permission-name="{{ $p->name }}"
                                                       {{ $role->hasPermissionTo($p->name) ? 'checked' : '' }}
                                                       style="width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer;">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 14px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--line); font-size: 12.5px; color: var(--muted); display: flex; align-items: center; gap: 8px;">
                    <span>💡</span> Toggling any checkbox in the matrix above instantly saves the permission mapping in the database.
                </div>
            </div>

            {{-- TAB 3: PERMISSION REGISTRY --}}
            <div id="tab-permissions" class="tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 14px; flex-wrap: wrap;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 16px; color: var(--ink);">Available System Permissions</h3>
                        <p style="margin: 0; font-size: 13px; color: var(--muted);">Browse all active permissions configured in the application guard registry.</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; width: 260px;">
                        <input type="text" id="permissionSearch" placeholder="Search permissions..." style="width: 100%; padding: 8px 14px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none; background: var(--panel); color: var(--ink);">
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x: auto; max-height: 500px; border: 1px solid var(--line); border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--line); background: #f8fafc;">
                                <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800;">Permission Key</th>
                                <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800;">Guard</th>
                                <th style="text-align: left; padding: 12px 16px; color: var(--ink); font-size: 13px; font-weight: 800;">Modules impacted</th>
                            </tr>
                        </thead>
                        <tbody id="permissionTableBody">
                            @foreach ($permissions as $p)
                                <tr class="permission-row" data-name="{{ strtolower($p->name) }}" style="border-bottom: 1px solid var(--line);">
                                    <td style="padding: 12px 16px; font-weight: 700; color: var(--ink);">
                                        <code>{{ $p->name }}</code>
                                    </td>
                                    <td style="padding: 12px 16px;"><span class="pill" style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 11px;">{{ $p->guard_name }}</span></td>
                                    <td style="padding: 12px 16px; color: var(--muted); font-size: 13px;">
                                        @if (str_contains($p->name, 'client'))
                                            Clients Segment &amp; Drilldowns
                                        @elseif (str_contains($p->name, 'summary'))
                                            Monthly Summaries &amp; Collections
                                        @elseif (str_contains($p->name, 'collection'))
                                            Collection Entry Modality
                                        @elseif (str_contains($p->name, 'risk'))
                                            Risk Analytics
                                        @elseif (str_contains($p->name, 'report'))
                                            Reporting Panel
                                        @elseif (str_contains($p->name, 'log'))
                                            Audit, System, &amp; Guidance Logs
                                        @else
                                            System &amp; Settings Admin
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 4: SYSTEM OPERATIONS --}}
            <div id="tab-system" class="tab-pane" style="display: none;">
                <div style="max-width: 700px; margin: 0 auto; padding: 10px 0;">
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                        <h3 style="margin: 0 0 12px 0; font-size: 16px; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                            <span>🗓️</span> Month Rollover Operation
                        </h3>
                        <p style="margin: 0 0 20px 0; font-size: 13.5px; color: var(--muted); line-height: 1.5;">
                            Month Rollover carries over outstanding closing balances to the opening outstanding balances for all active and discontinued clients in the new month. It initializes metrics (opening CR, ratings) so collection logging can commence.
                        </p>
                        
                        <div style="display: flex; gap: 18px; align-items: center; background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid var(--line); margin-bottom: 24px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 140px;">
                                <span style="font-size: 12px; color: var(--muted); display: block; font-weight: bold; text-transform: uppercase;">Current System Month</span>
                                <strong style="font-size: 20px; color: var(--ink);">{{ $currentSystemMonthLabel }}</strong>
                            </div>
                            <div style="flex: 1; min-width: 140px;">
                                <span style="font-size: 12px; color: var(--muted); display: block; font-weight: bold; text-transform: uppercase;">Target Month</span>
                                <strong style="font-size: 20px; color: var(--primary);">
                                    {{ $currentSystemMonth ? \Carbon\Carbon::parse($currentSystemMonth)->addMonth()->format('F Y') : \Carbon\Carbon::now()->format('F Y') }}
                                </strong>
                            </div>
                        </div>

                        <form action="{{ route('settings.system.rollover') }}" method="POST" onsubmit="return confirm('Are you sure you want to trigger the Month Rollover? This operation will generate summaries for the target month.');" style="margin: 0;">
                            @csrf
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="submit" class="button primary" title="Trigger Month Rollover" style="width: 42px; height: 42px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 3.5-2 3.5s2.24-.5 3.5-2c1.39-1.65 4.54-5.36 6-7.07l-3-3c-1.71 1.46-5.42 4.61-7.07 6z"></path><path d="M12 9.5l3.5 3.5c1.52-1.52 4.35-4.8 5.5-6.5C22 5 21 3 21 3s-2-1-3.5-.5c-1.7 1.15-4.98 3.98-6.5 5.5z"></path></svg>
                                </button>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; color: var(--muted); cursor: pointer; user-select: none;">
                                    <input type="checkbox" name="force" value="1" style="width: 16px; height: 16px;">
                                    Force overwrite existing target records
                                </label>
                            </div>
                        </form>
                    </div>

                    <div style="border: 1px dashed var(--line); border-radius: 12px; padding: 20px; background: rgba(0,0,0,0.01);">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: var(--ink);">Rollover Actions Executed</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--muted); line-height: 1.6; display: flex; flex-direction: column; gap: 4px;">
                            <li>Reads active clients list and transfers latest outstanding balances.</li>
                            <li>Identifies discontinued clients and transfers their respective balances.</li>
                            <li>Sets opening CR as: $\text{opening\_os} \div \text{billed\_mrc}$.</li>
                            <li>Applies collection rating category badge (A, B, C, D, E, F) depending on the calculated CR value.</li>
                            <li>Generates baseline records in `monthly_summaries` and `monthly_summaries_discontinued`.</li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    {{-- MODAL OVERLAYS --}}
    
    {{-- 1. CREATE USER MODAL --}}
    <div id="createUserModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 500px; box-shadow: var(--shadow); overflow: hidden; animation: slideDown 0.25s ease-out;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.01);">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--ink);">Create New User</h3>
                <button onclick="closeModal('createUserModal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
            </div>
            <form action="{{ route('settings.users.store') }}" method="POST" style="margin: 0;">
                @csrf
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Full Name *</label>
                        <input type="text" name="name" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Email Address *</label>
                        <input type="email" name="email" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Initial Password *</label>
                        <input type="password" name="password" required minlength="8" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Security Role</label>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px; padding: 10px; background: #fafafa;">
                            @foreach ($roles as $role)
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--ink); cursor: pointer;">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" style="width: 16px; height: 16px; accent-color: var(--primary);">
                                    <span>{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Status</label>
                        <select name="is_active" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px; font-weight: 700; cursor: pointer;">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="padding: 14px 20px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.01); display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="button" onclick="closeModal('createUserModal')" title="Cancel" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: #64748b; border: 1px solid #64748b; color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                    <button type="submit" class="button primary" title="Create User" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. EDIT USER MODAL --}}
    <div id="editUserModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 500px; box-shadow: var(--shadow); overflow: hidden; animation: slideDown 0.25s ease-out;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.01);">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--ink);">Edit User</h3>
                <button onclick="closeModal('editUserModal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
            </div>
            <form id="editUserForm" method="POST" style="margin: 0;">
                @csrf
                @method('PUT')
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Full Name *</label>
                        <input type="text" name="name" id="edit-name" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Email Address *</label>
                        <input type="email" name="email" id="edit-email" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Security Role</label>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px; padding: 10px; background: #fafafa;">
                            @foreach ($roles as $role)
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--ink); cursor: pointer;">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="edit-role-checkbox" data-role-name="{{ $role->name }}" style="width: 16px; height: 16px; accent-color: var(--primary);">
                                    <span>{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Status</label>
                        <select name="is_active" id="edit-active" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px; font-weight: 700; cursor: pointer;">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="padding: 14px 20px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.01); display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="button" onclick="closeModal('editUserModal')" title="Cancel" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: #64748b; border: 1px solid #64748b; color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                    <button type="submit" class="button primary" title="Save Changes" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. RESET PASSWORD MODAL --}}
    <div id="resetPasswordModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 500px; box-shadow: var(--shadow); overflow: hidden; animation: slideDown 0.25s ease-out;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.01);">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--ink);">Reset Password</h3>
                <button onclick="closeModal('resetPasswordModal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
            </div>
            <form id="resetPasswordForm" method="POST" style="margin: 0;">
                @csrf
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div style="padding: 12px; background: #f8fafc; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; color: var(--muted);">
                        Resetting password for: <strong id="reset-user-email" style="color: var(--ink);"></strong>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">New Password *</label>
                        <input type="password" name="password" required minlength="8" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Confirm New Password *</label>
                        <input type="password" name="password_confirmation" required minlength="8" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                </div>
                <div style="padding: 14px 20px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.01); display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="button" onclick="closeModal('resetPasswordModal')" title="Cancel" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: #64748b; border: 1px solid #64748b; color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                    <button type="submit" class="button primary" title="Reset Password" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. CREATE ROLE MODAL --}}
    <div id="createRoleModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; width: 90%; max-width: 440px; box-shadow: var(--shadow); overflow: hidden; animation: slideDown 0.25s ease-out;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.01);">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--ink);">Create New Role</h3>
                <button onclick="closeModal('createRoleModal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
            </div>
            <form action="{{ route('settings.roles.store') }}" method="POST" style="margin: 0;">
                @csrf
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Role Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Risk Manager" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-size: 13.5px;">
                    </div>
                </div>
                <div style="padding: 14px 20px; border-top: 1px solid var(--line); background: rgba(0, 0, 0, 0.01); display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="button" onclick="closeModal('createRoleModal')" title="Cancel" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: #64748b; border: 1px solid #64748b; color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                    <button type="submit" class="button primary" title="Create Role" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border-color: var(--primary); background: var(--primary); color: #ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- SCRIPTS FOR INTERACTION --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // TABS TOGGLE FUNCTIONALITY
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.dataset.tab;

                    // Update active button state
                    tabButtons.forEach(b => {
                        b.classList.remove('active');
                        b.style.borderBottomColor = 'transparent';
                        b.style.color = 'var(--muted)';
                    });
                    btn.classList.add('active');
                    btn.style.borderBottomColor = 'var(--primary)';
                    btn.style.color = 'var(--primary)';

                    // Update active content panel
                    tabPanes.forEach(pane => {
                        pane.style.display = 'none';
                    });
                    document.getElementById('tab-' + tabId).style.display = 'block';
                });
            });

            // USER SEARCH
            const userSearch = document.getElementById('userSearch');
            const userRows = document.querySelectorAll('.user-row');

            userSearch.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();

                userRows.forEach(row => {
                    const name = row.dataset.name;
                    const email = row.dataset.email;

                    if (name.includes(query) || email.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // PERMISSION SEARCH
            const permissionSearch = document.getElementById('permissionSearch');
            const permissionRows = document.querySelectorAll('.permission-row');

            permissionSearch.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();

                permissionRows.forEach(row => {
                    const name = row.dataset.name;

                    if (name.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // AJAX ROLE-PERMISSION MATRIX CHECKBOX TOGGLE
            const matrixCheckboxes = document.querySelectorAll('.matrix-checkbox');
            matrixCheckboxes.forEach(chk => {
                chk.addEventListener('change', function() {
                    const roleId = this.dataset.roleId;
                    const roleName = this.dataset.roleName;
                    const permName = this.dataset.permissionName;
                    const isChecked = this.checked;

                    // Disable matrix checks during save to prevent race conditions
                    this.disabled = true;

                    // Get all checked permissions for this role to sync
                    const checkedPermissions = [];
                    document.querySelectorAll(`.matrix-checkbox[data-role-id="${roleId}"]`).forEach(sibling => {
                        if (sibling.checked) {
                            checkedPermissions.push(sibling.dataset.permissionName);
                        }
                    });

                    // Send AJAX request
                    fetch(`{{ url('settings/roles') }}/${roleId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            _method: 'PUT',
                            name: roleName,
                            permissions: checkedPermissions
                        })
                    })
                    .then(response => {
                        this.disabled = false;
                        if (response.ok) {
                            showToast(`Permission mapping updated successfully for role "${roleName}".`);
                        } else {
                            this.checked = !isChecked; // Revert checkbox if error
                            showToast('Failed to update permission mapping. Try again.', true);
                        }
                    })
                    .catch(err => {
                        this.disabled = false;
                        this.checked = !isChecked; // Revert checkbox if error
                        showToast('Failed to update permission mapping. Network error.', true);
                    });
                });
            });

            // AJAX LANDING PAGE SELECT
            const landingSelects = document.querySelectorAll('.landing-page-select');
            landingSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const roleId = this.dataset.roleId;
                    const val = this.value;

                    this.disabled = true;

                    fetch(`{{ url('settings/roles') }}/${roleId}/landing-page`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            _method: 'PUT',
                            landing_page: val
                        })
                    })
                    .then(response => {
                        this.disabled = false;
                        if (response.ok) {
                            showToast('Default landing page updated successfully.');
                        } else {
                            showToast('Failed to update landing page.', true);
                        }
                    })
                    .catch(err => {
                        this.disabled = false;
                        showToast('Failed to update landing page. Network error.', true);
                    });
                });
            });
        });

        // TOAST NOTIFICATIONS HELPER
        function showToast(message, isError = false) {
            let toast = document.getElementById('settings-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'settings-toast';
                toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 10000; padding: 12px 24px; border-radius: 8px; font-size: 13.5px; font-weight: bold; color: white; display: flex; align-items: center; gap: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: opacity 0.3s;';
                document.body.appendChild(toast);
            }

            toast.style.backgroundColor = isError ? '#dc2626' : '#0f766e';
            toast.innerHTML = (isError ? '⚠️ ' : '✅ ') + message;
            toast.style.opacity = '1';
            toast.style.display = 'block';

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 300);
            }, 3500);
        }

        // MODAL HELPERS
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'flex';
        }

        function openCreateRoleModal() {
            document.getElementById('createRoleModal').style.display = 'flex';
        }

        function openEditUserModal(user, assignedRoles) {
            const form = document.getElementById('editUserForm');
            form.action = `{{ url('settings/users') }}/${user.id}`;

            document.getElementById('edit-name').value = user.name || '';
            document.getElementById('edit-email').value = user.email || '';
            document.getElementById('edit-active').value = user.is_active ? '1' : '0';

            // Clear checkmarks first
            const checkmarks = document.querySelectorAll('.edit-role-checkbox');
            checkmarks.forEach(chk => {
                chk.checked = assignedRoles.includes(chk.dataset.roleName);
            });

            document.getElementById('editUserModal').style.display = 'flex';
        }

        function openResetPasswordModal(user) {
            const form = document.getElementById('resetPasswordForm');
            form.action = `{{ url('settings/users') }}/${user.id}/reset-password`;

            document.getElementById('reset-user-email').innerText = user.email || '';
            document.getElementById('resetPasswordModal').style.display = 'flex';
        }
    </script>

    {{-- KEYFRAME ANIMATION FOR MODALS --}}
    <style>
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
@endsection
