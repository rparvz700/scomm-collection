@extends('layouts.app')

@section('title', 'Clients | SCOMM Collection')

@push('styles')
    <style>
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-input {
            min-width: 250px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 0 12px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        .summary-card {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            filter: brightness(0.97);
        }

        .summary-card:active {
            transform: translateY(0);
        }

        .client-table-wrap {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: var(--panel);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .client-table {
            width: 100%;
            border-collapse: collapse;
        }

        .client-table th, 
        .client-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #edf1f7;
            text-align: left;
            font-size: 13px;
        }

        .client-table th {
            background: #f8fafc;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .client-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-discontinued {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-other {
            background: #f1f5f9;
            color: #475569;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-link {
            font-weight: 700;
            text-decoration: none;
            color: var(--primary);
            font-size: 13px;
        }

        .action-link:hover {
            color: var(--accent);
        }

        .action-btn-danger {
            background: none;
            border: none;
            color: var(--danger);
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            font-size: 13px;
            font-family: inherit;
        }

        .action-btn-danger:hover {
            opacity: 0.8;
        }

        /* Pagination overrides */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 20px 0 0;
        }

        .pagination li a, 
        .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border: 1px solid var(--line);
            border-radius: 6px;
            text-decoration: none;
            color: var(--ink);
            font-weight: 700;
            background: #ffffff;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .pagination li a:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f8fafc;
        }

        .pagination li.active span {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .pagination li.disabled span {
            color: var(--muted);
            background: #f1f5f9;
            cursor: not-allowed;
        }

        /* Discontinue Modal custom style */
        .discontinue-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,.55);
            align-items: center;
            justify-content: center;
        }

        .discontinue-modal-content {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 440px;
            box-shadow: var(--shadow);
            position: relative;
        }

        .discontinue-modal h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 18px;
            color: var(--danger);
        }

        /* Sortable table headers */
        .client-table th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 28px;
            transition: background-color 0.2s ease;
        }

        .client-table th.sortable:hover {
            background: #f1f5f9;
            color: var(--ink);
        }

        .client-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.35;
            font-size: 10px;
        }

        .client-table th.sortable.sort-asc::after {
            content: '▲';
            opacity: 1;
            color: var(--primary);
        }

        .client-table th.sortable.sort-desc::after {
            content: '▼';
            opacity: 1;
            color: var(--primary);
        }
    </style>
@endpush

