<?php

namespace App\Http\Controllers;

use App\Models\ClientLog;
use App\Models\CollectionLog;
use App\Models\ManagementGuidanceLog;
use App\Models\SummaryAuditLog;
use App\Models\SystemAccessLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function collectionLogs(Request $request): View
    {
        $user = auth()->user();

        // Ensure 'view collection logs' permission exists and is granted to collection_hod & admin
        try {
            $perm = \App\Models\Permission::firstOrCreate(['name' => 'view collection logs', 'guard_name' => 'web']);
            $hodRole = \App\Models\Role::where('name', 'collection_hod')->first();
            if ($hodRole && !$hodRole->hasPermissionTo('view collection logs')) {
                $hodRole->givePermissionTo($perm);
            }
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if ($adminRole && !$adminRole->hasPermissionTo('view collection logs')) {
                $adminRole->givePermissionTo($perm);
            }
        } catch (\Throwable $e) {
            // Ignore if DB mismatch
        }

        if (!$user) {
            abort(403);
        }

        // Restrict collection_kam
        if ($user->hasRole('collection_kam') && !$user->hasAnyRole(['collection_hod', 'admin', 'mgt', 'manager', 'Super Admin'])) {
            abort(403, 'Unauthorized access to Collection Change Log.');
        }

        $isAuthorized = $user->can('view collection logs') || 
                        $user->hasAnyRole(['collection_hod', 'admin', 'mgt', 'manager', 'Super Admin', 'collection_supervisor']);

        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to Collection Change Log.');
        }

        $search = trim($request->input('search', ''));
        $action = trim($request->input('action', ''));

        $query = CollectionLog::query()->with('client')->latest('id');

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('field_name', 'like', "%{$search}%")
                  ->orWhere('old_value', 'like', "%{$search}%")
                  ->orWhere('new_value', 'like', "%{$search}%")
                  ->orWhere('performed_by', 'like', "%{$search}%")
                  ->orWhere('collection_id', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  });
                if (stripos('untraced collection', $search) !== false) {
                    $q->orWhereNull('client_id');
                }
            });
        }

        return view('logs.collection_logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
            'action' => $action,
        ]);
    }
    public function clientLogs(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $query = ClientLog::query()->with('client')->latest('client_log_id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('field_name', 'like', "%{$search}%")
                  ->orWhere('old_value', 'like', "%{$search}%")
                  ->orWhere('new_value', 'like', "%{$search}%")
                  ->orWhere('updated_by', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  });
            });
        }

        return view('logs.client_logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $query = SummaryAuditLog::query()->with('client')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('field_name', 'like', "%{$search}%")
                  ->orWhere('old_value', 'like', "%{$search}%")
                  ->orWhere('new_value', 'like', "%{$search}%")
                  ->orWhere('updated_by', 'like', "%{$search}%")
                  ->orWhere('summary_type', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  });
            });
        }

        return view('logs.audit_logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function guidanceLogs(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $query = ManagementGuidanceLog::query()->with(['client', 'user'])->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('guidance_text', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('client_name', 'like', "%{$search}%")
                         ->orWhere('opus_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return view('logs.guidance_logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function systemAccessLogs(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $query = SystemAccessLog::query()->with('user')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return view('logs.system_access_logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }
}
