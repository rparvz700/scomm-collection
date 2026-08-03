<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Api\MonthRolloverController;

class SettingsController extends Controller
{
    public function index(): View
    {
        // Query users with their roles
        $users = User::query()->with('roles')->orderBy('name')->get();
        // Query roles with permissions
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        // Query all permissions
        $permissions = Permission::query()->orderBy('name')->get();
        
        // Find latest summary month to show in system configurations
        $latestActiveMonth = \App\Models\MonthlySummary::max('summary_month');
        $latestDiscMonth = \App\Models\MonthlySummaryDiscontinued::max('summary_month');
        $currentSystemMonth = max($latestActiveMonth, $latestDiscMonth);
        $currentSystemMonthLabel = $currentSystemMonth ? \Carbon\Carbon::parse($currentSystemMonth)->format('F Y') : 'N/A';

        return view('settings.index', [
            'userCount' => $users->count(),
            'users' => $users,
            'roles' => $roles,
            'permissionCount' => $permissions->count(),
            'permissions' => $permissions,
            'currentSystemMonthLabel' => $currentSystemMonthLabel,
            'currentSystemMonth' => $currentSystemMonth,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,
        ]);

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('settings.index')->with('success', "User '{$user->name}' created successfully.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        // If the admin is deactivating themselves, prevent it
        if ($user->id === Auth::id() && !$request->is_active) {
            return redirect()->route('settings.index')->with('error', "You cannot deactivate your own account.");
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active,
        ]);

        $roles = $request->roles ?? [];
        $user->syncRoles($roles);

        return redirect()->route('settings.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('settings.index')->with('success', "Password for user '{$user->name}' reset successfully.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('settings.index')->with('error', "You cannot delete your own account.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('settings.index')->with('success', "User '{$name}' deleted successfully.");
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => strtolower(str_replace(' ', '_', $request->name)),
            'guard_name' => 'web',
        ]);

        return redirect()->route('settings.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => strtolower(str_replace(' ', '_', $request->name)),
        ]);

        $permissions = $request->permissions ?? [];
        $role->syncPermissions($permissions);

        return redirect()->route('settings.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function deleteRole(Role $role): RedirectResponse
    {
        // Don't allow deleting the admin role
        if ($role->name === 'admin') {
            return redirect()->route('settings.index')->with('error', "The 'admin' role cannot be deleted.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('settings.index')->with('success', "Role '{$name}' deleted successfully.");
    }

    public function updateRoleLandingPage(Request $request, Role $role)
    {
        $request->validate([
            'landing_page' => 'nullable|string|max:255',
        ]);

        $role->update([
            'landing_page' => $request->landing_page,
        ]);

        return response()->json(['success' => true, 'message' => 'Landing page updated successfully.']);
    }

    public function triggerRollover(Request $request)
    {
        $rolloverController = new MonthRolloverController();
        $response = $rolloverController->rollover($request);
        $data = $response->getData(true);

        if (isset($data['error'])) {
            return redirect()->route('settings.index')->with('error', "Month rollover failed: " . $data['error']);
        }

        $active = $data['active_inserted'] ?? 0;
        $disc = $data['discontinued_inserted'] ?? 0;
        $month = $data['target_month'] ?? 'Next Month';

        return redirect()->route('settings.index')->with('success', "Month rollover completed successfully for {$month}! (Active: {$active} records, Discontinued: {$disc} records)");
    }
}