@section('content')
    <section class="page-heading">
        <div style="width: 100%;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <h1 style="margin: 0;">Clients</h1>

                    @if($isTeamScoped)
                        <!-- Scope Toggle Pill Button Group -->
                        <div class="scope-toggle-group" style="display: inline-flex; background: #e2e8f0; border-radius: 999px; padding: 3px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);">
                            <a href="{{ route('clients.index', ['view_scope' => 'my']) }}" 
                               class="toggle-btn {{ ($viewScope ?? 'team') === 'my' ? 'active' : '' }}" 
                               style="padding: 6px 16px; border-radius: 999px; font-size: 12px; font-weight: 800; text-decoration: none; transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; {{ ($viewScope ?? 'team') === 'my' ? 'background: var(--primary); color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.15);' : 'color: var(--muted); background: transparent;' }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                My Clients
                            </a>
                            <a href="{{ route('clients.index', ['view_scope' => 'team']) }}" 
                               class="toggle-btn {{ ($viewScope ?? 'team') === 'team' ? 'active' : '' }}" 
                               style="padding: 6px 16px; border-radius: 999px; font-size: 12px; font-weight: 800; text-decoration: none; transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; {{ ($viewScope ?? 'team') === 'team' ? 'background: var(--primary); color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.15);' : 'color: var(--muted); background: transparent;' }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                My Team's Clients
                            </a>
                        </div>
                    @endif
                </div>

                @can('create clients')
                    <a href="{{ route('clients.create') }}" class="button primary">Add client</a>
                @endcan
            </div>

            @if($isTeamScoped)
                @if(($viewScope ?? 'team') === 'my')
                    <p style="margin-top: 8px;">Viewing customer master profiles, assignments, and service operational status assigned directly to you (My Clients).</p>
                @else
                    <p style="margin-top: 8px;">Viewing customer master profiles, assignments, and service operational status for your team (My Team's Clients).</p>
                @endif
            @else
                <p style="margin-top: 8px;">Manage customer master profiles, assignments, and service operational status across all company clients.</p>
            @endif
        </div>
    </section>

    @if($isTeamScoped)
        <!-- Company-Wide Summary Cards Section -->
        <div style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <h3 style="font-size: 13px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #3b82f6;"></span>
                    Company-Wide Client Summary (All Clients)
                </h3>
                <span style="font-size: 11px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 3px 10px; border-radius: 999px; border: 1px solid #bfdbfe;">
                    Company-Wide Metrics
                </span>
            </div>
            <section class="summary-cards" style="display: flex; gap: 14px; flex-wrap: wrap;">
                <div class="summary-card" data-filter-type="total" style="flex: 1; min-width: 130px; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">All Clients (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--ink); margin-top: 4px;">{{ $companyMetrics['total'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="active" style="flex: 1; min-width: 130px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Active (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #166534; margin-top: 4px;">{{ $companyMetrics['active'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="discontinued" style="flex: 1; min-width: 130px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px;">Discontinued (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #991b1b; margin-top: 4px;">{{ $companyMetrics['discontinued'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="barred" style="flex: 1; min-width: 130px; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">Barred (> 0%) (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #b91c1c; margin-top: 4px;">{{ $companyMetrics['barred'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="highRisk" style="flex: 1; min-width: 130px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px;">High Risk (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #9a3412; margin-top: 4px;">{{ $companyMetrics['highRisk'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="declining" style="flex: 1; min-width: 130px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #be123c; text-transform: uppercase; letter-spacing: 0.5px;">Declining (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #be123c; margin-top: 4px;">{{ $companyMetrics['declining'] ?? 0 }}</div>
                </div>
                <div class="summary-card" data-filter-type="improving" style="flex: 1; min-width: 130px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Improving (Company)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #047857; margin-top: 4px;">{{ $companyMetrics['improving'] ?? 0 }}</div>
                </div>
            </section>
        </div>
    @endif

    <!-- Scoped Cards Section -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <h3 style="font-size: 13px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                @if($isTeamScoped)
                    {{ ($viewScope ?? 'team') === 'my' ? 'My Client Summary (Assigned to Me)' : 'My Team Client Summary (Team Access)' }}
                @else
                    Company-Wide Client Summary
                @endif
            </h3>
            <span style="font-size: 11px; font-weight: 700; background: #ecfdf5; color: #047857; padding: 3px 10px; border-radius: 999px; border: 1px solid #a7f3d0;">
                @if($isTeamScoped)
                    {{ ($viewScope ?? 'team') === 'my' ? 'Direct KAM Assignments' : 'Your Team Assigned Access' }}
                @else
                    All Company Clients
                @endif
            </span>
        </div>
        <section class="summary-cards" style="display: flex; gap: 14px; flex-wrap: wrap;">
            <div class="summary-card" data-filter-type="total" style="flex: 1; min-width: 130px; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Total Clients' : 'My Team Total Clients' }}
                    @else
                        Total Clients
                    @endif
                </div>
                <div id="sumTotalCount" style="font-size: 22px; font-weight: 800; color: var(--ink); margin-top: 4px;">0</div>
            </div>
            <div class="summary-card" data-filter-type="active" style="flex: 1; min-width: 130px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Active' : 'My Team Active' }}
                    @else
                        Active
                    @endif
                </div>
                <div id="sumActiveCount" style="font-size: 22px; font-weight: 800; color: #166534; margin-top: 4px;">0</div>
            </div>
            <div class="summary-card" data-filter-type="discontinued" style="flex: 1; min-width: 130px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Discontinued' : 'My Team Discontinued' }}
                    @else
                        Discontinued
                    @endif
                </div>
                <div id="sumDiscontinuedCount" style="font-size: 22px; font-weight: 800; color: #991b1b; margin-top: 4px;">0</div>
            </div>
            <div class="summary-card" data-filter-type="barred" style="flex: 1; min-width: 130px; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Barred (> 0%)' : 'My Team Barred (> 0%)' }}
                    @else
                        Barred (> 0%)
                    @endif
                </div>
                <div id="sumBarredCount" style="font-size: 22px; font-weight: 800; color: #b91c1c; margin-top: 4px;">0</div>
            </div>
            <div class="summary-card" data-filter-type="highRisk" style="flex: 1; min-width: 130px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My High Risk' : 'My Team High Risk' }}
                    @else
                        High Risk
                    @endif
                </div>
                <div id="sumHighRiskCount" style="font-size: 22px; font-weight: 800; color: #9a3412; margin-top: 4px;">0</div>
            </div>
            <div class="summary-card" id="cardDeclining" data-filter-type="declining" style="flex: 1; min-width: 130px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #be123c; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Declining' : 'My Team Declining' }}
                    @else
                        Declining
                    @endif
                </div>
                <div id="sumDecliningCount" style="font-size: 22px; font-weight: 800; color: #be123c; margin-top: 4px;">{{ $decliningCount ?? 0 }}</div>
            </div>
            <div class="summary-card" id="cardImproving" data-filter-type="improving" style="flex: 1; min-width: 130px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.2s ease;">
                <div style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">
                    @if($isTeamScoped)
                        {{ ($viewScope ?? 'team') === 'my' ? 'My Improving' : 'My Team Improving' }}
                    @else
                        Improving
                    @endif
                </div>
                <div id="sumImprovingCount" style="font-size: 22px; font-weight: 800; color: #047857; margin-top: 4px;">{{ $improvingCount ?? 0 }}</div>
            </div>
        </section>
    </div>

    @php
        $uniqueSmTeams = $clients->pluck('team_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $uniqueCollectionTeams = $clients->pluck('collection_team_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $uniqueSmKams = $clients->pluck('sm_kam')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $uniqueCollectionKams = $clients->pluck('collection_kam')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $crRanges = config('risk.ranges') ?? [];
        $riskCategories = collect($crRanges)->pluck('category')->unique()->filter()->values();
    @endphp

    <section class="filter-panel" style="background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 16px; margin-bottom: 20px; box-shadow: var(--shadow);">
        <!-- Top Toolbar: Search with Icon, Filter Toggle, Clear Filters, and Page Size -->
        <div id="filterPanelToolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 0; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1; max-width: 640px; min-width: 260px; flex-wrap: wrap;">
                <!-- Search Field with Preceding Search Icon -->
                <div style="position: relative; flex: 1; min-width: 240px; display: flex; align-items: center;">
                    <span style="position: absolute; left: 12px; display: flex; align-items: center; pointer-events: none; color: var(--muted); z-index: 2;" title="Search">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input class="filter-input" id="clientSearch" type="text" placeholder="Search across all columns in real-time..." style="width: 100%; height: 38px; padding-left: 36px; border: 1px solid var(--line); border-radius: 8px;">
                </div>

                <!-- Toggle Filters Button (Collapsible / Expandable) -->
                <button id="toggleFiltersBtn" class="button" type="button" style="height: 38px; padding: 0 13px; display: inline-flex; align-items: center; gap: 7px; font-weight: 700; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; cursor: pointer; user-select: none;" title="Toggle filter dropdowns">
                    <svg style="width: 15px; height: 15px; color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span id="filterToggleText">Filters</span>
                    <span id="activeFilterBadge" style="display: none; background: var(--primary); color: #ffffff; font-size: 11px; font-weight: 800; border-radius: 999px; padding: 1px 7px; line-height: 1.4;"></span>
                    <svg id="filterToggleIcon" style="width: 14px; height: 14px; color: var(--muted); transition: transform 0.25s ease;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <button class="button" id="clearSearch" type="button" style="display: none; height: 38px; padding: 0 14px; white-space: nowrap; font-weight: 700;">Reset Filters</button>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; font-weight: 700; color: var(--muted);">Show</span>
                <select id="pageSizeSelect" style="height: 38px; border: 1px solid var(--line); border-radius: 8px; padding: 0 10px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span style="font-size: 13px; font-weight: 700; color: var(--muted);">entries</span>
            </div>
        </div>

        <!-- Collapsible Filter Dropdowns Grid -->
        <div id="filterGridCollapse" style="display: none; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 10px; align-items: flex-end; padding-top: 14px; border-top: 1px solid #edf1f7; transition: all 0.25s ease;">
            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Status</label>
                <select id="statusFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Discontinued">Discontinued</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Barring Priority</label>
                <select id="priorityFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Barring Priorities</option>
                    <option value="P1">P1 (High)</option>
                    <option value="P2">P2 (Normal)</option>
                    <option value="None">Unassigned (None)</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Barring</label>
                <select id="barringFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All</option>
                    <option value="Barred">Barred (&gt; 0%)</option>
                    <option value="Unbarred">Unbarred (0%)</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Risk Segment</label>
                <select id="riskFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Risk Levels</option>
                    <option value="High Risk">High Risk</option>
                    @foreach($riskCategories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Growth Trend</label>
                <select id="growthTrendFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Trends</option>
                    <option value="improving">Improving</option>
                    <option value="declining">Declining</option>
                    <option value="stable">Stable</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">S&amp;M Team</label>
                <select id="smTeamFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All S&amp;M Teams</option>
                    @foreach($uniqueSmTeams as $tm)
                        <option value="{{ $tm }}">{{ $tm }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Collection Team</label>
                <select id="collectionTeamFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Collection Teams</option>
                    @foreach($uniqueCollectionTeams as $ctm)
                        <option value="{{ $ctm }}">{{ $ctm }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">S&amp;M KAM</label>
                <select id="smKamFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All S&amp;M KAMs</option>
                    @foreach($uniqueSmKams as $skm)
                        <option value="{{ $skm }}">{{ $skm }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Collection KAM</label>
                <select id="collectionKamFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Collection KAMs</option>
                    @foreach($uniqueCollectionKams as $ckm)
                        <option value="{{ $ckm }}">{{ $ckm }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Service Billing</label>
                <select id="serviceTypeFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Services</option>
                    <option value="NTTN">NTTN</option>
                    <option value="IIG">IIG</option>
                    <option value="ITC">ITC</option>
                    <option value="NIX">NIX</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.3px;">Misc</label>
                <select id="miscFilter" style="width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 6px; padding: 0 8px; font-size: 13px; background: #ffffff; outline: none; cursor: pointer; font-family: inherit; font-weight: 700; color: var(--ink);">
                    <option value="All">All Misc</option>
                    <option value="legal">Has Legal Action</option>
                    <option value="deferred">Has Deferred Period (>0d)</option>
                    <option value="agreement_expired">Agreement Expired</option>
                    <option value="btrc_license_expired">BTRC License Expired</option>
                </select>
            </div>
        </div>
    </section>

    <div class="client-table-wrap">
        <table class="client-table" id="clientTable">
            <thead>
                <tr>
                    <th class="sortable sort-asc" data-sort="client_name">Client Name</th>
                    <th class="sortable" data-sort="opus_id">OPUS ID</th>
                    <th class="sortable" data-sort="client_status">Status</th>
                    <th class="sortable" data-sort="license_billing">License Billing</th>
                    <th class="sortable" data-sort="team_name">S&M Team</th>
                    <th class="sortable" data-sort="collection_kam">Collection KAM</th>
                    <th class="sortable" data-sort="current_month_cr">Current Month CR</th>
                    <th class="sortable" data-sort="growth_trend">Growth Trend</th>
                    <th class="sortable" data-sort="risk_segment">Risk Segment</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="clientTableBody">
                <!-- Will be dynamically populated via Javascript -->
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 14px; padding: 0 4px;">
        <div id="tableInfo" style="font-size: 13px; color: var(--muted); font-weight: 700;">
            Showing 0 to 0 of 0 entries
        </div>
        <div id="paginationContainer"></div>
    </div>

    {{-- DISCONTINUE CONFIRMATION MODAL --}}
    <div id="discontinueModal" class="discontinue-modal">
        <form id="discontinueForm" class="discontinue-modal-content" method="POST" action="">
            @csrf
            <h2>Discontinue Client</h2>
            <p id="discontinueClientText" style="font-size: 14px; margin-bottom: 18px; line-height: 1.5; color: var(--ink);">
                Are you sure you want to mark this client as Discontinued?
            </p>
            
            <div class="field">
                <label for="service_discontinuation_date">Service Discontinuation Date</label>
                <input id="service_discontinuation_date" name="service_discontinuation_date" type="date" value="{{ date('Y-m-d') }}" required>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button class="button" type="button" onclick="closeDiscontinueModal()">Cancel</button>
                <button class="button primary" style="background-color: var(--danger); border-color: var(--danger);" type="submit">Discontinue</button>
            </div>
        </form>
    </div>

    {{-- CLIENT LOGS MODAL --}}
    <div id="clientLogsModal" class="discontinue-modal" style="z-index: 10001;">
        <div class="discontinue-modal-content" style="max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <span style="float: right; font-size: 24px; cursor: pointer; font-weight: bold; line-height: 1; color: var(--muted); margin-top: -10px;" onclick="closeLogsModal()">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 15px; font-size: 20px; color: var(--primary-dark); border-bottom: 2px solid var(--line); padding-bottom: 8px;">
                Client Audit Logs: <span id="logModalClientName" style="color: var(--ink);">Client Name</span>
            </h2>
            
            <div style="overflow-x: auto; margin-top: 15px; border: 1px solid var(--line); border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--line);">
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Date</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Field</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Old Value</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">New Value</th>
                            <th style="padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 800; text-align: left;">Updated By</th>
                        </tr>
                    </thead>
                    <tbody id="clientLogsTableBody">
                        <!-- Filled dynamically via JS -->
                    </tbody>
                </table>
            </div>
            
            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="button primary" type="button" onclick="closeLogsModal()">Close</button>
            </div>
        </div>
    </div>

    {{-- CLIENT SUMMARY TOOLTIP (POPOVER ON 2s HOVER) --}}
    <div id="clientInfoTooltip" style="position: fixed; display: none; z-index: 99999; width: 600px; max-width: 92vw; background: #ffffff; border-radius: 10px; border: 2px solid #0f172a; box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.24), 0 8px 16px -6px rgba(15, 23, 42, 0.14); padding: 16px 20px; pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease; opacity: 0; transform: translateY(6px); font-family: inherit;">
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const discontinueRouteTemplate = "{{ route('clients.discontinue', ':id') }}";
        const logsRouteTemplate = "{{ route('clients.logs', ':id') }}";

        function openDiscontinueModal(clientId, clientName) {
            const form = document.getElementById('discontinueForm');
            form.action = discontinueRouteTemplate.replace('%3Aid', clientId).replace(':id', clientId);
            
            document.getElementById('discontinueClientText').innerText = 
                `Are you sure you want to mark "${clientName}" as Discontinued? This will freeze their active subscription.`;
            
            const modal = document.getElementById('discontinueModal');
            modal.style.display = 'flex';
        }

        function closeDiscontinueModal() {
            document.getElementById('discontinueModal').style.display = 'none';
        }

        async function openLogsModal(clientId, clientName) {
            document.getElementById('logModalClientName').innerText = clientName;
            const tableBody = document.getElementById('clientLogsTableBody');
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">Loading logs...</td></tr>`;
            
            const modal = document.getElementById('clientLogsModal');
            modal.style.display = 'flex';
            
            try {
                const logsUrl = logsRouteTemplate.replace('%3Aid', clientId).replace(':id', clientId);
                const response = await fetch(logsUrl);
                const logs = await response.json();
                
                tableBody.innerHTML = '';
                if (logs && logs.length > 0) {
                    logs.forEach(log => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #edf2f7';
                        
                        tr.innerHTML = `
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--ink); white-space: nowrap;">${escapeHtml(log.date)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; font-weight: 700; color: var(--ink);">${escapeHtml(log.field)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--muted);">${escapeHtml(log.old)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; font-weight: 700; color: var(--primary-dark);">${escapeHtml(log.new)}</td>
                            <td style="padding: 10px 12px; font-size: 13px; color: var(--ink); white-space: nowrap;">${escapeHtml(log.user)}</td>
                        `;
                        tableBody.appendChild(tr);
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">No logs found for this client.</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--danger);">Failed to load logs. Please try again.</td></tr>`;
            }
        }

        function closeLogsModal() {
            document.getElementById('clientLogsModal').style.display = 'none';
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Close when clicking background
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('discontinueModal');
            if (e.target === modal) {
                closeDiscontinueModal();
            }
            const logsModal = document.getElementById('clientLogsModal');
            if (e.target === logsModal) {
                closeLogsModal();
            }
        });

        // Client-side search, sort, and pagination logic
        $(document).ready(function() {
            // Data passed from Blade
            const clients = @json($clients);
            const canUpdateClients = @json(auth()->user()->can('update clients'));
            const editRouteTemplate = "{{ route('clients.edit', ':id') }}";
            const drilldownBaseUrl = "{{ route('dashboard.clients.index') }}";
            const discontinuedDrilldownBaseUrl = "{{ route('dashboard.discontinued-clients.index') }}";
            
            let filteredClients = [...clients];
            let currentPage = 1;
            let pageSize = parseInt($('#pageSizeSelect').val()) || 15;
            let sortField = 'client_name';
            let sortDirection = 'asc';

            const searchInput = document.getElementById('clientSearch');
            const clearBtn = document.getElementById('clearSearch');
            const clientTableBody = document.getElementById('clientTableBody');
            const tableInfo = document.getElementById('tableInfo');
            const paginationContainer = document.getElementById('paginationContainer');

            function getClientTrendStatus(client) {
                if (typeof client.growth_trend === 'string') {
                    return client.growth_trend;
                }
                if (client.growth_trend && typeof client.growth_trend === 'object' && client.growth_trend.trend_status) {
                    return client.growth_trend.trend_status;
                }
                if (client.growth_trend_status && typeof client.growth_trend_status === 'string') {
                    return client.growth_trend_status;
                }
                return 'stable';
            }

            function getGrowthTrendBadge(client) {
                const trend = getClientTrendStatus(client).toLowerCase();
                let mrcPct = 0;
                if (client.mrc_change_pct !== undefined && client.mrc_change_pct !== null) {
                    mrcPct = parseFloat(client.mrc_change_pct);
                } else if (client.growth_trend && typeof client.growth_trend === 'object' && client.growth_trend.mrc_change_pct !== undefined) {
                    mrcPct = parseFloat(client.growth_trend.mrc_change_pct);
                }

                let crVal = 0;
                if (client.cr_change_val !== undefined && client.cr_change_val !== null) {
                    crVal = parseFloat(client.cr_change_val);
                } else if (client.growth_trend && typeof client.growth_trend === 'object' && client.growth_trend.cr_change_val !== undefined) {
                    crVal = parseFloat(client.growth_trend.cr_change_val);
                }

                const mrcSign = mrcPct > 0 ? '+' : '';
                const mrcStr = `${mrcSign}${mrcPct.toFixed(1)}%`;
                const crSign = crVal > 0 ? '+' : '';
                const crStr = `${crSign}${crVal.toFixed(2)}`;

                const titleText = `12M MRC Change: ${mrcStr}, CR Change: ${crStr}`;

                if (trend === 'improving') {
                    return `<span class="status-badge" style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; white-space: nowrap;" title="${titleText}">
                        ↑ Improving (${mrcStr})
                    </span>`;
                } else if (trend === 'declining') {
                    return `<span class="status-badge" style="background: #ffe4e6; color: #be123c; font-size: 11px; font-weight: 700; white-space: nowrap;" title="${titleText}">
                        ↓ Declining (${mrcStr})
                    </span>`;
                } else {
                    return `<span class="status-badge" style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; white-space: nowrap;" title="${titleText}">
                        — Stable (${mrcStr})
                    </span>`;
                }
            }

            let lastRenderedStatus = null;

            function updateTableHeader(status) {
                if (lastRenderedStatus === status) return;
                lastRenderedStatus = status;

                const thead = document.querySelector('#clientTable thead');
                let headerHtml = '';

                if (status === 'Barred') {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'barring_percentage' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="barring_percentage">Barred %</th>
                            <th class="sortable ${sortField === 'barred_at' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="barred_at">Barring Aging</th>
                            <th class="sortable ${sortField === 'collection_kam' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="collection_kam">Collection KAM</th>
                            <th class="sortable ${sortField === 'current_month_cr' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="current_month_cr">Current Month CR</th>
                            <th class="sortable ${sortField === 'growth_trend' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="growth_trend">Growth Trend</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                } else if (status === 'Discontinued') {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'legal' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="legal">Legal Action</th>
                            <th class="sortable ${sortField === 'service_discontinuation_date' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="service_discontinuation_date">Discontinued Date</th>
                            <th class="sortable ${sortField === 'btrc_license_discontinuation_date' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="btrc_license_discontinuation_date">BTRC License Discont.</th>
                            <th class="sortable ${sortField === 'btrc_letter' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="btrc_letter">BTRC Letter</th>
                            <th class="sortable ${sortField === 'growth_trend' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="growth_trend">Growth Trend</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                } else {
                    headerHtml = `
                        <tr>
                            <th class="sortable ${sortField === 'client_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_name">Client Name</th>
                            <th class="sortable ${sortField === 'opus_id' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="opus_id">OPUS ID</th>
                            <th class="sortable ${sortField === 'client_status' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="client_status">Status</th>
                            <th class="sortable ${sortField === 'license_billing' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="license_billing">License Billing</th>
                            <th class="sortable ${sortField === 'team_name' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="team_name">S&M Team</th>
                            <th class="sortable ${sortField === 'collection_kam' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="collection_kam">Collection KAM</th>
                            <th class="sortable ${sortField === 'current_month_cr' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="current_month_cr">Current Month CR</th>
                            <th class="sortable ${sortField === 'growth_trend' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="growth_trend">Growth Trend</th>
                            <th class="sortable ${sortField === 'risk_segment' ? (sortDirection === 'asc' ? 'sort-asc' : 'sort-desc') : ''}" data-sort="risk_segment">Risk Segment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    `;
                }

                thead.innerHTML = headerHtml;

                // Re-bind click listeners for sorting
                thead.querySelectorAll('th.sortable').forEach(th => {
                    th.addEventListener('click', () => {
                        const field = th.dataset.sort;
                        if (sortField === field) {
                            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortField = field;
                            sortDirection = 'asc';
                        }

                        // Reset visual classes
                        thead.querySelectorAll('th.sortable').forEach(h => {
                            h.classList.remove('sort-asc', 'sort-desc');
                        });
                        th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                        render();
                    });
                });
            }

            // Render function
            function render() {
                // 1. Filter
                const query = searchInput.value.toLowerCase().trim();
                const selectedStatus = document.getElementById('statusFilter').value;
                const selectedPriority = document.getElementById('priorityFilter').value;
                const selectedBarring = document.getElementById('barringFilter').value;
                const selectedRisk = document.getElementById('riskFilter').value;
                const selectedTrend = document.getElementById('growthTrendFilter').value;
                const selectedSmTeam = document.getElementById('smTeamFilter') ? document.getElementById('smTeamFilter').value : 'All';
                const selectedCollectionTeam = document.getElementById('collectionTeamFilter') ? document.getElementById('collectionTeamFilter').value : 'All';
                const selectedSmKam = document.getElementById('smKamFilter') ? document.getElementById('smKamFilter').value : 'All';
                const selectedCollectionKam = document.getElementById('collectionKamFilter') ? document.getElementById('collectionKamFilter').value : 'All';
                const selectedService = document.getElementById('serviceTypeFilter').value;
                const selectedMisc = document.getElementById('miscFilter') ? document.getElementById('miscFilter').value : (document.getElementById('complianceFilter') ? document.getElementById('complianceFilter').value : 'All');

                const activeTableStatus = (selectedBarring === 'Barred') ? 'Barred' : selectedStatus;
                updateTableHeader(activeTableStatus);

                filteredClients = clients.filter(client => {
                    const clientTrend = getClientTrendStatus(client);
                    const barringPct = parseFloat(client.barring_percentage) || 0;
                    const priorityVal = (client.barring_priority || '').trim().toUpperCase();

                    // Check search query
                    if (query) {
                        const matchesSearch = 
                               (client.client_name && String(client.client_name).toLowerCase().includes(query)) ||
                               (client.opus_id && String(client.opus_id).toLowerCase().includes(query)) ||
                               (client.client_status && String(client.client_status).toLowerCase().includes(query)) ||
                               (client.barring_priority && String(client.barring_priority).toLowerCase().includes(query)) ||
                               (client.license_billing && String(client.license_billing).toLowerCase().includes(query)) ||
                               (client.service_type_billing && String(client.service_type_billing).toLowerCase().includes(query)) ||
                               (client.team_name && String(client.team_name).toLowerCase().includes(query)) ||
                               (client.collection_team_name && String(client.collection_team_name).toLowerCase().includes(query)) ||
                               (client.sm_kam && String(client.sm_kam).toLowerCase().includes(query)) ||
                               (client.collection_kam && String(client.collection_kam).toLowerCase().includes(query)) ||
                               (client.current_month_cr && String(client.current_month_cr).toLowerCase().includes(query)) ||
                               (clientTrend && clientTrend.toLowerCase().includes(query)) ||
                               (client.risk_segment && String(client.risk_segment).toLowerCase().includes(query));
                        if (!matchesSearch) return false;
                    }

                    // Check status
                    if (selectedStatus && selectedStatus !== 'All') {
                        const statusMatch = client.client_status && String(client.client_status).toLowerCase() === selectedStatus.toLowerCase();
                        if (!statusMatch) return false;
                    }

                    // Check barring priority filter
                    if (selectedPriority && selectedPriority !== 'All') {
                        if (selectedPriority === 'None') {
                            const isNone = (!client.barring_priority || priorityVal === '' || priorityVal === 'NONE' || priorityVal === 'N/A' || priorityVal === 'NULL');
                            if (!isNone) return false;
                        } else {
                            if (priorityVal !== selectedPriority.toUpperCase()) return false;
                        }
                    }

                    // Check barring status filter
                    if (selectedBarring && selectedBarring !== 'All') {
                        if (selectedBarring === 'Barred') {
                            if (barringPct <= 0) return false;
                        } else if (selectedBarring === 'Unbarred') {
                            if (barringPct !== 0) return false;
                        }
                    }

                    // Check risk
                    if (selectedRisk && selectedRisk !== 'All') {
                        if (selectedRisk === 'High Risk') {
                            const riskMatch = client.risk_segment && ['Risky', 'High Risky', 'Most Risky'].includes(client.risk_segment);
                            if (!riskMatch) return false;
                        } else {
                            const riskMatch = client.risk_segment && String(client.risk_segment).toLowerCase() === selectedRisk.toLowerCase();
                            if (!riskMatch) return false;
                        }
                    }

                    // Check growth trend
                    if (selectedTrend && selectedTrend !== 'All') {
                        const trendMatch = clientTrend && clientTrend.toLowerCase() === selectedTrend.toLowerCase();
                        if (!trendMatch) return false;
                    }

                    // Check S&M Team filter
                    if (selectedSmTeam && selectedSmTeam !== 'All') {
                        const clientSmTeam = (client.team_name || '').trim();
                        if (clientSmTeam !== selectedSmTeam) return false;
                    }

                    // Check Collection Team filter
                    if (selectedCollectionTeam && selectedCollectionTeam !== 'All') {
                        const clientColTeam = (client.collection_team_name || '').trim();
                        if (clientColTeam !== selectedCollectionTeam) return false;
                    }

                    // Check S&M KAM filter
                    if (selectedSmKam && selectedSmKam !== 'All') {
                        const clientSmKam = (client.sm_kam || '').trim();
                        if (clientSmKam !== selectedSmKam) return false;
                    }

                    // Check Collection KAM filter
                    if (selectedCollectionKam && selectedCollectionKam !== 'All') {
                        const clientColKam = (client.collection_kam || '').trim();
                        if (clientColKam !== selectedCollectionKam) return false;
                    }

                    // Check Service Type Billing filter
                    if (selectedService && selectedService !== 'All') {
                        const combinedBilling = ((client.service_type_billing || '') + ' ' + (client.license_billing || '')).toUpperCase();
                        if (!combinedBilling.includes(selectedService.toUpperCase())) return false;
                    }

                    // Check Misc filter
                    if (selectedMisc && selectedMisc !== 'All') {
                        if (selectedMisc === 'legal') {
                            const leg = String(client.legal || '').trim().toLowerCase();
                            if (!leg || leg === 'no' || leg === 'n/a' || leg === '0' || leg === 'false' || leg === 'none') return false;
                        } else if (selectedMisc === 'deferred') {
                            const def = parseInt(client.deferred_period) || 0;
                            if (def <= 0) return false;
                        } else if (selectedMisc === 'agreement_expired') {
                            let isExpired = false;
                            if (client.overall_agreement_health && String(client.overall_agreement_health).toLowerCase() === 'expired') {
                                isExpired = true;
                            } else {
                                let items = client.agreement_status;
                                if (typeof items === 'string') {
                                    try { items = JSON.parse(items); } catch(e) {}
                                }
                                if (typeof items === 'string') {
                                    try { items = JSON.parse(items); } catch(e) {}
                                }
                                if (Array.isArray(items) && items.length > 0) {
                                    const todayStr = new Date().toISOString().slice(0, 10);
                                    for (const item of items) {
                                        if (!item) continue;
                                        const st = String(item.status || item.ref_status || item.ref_no || '').toLowerCase();
                                        if (st.includes('expired')) {
                                            isExpired = true;
                                            break;
                                        }
                                        if (item.expiry_date) {
                                            const exp = String(item.expiry_date).slice(0, 10);
                                            if (exp && exp <= todayStr) {
                                                isExpired = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            if (!isExpired) return false;
                        } else if (selectedMisc === 'btrc_license_expired') {
                            if (!client.btrc_license_discontinuation_date) return false;
                            const rawDate = String(client.btrc_license_discontinuation_date).slice(0, 10);
                            if (!rawDate || rawDate === '0000-00-00') return false;
                            const todayStr = new Date().toISOString().slice(0, 10);
                            if (rawDate > todayStr) return false;
                        }
                    }

                    return true;
                });

                // Calculate summary counts from current filtered list
                let activeCount = 0;
                let discontinuedCount = 0;
                let barredCount = 0;
                let highRiskCount = 0;
                let decliningCount = 0;
                let improvingCount = 0;

                filteredClients.forEach(client => {
                    const status = (client.client_status || '').toLowerCase();
                    const barringPct = parseFloat(client.barring_percentage) || 0;

                    if (status === 'discontinued') {
                        discontinuedCount++;
                    } else {
                        activeCount++;
                    }

                    if (barringPct > 0) {
                        barredCount++;
                    }

                    if (client.risk_segment && ['Risky', 'High Risky', 'Most Risky'].includes(client.risk_segment)) {
                        highRiskCount++;
                    }

                    const trend = getClientTrendStatus(client).toLowerCase();
                    if (trend === 'declining') decliningCount++;
                    else if (trend === 'improving') improvingCount++;
                });

                document.getElementById('sumTotalCount').textContent = filteredClients.length;
                document.getElementById('sumActiveCount').textContent = activeCount;
                document.getElementById('sumDiscontinuedCount').textContent = discontinuedCount;
                document.getElementById('sumBarredCount').textContent = barredCount;
                document.getElementById('sumHighRiskCount').textContent = highRiskCount;
                document.getElementById('sumDecliningCount').textContent = decliningCount;
                document.getElementById('sumImprovingCount').textContent = improvingCount;

                let activeDropdownCount = 0;
                if (selectedStatus && selectedStatus !== 'All') activeDropdownCount++;
                if (selectedPriority && selectedPriority !== 'All') activeDropdownCount++;
                if (selectedBarring && selectedBarring !== 'All') activeDropdownCount++;
                if (selectedRisk && selectedRisk !== 'All') activeDropdownCount++;
                if (selectedTrend && selectedTrend !== 'All') activeDropdownCount++;
                if (selectedSmTeam && selectedSmTeam !== 'All') activeDropdownCount++;
                if (selectedCollectionTeam && selectedCollectionTeam !== 'All') activeDropdownCount++;
                if (selectedSmKam && selectedSmKam !== 'All') activeDropdownCount++;
                if (selectedCollectionKam && selectedCollectionKam !== 'All') activeDropdownCount++;
                if (selectedService && selectedService !== 'All') activeDropdownCount++;
                if (selectedMisc && selectedMisc !== 'All') activeDropdownCount++;

                const activeFilterBadge = document.getElementById('activeFilterBadge');
                if (activeFilterBadge) {
                    if (activeDropdownCount > 0) {
                        activeFilterBadge.style.display = 'inline-block';
                        activeFilterBadge.textContent = activeDropdownCount;
                    } else {
                        activeFilterBadge.style.display = 'none';
                    }
                }

                const hasActiveFilters = Boolean(query || activeDropdownCount > 0);
                clearBtn.style.display = hasActiveFilters ? 'inline-block' : 'none';

                // 2. Sort
                filteredClients.sort((a, b) => {
                    let valA = a[sortField];
                    let valB = b[sortField];

                    if (sortField === 'barring_priority') {
                        const prioRank = { 'P1': 1, 'P2': 2, 'NONE': 99, '': 99 };
                        const rankA = prioRank[(a.barring_priority || '').trim().toUpperCase()] || 99;
                        const rankB = prioRank[(b.barring_priority || '').trim().toUpperCase()] || 99;
                        if (rankA < rankB) return sortDirection === 'asc' ? -1 : 1;
                        if (rankA > rankB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    }

                    if (sortField === 'current_month_cr' || sortField === 'barring_percentage') {
                        const numA = valA === 'N/A' || valA === null || valA === undefined ? -999999 : parseFloat(valA);
                        const numB = valB === 'N/A' || valB === null || valB === undefined ? -999999 : parseFloat(valB);
                        if (numA < numB) return sortDirection === 'asc' ? -1 : 1;
                        if (numA > numB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    }

                    if (sortField === 'legal') {
                        const boolA = valA ? 1 : 0;
                        const boolB = valB ? 1 : 0;
                        if (boolA < boolB) return sortDirection === 'asc' ? -1 : 1;
                        if (boolA > boolB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    }

                    valA = valA || '';
                    valB = valB || '';

                    // Convert to lowercase strings for case-insensitive sorting if they are strings
                    if (typeof valA === 'string') valA = valA.toLowerCase();
                    if (typeof valB === 'string') valB = valB.toLowerCase();

                    if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                    if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });

                // 3. Paginate
                const totalEntries = filteredClients.length;
                const totalPages = Math.ceil(totalEntries / pageSize) || 1;
                
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const startIndex = (currentPage - 1) * pageSize;
                const endIndex = Math.min(startIndex + pageSize, totalEntries);

                // Render Table Rows
                clientTableBody.innerHTML = '';
                
                if (totalEntries === 0) {
                    clientTableBody.innerHTML = `
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--muted); padding: 30px;">
                                No clients match your search criteria.
                            </td>
                        </tr>
                    `;
                    tableInfo.textContent = 'Showing 0 to 0 of 0 entries';
                    paginationContainer.innerHTML = '';
                    return;
                }

                const displayed = filteredClients.slice(startIndex, endIndex);
                displayed.forEach(client => {
                    const tr = document.createElement('tr');
                    tr.className = 'client-row';
                    tr.style.opacity = '0';
                    tr.style.transition = 'opacity 0.2s ease-in-out';

                    // Determine status class and badge HTML
                    const barringPct = parseFloat(client.barring_percentage) || 0;
                    const statusLower = (client.client_status || '').toLowerCase();
                    const priorityVal = (client.barring_priority || '').trim().toUpperCase();
                    let priorityBadge = '';
                    if (priorityVal === 'P1') {
                        priorityBadge = `<span style="display: inline-block; font-size: 10px; font-weight: 900; color: #b91c1c; background: #fee2e2; padding: 1px 5px; border-radius: 4px; border: 1px solid #fca5a5; margin-left: 4px; vertical-align: middle;" title="Barring Priority: P1">P1</span>`;
                    } else if (priorityVal === 'P2') {
                        priorityBadge = `<span style="display: inline-block; font-size: 10px; font-weight: 900; color: #0284c7; background: #e0f2fe; padding: 1px 5px; border-radius: 4px; border: 1px solid #bae6fd; margin-left: 4px; vertical-align: middle;" title="Barring Priority: P2">P2</span>`;
                    }

                    let statusHtml = '';
                    if (statusLower.includes('discontinued') || barringPct >= 100) {
                        statusHtml = `<span class="status-badge status-discontinued">Discontinued</span>`;
                    } else {
                        statusHtml = `<span class="status-badge status-active">Active</span>`;
                        if (barringPct > 0) {
                            statusHtml += `<span style="font-size: 11px; font-weight: 700; color: #b91c1c; background: #fef2f2; padding: 2px 6px; border-radius: 4px; border: 1px solid #fecaca; margin-left: 4px;" title="Partially Barred ${barringPct}%">(${barringPct}%)</span>`;
                        }
                    }
                    if (priorityBadge) {
                        statusHtml += priorityBadge;
                    }

                    // Build actions HTML
                    let actionsHtml = '';
                    const escName = (client.client_name || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
                    if (canUpdateClients) {
                        const editUrl = editRouteTemplate.replace('%3Aid', client.client_id).replace(':id', client.client_id);
                        actionsHtml = `
                            <div class="actions-cell">
                                <a class="action-link" href="${editUrl}">Edit</a>
                                <button class="action-link" style="background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit;" type="button" onclick="openLogsModal('${client.client_id}', '${escName}')">
                                    Logs
                                </button>
                            </div>
                        `;
                    } else {
                        actionsHtml = `
                            <div class="actions-cell">
                                <button class="action-link" style="background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit;" type="button" onclick="openLogsModal('${client.client_id}', '${escName}')">
                                    Logs
                                </button>
                            </div>
                        `;
                    }

                    // Build drilldown URL
                    let drilldownLink = '';
                    const statusLowerVal = String(client.client_status || '').toLowerCase();
                    const isDiscOrBarred = statusLowerVal.includes('discontinued') || statusLowerVal.includes('barred');
                    const targetBaseUrl = isDiscOrBarred ? discontinuedDrilldownBaseUrl : drilldownBaseUrl;

                    if (client.summary_month) {
                        drilldownLink = `${targetBaseUrl}?segment=${encodeURIComponent(client.segment_name)}&range=${encodeURIComponent(client.cr_range)}&month=${encodeURIComponent(client.summary_month)}&client_id=${client.client_id}`;
                    } else {
                        drilldownLink = `${targetBaseUrl}?segment=${encodeURIComponent(client.segment_name)}&client_id=${client.client_id}`;
                    }

                    const trendBadge = getGrowthTrendBadge(client);

                    if (selectedStatus === 'Discontinued') {
                        const legalText = client.legal ? '<span style="color: #ef4444; font-weight: 800; background: #fee2e2; padding: 2px 8px; border-radius: 999px; font-size: 11px; text-transform: uppercase;">Yes</span>' : '<span style="color: #64748b; font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 999px; font-size: 11px; text-transform: uppercase;">No</span>';
                        
                        const formatJsDate = (dateStr) => {
                            if (!dateStr) return 'N/A';
                            try {
                                const d = new Date(dateStr);
                                if (isNaN(d.getTime())) return 'N/A';
                                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                            } catch (e) {
                                return 'N/A';
                            }
                        };

                        const discontDate = formatJsDate(client.service_discontinuation_date);
                        const btrcLicenseDate = formatJsDate(client.btrc_license_discontinuation_date);
                        const btrcLetterText = client.btrc_letter || 'N/A';

                        tr.innerHTML = `
                            <td style="font-weight: 700;">
                                <a href="${drilldownLink}" target="_blank" class="action-link client-name-link" data-client-id="${client.client_id}" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.client_name || 'N/A'}
                                </a>
                            </td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>${statusHtml}</td>
                            <td>${legalText}</td>
                            <td style="font-weight: 700; color: #b91c1c;">${discontDate}</td>
                            <td>${btrcLicenseDate}</td>
                            <td>${btrcLetterText}</td>
                            <td>${trendBadge}</td>
                            <td>${client.risk_segment || 'N/A'}</td>
                            <td>${actionsHtml}</td>
                        `;
                    } else if (activeTableStatus === 'Barred') {
                        const barredPctFormatted = barringPct > 0 ? `${barringPct.toFixed(2)}%` : '0%';
                        let agingStr = 'N/A';
                        if (client.barred_at) {
                            try {
                                const d = new Date(client.barred_at);
                                if (!isNaN(d.getTime())) {
                                    const diffDays = Math.max(0, Math.round((new Date() - d) / (1000 * 60 * 60 * 24)));
                                    const diffMonths = (diffDays / 30.4).toFixed(1);
                                    agingStr = `${diffDays}d (${diffMonths}m)`;
                                }
                            } catch(e) {}
                        }

                        tr.innerHTML = `
                            <td style="font-weight: 700;">
                                <a href="${drilldownLink}" target="_blank" class="action-link client-name-link" data-client-id="${client.client_id}" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.client_name || 'N/A'}
                                </a>
                            </td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>${statusHtml}</td>
                            <td>
                                <span class="status-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 800; font-size: 11px;">
                                    ${barredPctFormatted}
                                </span>
                            </td>
                            <td style="font-weight: 700; color: #b91c1c;">${agingStr}</td>
                            <td>${client.collection_kam || 'N/A'}</td>
                            <td>${client.current_month_cr || 'N/A'}</td>
                            <td>${trendBadge}</td>
                            <td>${client.risk_segment || 'N/A'}</td>
                            <td>${actionsHtml}</td>
                        `;
                    } else {
                        tr.innerHTML = `
                            <td style="font-weight: 700;">
                                <a href="${drilldownLink}" target="_blank" class="action-link client-name-link" data-client-id="${client.client_id}" style="border-bottom: 1px dashed var(--primary); text-decoration: none;">
                                    ${client.client_name || 'N/A'}
                                </a>
                            </td>
                            <td>${client.opus_id || 'N/A'}</td>
                            <td>${statusHtml}</td>
                            <td>${client.license_billing || 'N/A'}</td>
                            <td>${client.team_name || 'N/A'}</td>
                            <td>${client.collection_kam || 'N/A'}</td>
                            <td>${client.current_month_cr || 'N/A'}</td>
                            <td>${trendBadge}</td>
                            <td>${client.risk_segment || 'N/A'}</td>
                            <td>${actionsHtml}</td>
                        `;
                    }

                    clientTableBody.appendChild(tr);

                    // Trigger reflow & fade in
                    setTimeout(() => {
                        tr.style.opacity = '1';
                    }, 20);
                });
                // Update Info text
                const showStart = totalEntries === 0 ? 0 : startIndex + 1;
                const showEnd = endIndex;
                let infoString = `Showing ${showStart} to ${showEnd} of ${totalEntries} entries`;
                if (totalEntries < clients.length) {
                    infoString += ` (filtered from ${clients.length} total entries)`;
                }
                tableInfo.textContent = infoString;

                // Render pagination buttons
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                paginationContainer.innerHTML = '';
                if (totalPages <= 1) return;

                const ul = document.createElement('ul');
                ul.className = 'pagination';

                // First Page Button
                const firstLi = document.createElement('li');
                if (currentPage === 1) {
                    firstLi.className = 'disabled';
                    firstLi.innerHTML = `<span>&laquo;&laquo;</span>`;
                } else {
                    const firstA = document.createElement('a');
                    firstA.href = 'javascript:void(0)';
                    firstA.innerHTML = `&laquo;&laquo;`;
                    firstA.addEventListener('click', () => {
                        currentPage = 1;
                        render();
                    });
                    firstLi.appendChild(firstA);
                }
                ul.appendChild(firstLi);

                // Prev Page Button
                const prevLi = document.createElement('li');
                if (currentPage === 1) {
                    prevLi.className = 'disabled';
                    prevLi.innerHTML = `<span>&laquo;</span>`;
                } else {
                    const prevA = document.createElement('a');
                    prevA.href = 'javascript:void(0)';
                    prevA.innerHTML = `&laquo;`;
                    prevA.addEventListener('click', () => {
                        currentPage--;
                        render();
                    });
                    prevLi.appendChild(prevA);
                }
                ul.appendChild(prevLi);

                // Page Numbers
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, currentPage + 2);

                if (startPage > 1) {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = 'javascript:void(0)';
                    a.textContent = '1';
                    a.addEventListener('click', () => {
                        currentPage = 1;
                        render();
                    });
                    li.appendChild(a);
                    ul.appendChild(li);

                    if (startPage > 2) {
                        const dots = document.createElement('li');
                        dots.className = 'disabled';
                        dots.innerHTML = `<span>...</span>`;
                        ul.appendChild(dots);
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    const li = document.createElement('li');
                    if (i === currentPage) {
                        li.className = 'active';
                        li.innerHTML = `<span>${i}</span>`;
                    } else {
                        const a = document.createElement('a');
                        a.href = 'javascript:void(0)';
                        a.textContent = i;
                        a.addEventListener('click', () => {
                            currentPage = i;
                            render();
                        });
                        li.appendChild(a);
                    }
                    ul.appendChild(li);
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        const dots = document.createElement('li');
                        dots.className = 'disabled';
                        dots.innerHTML = `<span>...</span>`;
                        ul.appendChild(dots);
                    }

                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = 'javascript:void(0)';
                    a.textContent = totalPages;
                    a.addEventListener('click', () => {
                        currentPage = totalPages;
                        render();
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                }

                // Next Page Button
                const nextLi = document.createElement('li');
                if (currentPage === totalPages) {
                    nextLi.className = 'disabled';
                    nextLi.innerHTML = `<span>&raquo;</span>`;
                } else {
                    const nextA = document.createElement('a');
                    nextA.href = 'javascript:void(0)';
                    nextA.innerHTML = `&raquo;`;
                    nextA.addEventListener('click', () => {
                        currentPage++;
                        render();
                    });
                    nextLi.appendChild(nextA);
                }
                ul.appendChild(nextLi);

                // Last Page Button
                const lastLi = document.createElement('li');
                if (currentPage === totalPages) {
                    lastLi.className = 'disabled';
                    lastLi.innerHTML = `<span>&raquo;&raquo;</span>`;
                } else {
                    const lastA = document.createElement('a');
                    lastA.href = 'javascript:void(0)';
                    lastA.innerHTML = `&raquo;&raquo;`;
                    lastA.addEventListener('click', () => {
                        currentPage = totalPages;
                        render();
                    });
                    lastLi.appendChild(lastA);
                }
                ul.appendChild(lastLi);

                paginationContainer.appendChild(ul);
            }

            // Setup sorting events
            document.querySelectorAll('#clientTable th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const field = th.dataset.sort;
                    if (sortField === field) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortField = field;
                        sortDirection = 'asc';
                    }

                    // Update class names on headers
                    document.querySelectorAll('#clientTable th.sortable').forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                    render();
                });
            });

            // Collapsible Filter Div Controller
            const filterGrid = document.getElementById('filterGridCollapse');
            const filterToggleBtn = document.getElementById('toggleFiltersBtn');
            const filterToggleIcon = document.getElementById('filterToggleIcon');
            const filterPanelToolbar = document.getElementById('filterPanelToolbar');

            let isFilterCollapsed = true;

            function setFilterCollapse(collapsed, save = false) {
                isFilterCollapsed = collapsed;
                if (!filterGrid) return;

                if (collapsed) {
                    filterGrid.style.display = 'none';
                    if (filterToggleIcon) filterToggleIcon.style.transform = 'rotate(0deg)';
                    if (filterPanelToolbar) filterPanelToolbar.style.marginBottom = '0';
                } else {
                    filterGrid.style.display = 'grid';
                    if (filterToggleIcon) filterToggleIcon.style.transform = 'rotate(180deg)';
                    if (filterPanelToolbar) filterPanelToolbar.style.marginBottom = '14px';
                }

                if (save) {
                    try {
                        localStorage.setItem('clients_filter_panel_collapsed', collapsed ? 'true' : 'false');
                    } catch(e) {}
                }
            }

            if (filterToggleBtn) {
                filterToggleBtn.addEventListener('click', () => {
                    setFilterCollapse(!isFilterCollapsed, true);
                });
            }

            // Always collapsed by default on page load
            setFilterCollapse(true, false);

            // Helper to reset all filter controls
            function resetAllFilters() {
                searchInput.value = '';
                if (document.getElementById('statusFilter')) document.getElementById('statusFilter').value = 'All';
                if (document.getElementById('priorityFilter')) document.getElementById('priorityFilter').value = 'All';
                if (document.getElementById('barringFilter')) document.getElementById('barringFilter').value = 'All';
                if (document.getElementById('riskFilter')) document.getElementById('riskFilter').value = 'All';
                if (document.getElementById('growthTrendFilter')) document.getElementById('growthTrendFilter').value = 'All';
                if (document.getElementById('smTeamFilter')) document.getElementById('smTeamFilter').value = 'All';
                if (document.getElementById('collectionTeamFilter')) document.getElementById('collectionTeamFilter').value = 'All';
                if (document.getElementById('smKamFilter')) document.getElementById('smKamFilter').value = 'All';
                if (document.getElementById('collectionKamFilter')) document.getElementById('collectionKamFilter').value = 'All';
                if (document.getElementById('serviceTypeFilter')) document.getElementById('serviceTypeFilter').value = 'All';
                if (document.getElementById('miscFilter')) document.getElementById('miscFilter').value = 'All';
                if (document.getElementById('complianceFilter')) document.getElementById('complianceFilter').value = 'All';
                const activeFilterBadge = document.getElementById('activeFilterBadge');
                if (activeFilterBadge) activeFilterBadge.style.display = 'none';
            }

            // Parse URL search parameters on load
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            const priorityParam = urlParams.get('priority');
            const barringParam = urlParams.get('barring');
            const riskParam = urlParams.get('risk');
            const trendParam = urlParams.get('trend');
            const smTeamParam = urlParams.get('sm_team') || urlParams.get('team');
            const collectionTeamParam = urlParams.get('collection_team');
            const smKamParam = urlParams.get('sm_kam');
            const collectionKamParam = urlParams.get('collection_kam') || urlParams.get('kam');
            const serviceParam = urlParams.get('service');
            const miscParam = urlParams.get('misc') || urlParams.get('compliance');

            if (barringParam && document.getElementById('barringFilter')) {
                document.getElementById('barringFilter').value = barringParam;
            }
            if (statusParam) {
                if (statusParam.toLowerCase() === 'barred') {
                    if (document.getElementById('barringFilter')) document.getElementById('barringFilter').value = 'Barred';
                } else if (document.getElementById('statusFilter')) {
                    document.getElementById('statusFilter').value = statusParam;
                }
            }
            if (priorityParam && document.getElementById('priorityFilter')) {
                document.getElementById('priorityFilter').value = priorityParam;
            }
            if (riskParam && document.getElementById('riskFilter')) {
                document.getElementById('riskFilter').value = riskParam;
            }
            if (trendParam && document.getElementById('growthTrendFilter')) {
                document.getElementById('growthTrendFilter').value = trendParam;
            }
            if (smTeamParam && document.getElementById('smTeamFilter')) {
                document.getElementById('smTeamFilter').value = smTeamParam;
            }
            if (collectionTeamParam && document.getElementById('collectionTeamFilter')) {
                document.getElementById('collectionTeamFilter').value = collectionTeamParam;
            }
            if (smKamParam && document.getElementById('smKamFilter')) {
                document.getElementById('smKamFilter').value = smKamParam;
            }
            if (collectionKamParam && document.getElementById('collectionKamFilter')) {
                document.getElementById('collectionKamFilter').value = collectionKamParam;
            }
            if (serviceParam && document.getElementById('serviceTypeFilter')) {
                document.getElementById('serviceTypeFilter').value = serviceParam;
            }
            if (miscParam) {
                if (document.getElementById('miscFilter')) document.getElementById('miscFilter').value = miscParam;
                if (document.getElementById('complianceFilter')) document.getElementById('complianceFilter').value = miscParam;
            }

            // If active filter params are provided via URL, ensure filter panel is expanded
            if (statusParam || priorityParam || barringParam || riskParam || trendParam || smTeamParam || collectionTeamParam || smKamParam || collectionKamParam || serviceParam || miscParam) {
                setFilterCollapse(false);
            }

            // Bind change events for all 11 filters
            $('#statusFilter, #priorityFilter, #barringFilter, #riskFilter, #growthTrendFilter, #smTeamFilter, #collectionTeamFilter, #smKamFilter, #collectionKamFilter, #serviceTypeFilter, #miscFilter, #complianceFilter').on('change', () => {
                currentPage = 1;
                render();
            });

            // Universal Card Click Event for all summary cards
            $(document).on('click', '.summary-card[data-filter-type]', function() {
                const type = $(this).data('filter-type');

                // 1. First clear all search inputs and filter dropdowns
                resetAllFilters();

                // 2. Auto-expand filter panel so user sees the active filter
                setFilterCollapse(false);

                // 3. Apply the specific filter corresponding to the clicked card
                if (type === 'active') {
                    document.getElementById('statusFilter').value = 'Active';
                } else if (type === 'discontinued') {
                    document.getElementById('statusFilter').value = 'Discontinued';
                } else if (type === 'barred') {
                    if (document.getElementById('barringFilter')) document.getElementById('barringFilter').value = 'Barred';
                } else if (type === 'highRisk') {
                    document.getElementById('riskFilter').value = 'High Risk';
                } else if (type === 'declining') {
                    document.getElementById('growthTrendFilter').value = 'declining';
                } else if (type === 'improving') {
                    document.getElementById('growthTrendFilter').value = 'improving';
                }

                currentPage = 1;
                render();
            });

            // Input listener
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                render();
            });

            // Clear button listener
            clearBtn.addEventListener('click', () => {
                resetAllFilters();
                currentPage = 1;
                render();
            });

            // Page size selector listener
            document.getElementById('pageSizeSelect').addEventListener('change', (e) => {
                pageSize = parseInt(e.target.value) || 15;
                currentPage = 1;
                render();
            });

            // Initial render
            render();

            // --- Client Info Hover Tooltip (2-second delay) ---
            function formatTooltipDate(dateStr) {
                if (!dateStr) return 'N/A';
                try {
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return 'N/A';
                    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                } catch(e) {
                    return 'N/A';
                }
            }

            function formatSecurityCoverage(val) {
                if (val === null || val === undefined) return 'N/A';
                const str = String(val).trim();
                if (!str || str === '#DIV/0!' || str.toUpperCase() === 'N/A' || str === 'null') {
                    return 'N/A';
                }
                if (str.endsWith('%')) {
                    return str;
                }
                const num = parseFloat(str);
                if (isNaN(num)) {
                    return str;
                }
                let pct;
                if (num >= 0 && num <= 1) {
                    pct = Math.round(num * 10000) / 100;
                } else {
                    pct = Math.round(num * 100) / 100;
                }
                return `${pct}%`;
            }

            function buildClientTooltipHtml(client) {
                const statusLower = String(client.client_status || '').toLowerCase();
                const barringPct = parseFloat(client.barring_percentage) || 0;
                const priorityVal = (client.barring_priority || '').trim().toUpperCase();

                let statusBadgeHtml = '';
                if (statusLower.includes('discontinued') || barringPct >= 100) {
                    statusBadgeHtml = `<span style="display: inline-block; font-size: 11px; font-weight: 800; color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 999px;">Discontinued</span>`;
                } else if (barringPct > 0) {
                    statusBadgeHtml = `<span style="display: inline-block; font-size: 11px; font-weight: 800; color: #b91c1c; background: #fef2f2; padding: 2px 8px; border-radius: 999px; border: 1px solid #fecaca;">Barred (${barringPct}%)</span>`;
                } else {
                    statusBadgeHtml = `<span style="display: inline-block; font-size: 11px; font-weight: 800; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 999px;">Active</span>`;
                }

                let priorityBadge = '';
                if (priorityVal === 'P1') {
                    priorityBadge = `<span style="display: inline-block; font-size: 10px; font-weight: 900; color: #b91c1c; background: #fee2e2; padding: 1px 6px; border-radius: 4px; border: 1px solid #fca5a5;">P1 Priority</span>`;
                } else if (priorityVal === 'P2') {
                    priorityBadge = `<span style="display: inline-block; font-size: 10px; font-weight: 900; color: #0284c7; background: #e0f2fe; padding: 1px 6px; border-radius: 4px; border: 1px solid #bae6fd;">P2 Priority</span>`;
                }

                const riskVal = client.risk_segment || 'N/A';
                let riskBadgeColor = '#64748b';
                let riskBadgeBg = '#f1f5f9';
                if (['Risky', 'High Risky', 'Most Risky'].includes(riskVal)) {
                    riskBadgeColor = '#b91c1c';
                    riskBadgeBg = '#fee2e2';
                } else if (['Best', 'Good'].includes(riskVal)) {
                    riskBadgeColor = '#15803d';
                    riskBadgeBg = '#dcfce7';
                } else if (riskVal === 'Moderate') {
                    riskBadgeColor = '#b45309';
                    riskBadgeBg = '#fef3c7';
                }

                const trendBadge = getGrowthTrendBadge(client);

                let agreementHealth = client.overall_agreement_health;
                if (!agreementHealth) {
                    let items = client.agreement_status;
                    if (typeof items === 'string') {
                        try { items = JSON.parse(items); } catch(e) {}
                    }
                    if (typeof items === 'string') {
                        try { items = JSON.parse(items); } catch(e) {}
                    }
                    if (Array.isArray(items) && items.length > 0) {
                        const todayStr = new Date().toISOString().slice(0, 10);
                        let hasExpired = false;
                        for (const it of items) {
                            if (!it) continue;
                            const st = String(it.status || it.ref_status || it.ref_no || '').toLowerCase();
                            if (st.includes('expired') || (it.expiry_date && String(it.expiry_date).slice(0, 10) <= todayStr)) {
                                hasExpired = true;
                                break;
                            }
                        }
                        agreementHealth = hasExpired ? 'Expired' : 'Valid';
                    } else {
                        agreementHealth = 'No Record';
                    }
                }

                let agreementColor = '#15803d';
                let agreementBg = '#dcfce7';
                if (agreementHealth.includes('Expired')) {
                    agreementColor = '#b91c1c';
                    agreementBg = '#fee2e2';
                } else if (agreementHealth.includes('Renewal') || agreementHealth.includes('Due')) {
                    agreementColor = '#b45309';
                    agreementBg = '#fef3c7';
                } else if (agreementHealth === 'No Record') {
                    agreementColor = '#64748b';
                    agreementBg = '#f1f5f9';
                }

                const btrcExpireFormatted = formatTooltipDate(client.btrc_license_discontinuation_date);
                const secCoverage = formatSecurityCoverage(client.formatted_security_coverage || client.security_coverage);
                const defPeriod = parseInt(client.deferred_period) || 0;
                const defText = defPeriod > 0 ? `${defPeriod} days` : '0 days';
                const hasLegal = Boolean(client.legal && !['no', '0', 'none', 'n/a', 'false'].includes(String(client.legal).trim().toLowerCase()));

                const rawService = client.service_type_billing;
                let serviceTypes = rawService ? (Array.isArray(rawService) ? rawService.join(', ') : rawService) : (client.license_billing || 'N/A');

                return `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(client.client_name || 'N/A')}">
                                ${escapeHtml(client.client_name || 'N/A')}
                            </div>
                            <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-top: 2px;">
                                OPUS ID: <span style="color: var(--primary-dark); font-weight: 800;">${escapeHtml(client.opus_id || 'N/A')}</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0;">
                            ${statusBadgeHtml}
                            ${priorityBadge}
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; font-size: 11.5px; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">S&M Team / KAM</div>
                            <div style="font-weight: 700; color: #1e293b; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${escapeHtml(client.team_name || 'N/A')} / <span style="color: #0284c7;">${escapeHtml(client.sm_kam || 'N/A')}</span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Collection Team / KAM</div>
                            <div style="font-weight: 700; color: #1e293b; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${escapeHtml(client.collection_team_name || 'N/A')} / <span style="color: #0d9488;">${escapeHtml(client.collection_kam || 'N/A')}</span>
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Current CR & Risk</div>
                            <div style="font-weight: 800; color: #1e293b; margin-top: 1px; display: flex; align-items: center; gap: 5px;">
                                <span>${client.current_month_cr || 'N/A'}</span>
                                <span style="font-size: 10px; padding: 1px 6px; border-radius: 4px; background: ${riskBadgeBg}; color: ${riskBadgeColor}; font-weight: 700;">${riskVal}</span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Growth Trend</div>
                            <div style="margin-top: 1px;">
                                ${trendBadge}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Agreement Health</div>
                            <div style="margin-top: 1px;">
                                <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 4px; background: ${agreementBg}; color: ${agreementColor}; font-weight: 800;">${agreementHealth}</span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">BTRC License Expire</div>
                            <div style="font-weight: 700; color: #1e293b; margin-top: 1px;">
                                ${btrcExpireFormatted}
                            </div>
                        </div>

                        <div style="grid-column: span 2;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;">Service / License Billing</div>
                            <div style="font-weight: 700; color: #1e293b; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${escapeHtml(serviceTypes)}
                            </div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; font-size: 11px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <div><span style="color: #64748b; font-weight: 700;">Sec. Coverage:</span> <span style="font-weight: 800; color: #1e293b;">${escapeHtml(secCoverage)}</span></div>
                        <div><span style="color: #64748b; font-weight: 700;">Deferred:</span> <span style="font-weight: 800; color: #1e293b;">${defText}</span></div>
                        <div><span style="color: #64748b; font-weight: 700;">Legal:</span> <span style="font-weight: 800; color: ${hasLegal ? '#b91c1c' : '#64748b'};">${hasLegal ? 'Yes' : 'No'}</span></div>
                    </div>
                `;
            }

            const tooltipEl = document.getElementById('clientInfoTooltip');
            let hoverTimeout = null;
            let hideTimeout = null;
            let currentHoveredClientId = null;
            let isHoveringTooltip = false;

            function hideClientTooltip() {
                if (!tooltipEl) return;
                tooltipEl.style.opacity = '0';
                tooltipEl.style.transform = 'translateY(6px)';
                tooltipEl.style.pointerEvents = 'none';
                setTimeout(() => {
                    if (tooltipEl.style.opacity === '0') {
                        tooltipEl.style.display = 'none';
                    }
                }, 200);
            }

            function showClientTooltip(clientId, targetEl) {
                const client = clients.find(c => String(c.client_id) === String(clientId));
                if (!client || !tooltipEl) return;

                tooltipEl.innerHTML = buildClientTooltipHtml(client);
                tooltipEl.style.display = 'block';

                const rect = targetEl.getBoundingClientRect();
                const tooltipRect = tooltipEl.getBoundingClientRect();

                const tooltipWidth = tooltipRect.width || 600;
                const tooltipHeight = tooltipRect.height || 240;

                let left = rect.left;
                let top = rect.bottom + 8; // below link by default

                // If overflowing right edge of window
                if (left + tooltipWidth > window.innerWidth - 16) {
                    left = window.innerWidth - tooltipWidth - 16;
                }
                if (left < 16) {
                    left = 16;
                }

                // If overflowing bottom edge of window, show above
                if (top + tooltipHeight > window.innerHeight - 16) {
                    top = rect.top - tooltipHeight - 8;
                    if (top < 16) {
                        top = 16;
                    }
                }

                tooltipEl.style.left = `${left}px`;
                tooltipEl.style.top = `${top}px`;
                tooltipEl.style.opacity = '1';
                tooltipEl.style.transform = 'translateY(0)';
                tooltipEl.style.pointerEvents = 'auto';
            }

            $(document).on('mouseenter', '.client-name-link', function() {
                const target = this;
                const clientId = $(target).data('client-id');
                currentHoveredClientId = clientId;

                clearTimeout(hideTimeout);
                clearTimeout(hoverTimeout);

                // 2 second hover timer requirement
                hoverTimeout = setTimeout(() => {
                    if (currentHoveredClientId === clientId) {
                        showClientTooltip(clientId, target);
                    }
                }, 2000);
            });

            $(document).on('mouseleave', '.client-name-link', function() {
                clearTimeout(hoverTimeout);
                currentHoveredClientId = null;
                hideTimeout = setTimeout(() => {
                    if (!isHoveringTooltip) {
                        hideClientTooltip();
                    }
                }, 150);
            });

            if (tooltipEl) {
                tooltipEl.addEventListener('mouseenter', () => {
                    isHoveringTooltip = true;
                    clearTimeout(hideTimeout);
                });
                tooltipEl.addEventListener('mouseleave', () => {
                    isHoveringTooltip = false;
                    hideClientTooltip();
                });
            }

            window.addEventListener('scroll', () => {
                clearTimeout(hoverTimeout);
                hideClientTooltip();
            }, { passive: true });
        });
    </script>
@endpush
