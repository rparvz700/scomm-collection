<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollectionEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlySummaryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:view dashboard')
        ->name('dashboard');
    
    Route::get('/dashboard/clients/drilldown', [DashboardController::class, 'clientDrilldown']);
    Route::get('/dashboard/clients', [DashboardController::class, 'clientsIndex'])
        ->name('dashboard.clients.index');
    
    Route::get('/dashboard/client-trend/{client}',[DashboardController::class, 'clientTrend'])
        ->name('dashboard.client.trend');

    Route::get('/collection-entry', [CollectionEntryController::class, 'index'])
        ->middleware('permission:view collections')
        ->name('collection-entry.index');

    Route::post('/collection-entry', [CollectionEntryController::class, 'store'])
        ->middleware('permission:create collections')
        ->name('collection-entry.store');

    Route::get('/monthly-summary', [MonthlySummaryController::class, 'index'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary.index');

    Route::get('/monthly-summary/data', [MonthlySummaryController::class, 'data'])
        ->middleware('permission:view monthly summaries')
        ->name('monthly-summary.data');

    Route::post('/monthly-summary/bulk-update', [MonthlySummaryController::class, 'bulkUpdate'])
        ->middleware('permission:update monthly summaries')
        ->name('monthly-summary.bulk-update');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:manage roles')
        ->name('settings.index');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
