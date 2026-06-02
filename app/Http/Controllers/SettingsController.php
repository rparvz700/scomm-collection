<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'userCount' => User::query()->count(),
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
            'permissionCount' => Permission::query()->count(),
        ]);
    }
}
