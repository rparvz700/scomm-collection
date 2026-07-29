<?php

use App\Http\Controllers\Api\MonthRolloverController;
use Illuminate\Support\Facades\Route;

Route::get('/month-opening/rollover', [MonthRolloverController::class, 'rollover'])->name('api.month-opening.rollover');
Route::get('/guidance-logs', [\App\Http\Controllers\DashboardController::class, 'getGuidanceLogs'])->name('api.guidance-logs');
