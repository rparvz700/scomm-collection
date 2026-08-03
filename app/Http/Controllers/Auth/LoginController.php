<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemAccessLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['is_active'] = true;

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->flash('just_logged_in', true);

            $user = Auth::user();
            SystemAccessLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => 'login',
                'status' => 'success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            // Role landing page check
            $role = $user->roles()->first();
            if ($role && $role->landing_page) {
                if (\Route::has($role->landing_page)) {
                    return redirect()->intended(route($role->landing_page));
                }
            }

            // Fallback checks depending on permissions
            if ($user->can('view dashboard')) {
                return redirect()->intended(route('dashboard.optimized'));
            } elseif ($user->can('view collections')) {
                return redirect()->intended(route('collection-entry.index'));
            } elseif ($user->can('view monthly summaries')) {
                return redirect()->intended(route('monthly-summary.index'));
            } else {
                return redirect()->intended(route('settings.index'));
            }
        }

        SystemAccessLog::create([
            'user_id' => null,
            'email' => $request->input('email', 'unknown'),
            'event' => 'login_failed',
            'status' => 'failed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            SystemAccessLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => 'logout',
                'status' => 'success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
