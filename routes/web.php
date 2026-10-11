<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollectionEntryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardOptimizedController;
use App\Http\Controllers\MonthlySummaryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
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
            return redirect()->route('dashboard');
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
    Route::get('/dashboard', DashboardOptimizedController::class)
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::get('/dashboard-optimized', DashboardOptimizedController::class)
        ->middleware('permission:view dashboard')
        ->name('dashboard.optimized');

    Route::get('/employee-avatar-json', [DashboardOptimizedController::class, 'getEmployeeAvatarJson'])
        ->middleware('permission:view dashboard')
        ->name('employee.avatar.json');

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
        Route::get('/collection-logs', [\App\Http\Controllers\LogController::class, 'collectionLogs'])
            ->middleware('permission:view collection logs')
            ->name('collection');
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
    
    Route::get('/dashboard/clients/drilldown', [DashboardOptimizedController::class, 'clientDrilldown']);
    Route::get('/dashboard/clients', [DashboardOptimizedController::class, 'clientsIndex'])
        ->name('dashboard.clients.index');
    Route::get('/dashboard/discontinued-clients', [DashboardOptimizedController::class, 'discontinuedClientsIndex'])
        ->name('dashboard.discontinued-clients.index');
    
    Route::get('/dashboard/client-trend/{client}', [ClientController::class, 'clientTrend'])
        ->name('dashboard.client.trend');
    Route::get('/dashboard/client-trend-discontinued/{client}', [ClientController::class, 'discontinuedClientTrend'])
        ->name('dashboard.client.trend.discontinued');
    Route::post('/dashboard/workflow/update', [DashboardOptimizedController::class, 'updateWorkflowStatus'])
        ->middleware('permission:update workflow status')
        ->name('dashboard.workflow.update');
    Route::post('/dashboard/guidance/log', [DashboardOptimizedController::class, 'logGuidance'])
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

    Route::get('/collection-entry/export', [CollectionEntryController::class, 'export'])
        ->middleware('permission:view collections')
        ->name('collection-entry.export');

    Route::post('/collection-entry', [CollectionEntryController::class, 'store'])
        ->middleware('permission:create collections')
        ->name('collection-entry.store');

    Route::get('/collections', [CollectionEntryController::class, 'collectionsIndex'])
        ->middleware('permission:view collections')
        ->name('collections.index');

    Route::put('/collections/{collection}', [CollectionEntryController::class, 'update'])
        ->middleware(['role:collection_kam|collection_supervisor|collection_hod|admin'])
        ->name('collections.update');

    Route::delete('/collections/{collection}', [CollectionEntryController::class, 'destroy'])
        ->middleware(['role:collection_hod|admin'])
        ->name('collections.destroy');

    Route::get('/waivers', [\App\Http\Controllers\WaiverController::class, 'index'])
        ->middleware(['role:collection_kam|collection_supervisor|collection_hod|admin'])
        ->name('waivers.index');

    Route::post('/waivers', [\App\Http\Controllers\WaiverController::class, 'store'])
        ->middleware(['role:collection_kam|collection_supervisor|collection_hod|admin'])
        ->name('waivers.store');

    Route::put('/waivers/{waiver}', [\App\Http\Controllers\WaiverController::class, 'update'])
        ->middleware(['role:collection_supervisor|collection_hod|admin'])
        ->name('waivers.update');

    Route::delete('/waivers/{waiver}', [\App\Http\Controllers\WaiverController::class, 'destroy'])
        ->middleware(['role:collection_supervisor|collection_hod|admin'])
        ->name('waivers.destroy');

    Route::get('/waivers/{waiver}/attachment', [\App\Http\Controllers\WaiverController::class, 'showAttachment'])
        ->name('waivers.attachment');

    Route::get('/clients', [ClientController::class, 'index'])
        ->middleware('permission:view clients')
        ->name('clients.index');

    Route::get('/clients/{client}/logs', [ClientController::class, 'logs'])
        ->middleware('permission:view clients')
        ->name('clients.logs');

    Route::get('/dashboard/clients/{client}/outstanding-summary', [ClientController::class, 'outstandingSummary'])
        ->name('clients.outstanding-summary');

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
Route::get('/api/guidance-logs', [\App\Http\Controllers\DashboardOptimizedController::class, 'getGuidanceLogs'])->name('web.api.guidance-logs');

Route::get('/run-housekeeping-audit', function () {
    $service = app(\App\Services\HousekeepingAuditService::class);
    $notification = $service->generateAndStoreReport();
    $service->emailReportToGroup($notification);
    return response()->json([
        'success' => true,
        'message' => 'Housekeeping audit report generated successfully and email dispatched to recipient group.',
        'report' => [
            'id' => $notification->id,
            'report_month' => $notification->report_month,
            'total_issues' => $notification->total_issues_count,
            'expired_agreements' => $notification->expired_agreements_count,
            'urgent_renewals' => $notification->urgent_renewals_count,
            'btrc_license_issues' => $notification->btrc_license_issues_count,
            'data_discrepancies' => $notification->data_discrepancies_count,
            'created_at' => $notification->created_at ? $notification->created_at->format('Y-m-d H:i:s') : null,
        ]
    ]);
});

