<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollectionEntryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlySummaryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardOptimizedController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        $role = $user->roles()->first();
        if ($role && $role->landing_page) {
            if (Route::has($role->landing_page)) {
                return redirect()->route($role->landing_page);
            }
        }
        
        // Fallbacks based on permission checks
        if ($user->can('view dashboard')) {
            return redirect()->route('dashboard.optimized');
        } elseif ($user->can('view collections')) {
            return redirect()->route('collection-entry.index');
        } elseif ($user->can('view monthly summaries')) {
            return redirect()->route('monthly-summary.index');
        } else {
            return redirect()->route('settings.index');
        }
    }
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::get('/dashboard-optimized', DashboardOptimizedController::class)
        ->middleware('permission:view dashboard')
        ->name('dashboard.optimized');

    Route::prefix('reports')->group(function () {
        Route::get('/builder', [ReportController::class, 'index'])
            ->middleware('permission:view reports')
            ->name('reports.builder');
        Route::post('/preview', [ReportController::class, 'preview'])
            ->middleware('permission:view reports')
            ->name('reports.preview');
        Route::post('/export', [ReportController::class, 'export'])
            ->middleware('permission:export reports')
            ->name('reports.export');
        Route::post('/save-template', [ReportController::class, 'saveTemplate'])
            ->middleware('permission:manage report templates')
            ->name('reports.save-template');
        Route::delete('/delete-template/{template}', [ReportController::class, 'deleteTemplate'])
            ->middleware('permission:manage report templates')
            ->name('reports.delete-template');
    });

    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/client-logs', [\App\Http\Controllers\LogController::class, 'clientLogs'])
            ->middleware('permission:view client logs')
            ->name('client');
        Route::get('/audit-logs', [\App\Http\Controllers\LogController::class, 'auditLogs'])
            ->middleware('permission:view audit logs')
            ->name('audit');
        Route::get('/guidance-logs', [\App\Http\Controllers\LogController::class, 'guidanceLogs'])
            ->middleware('permission:view guidance logs')
            ->name('guidance');
        Route::get('/system-access-logs', [\App\Http\Controllers\LogController::class, 'systemAccessLogs'])
            ->middleware('permission:view system access logs')
            ->name('system-access');
    });
    
    Route::get('/dashboard/clients/drilldown', [DashboardController::class, 'clientDrilldown']);
    Route::get('/dashboard/clients', [DashboardController::class, 'clientsIndex'])
        ->name('dashboard.clients.index');
    Route::get('/dashboard/discontinued-clients', [DashboardController::class, 'discontinuedClientsIndex'])
        ->name('dashboard.discontinued-clients.index');
    
    Route::get('/dashboard/client-trend/{client}',[DashboardController::class, 'clientTrend'])
        ->name('dashboard.client.trend');
    Route::get('/dashboard/client-trend-discontinued/{client}', [DashboardController::class, 'discontinuedClientTrend'])
        ->name('dashboard.client.trend.discontinued');
    Route::post('/dashboard/workflow/update', [DashboardController::class, 'updateWorkflowStatus'])
        ->middleware('permission:update workflow status')
        ->name('dashboard.workflow.update');
    Route::post('/dashboard/guidance/log', [DashboardController::class, 'logGuidance'])
        ->name('dashboard.guidance.log');
    Route::get('/collection-entry', [CollectionEntryController::class, 'index'])
        ->middleware('permission:view collections')
        ->name('collection-entry.index');

    Route::get('/collection-entry/client-metrics', [CollectionEntryController::class, 'clientMetrics'])
        ->middleware('permission:view collections')
        ->name('collection-entry.client-metrics');

    Route::get('/collection-entry/recent-entries', [CollectionEntryController::class, 'recentEntries'])
        ->middleware('permission:view collections')
        ->name('collection-entry.recent-entries');

    Route::post('/collection-entry', [CollectionEntryController::class, 'store'])
        ->middleware('permission:create collections')
        ->name('collection-entry.store');

    Route::get('/collections', [CollectionEntryController::class, 'collectionsIndex'])
        ->middleware(['role:collection_hod|admin'])
        ->name('collections.index');

    Route::get('/clients', [ClientController::class, 'index'])
        ->middleware('permission:view clients')
        ->name('clients.index');

    Route::get('/clients/{client}/logs', [ClientController::class, 'logs'])
        ->middleware('permission:view clients')
        ->name('clients.logs');

    Route::get('/clients/create', [ClientController::class, 'create'])
        ->middleware('permission:create clients')
        ->name('clients.create');

    Route::post('/clients', [ClientController::class, 'store'])
        ->middleware('permission:create clients')
        ->name('clients.store');

    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])
        ->middleware('permission:update clients')
        ->name('clients.edit');

    Route::put('/clients/{client}', [ClientController::class, 'update'])
        ->middleware('permission:update clients')
        ->name('clients.update');

    Route::post('/clients/{client}/discontinue', [ClientController::class, 'discontinue'])
        ->middleware('permission:update clients')
        ->name('clients.discontinue');

    Route::get('/monthly-summary', [MonthlySummaryController::class, 'index'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary.index');

    Route::get('/monthly-summary/data', [MonthlySummaryController::class, 'data'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary.data');

    Route::post('/monthly-summary/bulk-update', [MonthlySummaryController::class, 'bulkUpdate'])
        ->middleware('permission:update monthly summaries')
        ->name('monthly-summary.bulk-update');

    Route::get('/monthly-summary-discontinued', [\App\Http\Controllers\MonthlySummaryDiscontinuedController::class, 'index'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary-discontinued.index');

    Route::get('/monthly-summary-discontinued/data', [\App\Http\Controllers\MonthlySummaryDiscontinuedController::class, 'data'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary-discontinued.data');

    Route::post('/monthly-summary-discontinued/bulk-update', [\App\Http\Controllers\MonthlySummaryDiscontinuedController::class, 'bulkUpdate'])
        ->middleware('permission:update monthly summaries')
        ->name('monthly-summary-discontinued.bulk-update');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:manage roles')
        ->name('settings.index');

    Route::prefix('settings')->name('settings.')->group(function () {
        // User management CRUD
        Route::post('/users', [SettingsController::class, 'storeUser'])
            ->middleware('permission:manage users')
            ->name('users.store');
        Route::put('/users/{user}', [SettingsController::class, 'updateUser'])
            ->middleware('permission:manage users')
            ->name('users.update');
        Route::post('/users/{user}/reset-password', [SettingsController::class, 'resetUserPassword'])
            ->middleware('permission:manage users')
            ->name('users.reset-password');
        Route::delete('/users/{user}', [SettingsController::class, 'deleteUser'])
            ->middleware('permission:manage users')
            ->name('users.destroy');

        // Role management CRUD
        Route::post('/roles', [SettingsController::class, 'storeRole'])
            ->middleware('permission:manage roles')
            ->name('roles.store');
        Route::put('/roles/{role}', [SettingsController::class, 'updateRole'])
            ->middleware('permission:manage roles')
            ->name('roles.update');
        Route::put('/roles/{role}/landing-page', [SettingsController::class, 'updateRoleLandingPage'])
            ->middleware('permission:manage roles')
            ->name('roles.landing-page');
        Route::delete('/roles/{role}', [SettingsController::class, 'deleteRole'])
            ->middleware('permission:manage roles')
            ->name('roles.destroy');

        // System operations
        Route::post('/system/rollover', [SettingsController::class, 'triggerRollover'])
            ->middleware('permission:manage roles')
            ->name('system.rollover');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/api/month-opening/rollover', [\App\Http\Controllers\Api\MonthRolloverController::class, 'rollover'])->name('web.api.month-opening.rollover');
Route::get('/api/guidance-logs', [\App\Http\Controllers\DashboardController::class, 'getGuidanceLogs'])->name('web.api.guidance-logs');