Route::get('/api/housekeeping/latest-document', function () {
    $latest = \App\Models\HousekeepingNotification::latestDocument();
    if (!$latest) {
        return response()->json([
            'success' => false,
            'message' => 'No housekeeping report found.'
        ]);
    }
    return response()->json([
        'success' => true,
        'report_month' => $latest->report_month_label,
        'created_at' => $latest->created_at ? $latest->created_at->format('d M Y, h:i A') : 'N/A',
        'recipients' => config('housekeeping.recipients', []),
        'html_content' => $latest->formatted_html_content,
    ]);
});

Route::get('/inspect-service-types', function() {
    $types = \App\Models\Client::whereNotNull('service_type_billing')->pluck('service_type_billing')->unique()->values();
    $legals = \App\Models\Client::whereNotNull('legal')->pluck('legal')->unique()->values();
    return response()->json([
        'service_type_billing' => $types,
        'legal' => $legals
    ]);
});


Route::get('/api/sync-live-isp-data', function (\Illuminate\Http\Request $request) {
    $execute = $request->boolean('execute', false);
    $filePath = 'D:\\D\\Automation-Innovation\\scomm-collection\\Information for OS Automation.xlsx';


    try {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE client MODIFY legal VARCHAR(255) NULL');
        \Illuminate\Support\Facades\Schema::table('client', function (\Illuminate\Database\Schema\Blueprint $table) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('client', 'security_coverage')) {
                $table->string('security_coverage')->nullable();
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('client', 'btrc_letter')) {
                $table->string('btrc_letter')->nullable();
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('client', 'other_upstream')) {
                $table->boolean('other_upstream')->default(false);
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('client', 'btrc_license_discontinuation_date')) {
                $table->date('btrc_license_discontinuation_date')->nullable();
            }
        });
    } catch (\Throwable $e) {
        // Schema table update log
    }

    if (!file_exists($filePath)) {
        return response()->json(['success' => false, 'message' => 'Excel file not found at ' . $filePath]);
    }


    if (!class_exists('ZipArchive')) {
        return response()->json(['success' => false, 'message' => 'ZipArchive extension missing']);
    }

    $zip = new \ZipArchive();
    if ($zip->open($filePath) !== true) {
        return response()->json(['success' => false, 'message' => 'Failed to open Excel zip archive']);
    }

    // 1. Parse workbook to find "Live ISP" sheet
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $wbDoc = simplexml_load_string($workbookXml);
    $sheets = [];
    foreach ($wbDoc->sheets->sheet as $s) {
        $name = (string)$s['name'];
        $rId = (string)$s->attributes('r', true)['id'];
        $sheets[] = ['name' => $name, 'rId' => $rId];
    }

    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $relsDoc = simplexml_load_string($relsXml);
    $relMap = [];
    foreach ($relsDoc->Relationship as $rel) {
        $relMap[(string)$rel['Id']] = (string)$rel['Target'];
    }

    // Read sharedStrings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ssDoc = simplexml_load_string($ssXml);
        foreach ($ssDoc->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string)$si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = '';
            }
        }
    }

    $targetFile = null;
    foreach ($sheets as $s) {
        if (stripos($s['name'], 'live isp') !== false) {
            $targetFile = 'xl/' . ltrim($relMap[$s['rId']], '/');
            break;
        }
    }

    if (!$targetFile) {
        $zip->close();
        return response()->json(['success' => false, 'message' => 'Live ISP sheet not found']);
    }

    $sheetXml = $zip->getFromName($targetFile);
    $zip->close();

    $sDoc = simplexml_load_string($sheetXml);
    $excelRows = [];
    foreach ($sDoc->sheetData->row as $row) {
        $rIndex = (int)$row['r'];
        $rowData = [];
        foreach ($row->c as $c) {
            $cellRef = (string)$c['r'];
            $colLetter = preg_replace('/[0-9]/', '', $cellRef);
            $type = (string)$c['t'];
            $val = isset($c->v) ? (string)$c->v : '';

            if ($type === 's' && $val !== '' && isset($sharedStrings[(int)$val])) {
                $val = $sharedStrings[(int)$val];
            }
            $rowData[$colLetter] = trim($val);
        }
        $excelRows[$rIndex] = $rowData;
    }

    // Helper date converter
    $convertExcelDate = function ($val) {
        if (empty($val) || $val === '0' || $val === '-' || $val === 'N/A') return null;
        if (is_numeric($val) && (float)$val > 1000) {
            try {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays((int)$val)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    };

    // Load existing clients indexed by lower/trimmed client_name
    $dbClients = \App\Models\Client::all();
    $clientMap = [];
    $normMap = [];

    $normalize = function ($str) {
        $str = mb_strtolower(trim($str));
        $str = str_replace(['m/s.', 'm/s', 'ltd.', 'ltd', 'limited', 'inc', 'co'], '', $str);
        return preg_replace('/[^a-z0-9]/', '', $str);
    };

    foreach ($dbClients as $c) {
        $key = mb_strtolower(trim($c->client_name));
        $clientMap[$key] = $c;
        $normKey = $normalize($c->client_name);
        if (!empty($normKey) && !isset($normMap[$normKey])) {
            $normMap[$normKey] = $c;
        }
    }

    $stats = [
        'total_excel_rows' => 0,
        'matched_clients' => 0,
        'unmatched_clients' => 0,
        'updated_records' => 0,
        'unmatched_list' => [],
        'sample_updates' => [],
    ];

    // Data rows start from row 4
    foreach ($excelRows as $rIndex => $row) {
        if ($rIndex < 4) continue;

        $rawClientName = $row['A'] ?? '';
        if (empty($rawClientName)) continue;

        $stats['total_excel_rows']++;

        $cleanKey = mb_strtolower(trim($rawClientName));
        $client = $clientMap[$cleanKey] ?? null;

        if (!$client) {
            $normKey = $normalize($rawClientName);
            if (!empty($normKey) && isset($normMap[$normKey])) {
                $client = $normMap[$normKey];
            }
        }

        if (!$client) {
            // Try fuzzy matching without special characters
            $simplified = preg_replace('/[^a-z0-9]/', '', $cleanKey);
            foreach ($clientMap as $k => $cObj) {
                if (preg_replace('/[^a-z0-9]/', '', $k) === $simplified) {
                    $client = $cObj;
                    break;
                }
            }
        }

        if (!$client) {
            $stats['unmatched_clients']++;
            if (count($stats['unmatched_list']) < 30) {
                $stats['unmatched_list'][] = $rawClientName;
            }
            continue;
        }


        $stats['matched_clients']++;

        // 1. Security Coverage
        $secCoverage = $row['B'] ?? '';

        // 2. BTRC Letter
        $btrcLetter = $row['C'] ?? '';

        // 3. Other Upstream
        $rawUpstream = $row['D'] ?? '';
        $hasOtherUpstream = false;
        if (!empty($rawUpstream)) {
            if (stripos($rawUpstream, 'Yes') !== false || $rawUpstream === '1' || stripos($rawUpstream, 'Other') !== false) {
                $hasOtherUpstream = true;
            }
        }

        // 4. BTRC License Expire Date (Col N)
        $btrcExpireDate = $convertExcelDate($row['N'] ?? null);

        // 5. Agreements JSON mapped directly to agreement_status
        $agreements = [];

        // NTTN Agreement (Col E, F, G, H)
        $hasNttn = ($row['E'] ?? '') === 'Yes' || !empty($row['F'] ?? '') || !empty($row['G'] ?? '') || !empty($row['H'] ?? '');
        if ($hasNttn) {
            $nttnStatus = $row['F'] ?? '';
            $nttnSigning = $convertExcelDate($row['G'] ?? null);
            $nttnExpiry = $convertExcelDate($row['H'] ?? null);
            $agreements[] = [
                'license_name' => 'NTTN',
                'signing_date' => $nttnSigning,
                'expiry_date' => $nttnExpiry,
                'status' => $nttnStatus,
            ];
        }

        // IIG/ITC Agreement (Col I, J, K, L)
        $hasIig = ($row['I'] ?? '') === 'Yes' || !empty($row['J'] ?? '') || !empty($row['K'] ?? '') || !empty($row['L'] ?? '');
        if ($hasIig) {
            $iigStatus = $row['J'] ?? '';
            $iigSigning = $convertExcelDate($row['K'] ?? null);
            $iigExpiry = $convertExcelDate($row['L'] ?? null);
            $agreements[] = [
                'license_name' => 'IIG/ITC',
                'signing_date' => $iigSigning,
                'expiry_date' => $iigExpiry,
                'status' => $iigStatus,
            ];
        }

        // Apply changes directly to agreement_status as JSON
        $client->security_coverage = $secCoverage;
        $client->btrc_letter = $btrcLetter;
        $client->other_upstream = $hasOtherUpstream;
        $client->btrc_license_discontinuation_date = $btrcExpireDate;
        $client->agreement_status = $agreements;

        if ($execute) {
            $client->save();
        }

        $stats['updated_records']++;

        if (count($stats['sample_updates']) < 5) {
            $stats['sample_updates'][] = [
                'client_id' => $client->client_id,
                'client_name' => $client->client_name,
                'security_coverage' => $secCoverage,
                'btrc_letter' => $btrcLetter,
                'other_upstream' => $hasOtherUpstream,
                'btrc_license_discontinuation_date' => $btrcExpireDate,
                'agreement_status' => $agreements,
            ];
        }
    }

    return response()->json([
        'success' => true,
        'mode' => $execute ? 'EXECUTE' : 'DRY_RUN',
        'stats' => $stats
    ]);
});




























