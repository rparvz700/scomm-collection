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

            // Role landing page or fallback check
            $role = $user->roles()->first();
            $targetUrl = route('dashboard');

            if ($role && $role->landing_page && \Route::has($role->landing_page)) {
                $targetUrl = route($role->landing_page);
            } elseif ($user->can('view dashboard')) {
                $targetUrl = route('dashboard');
            } elseif ($user->can('view collections')) {
                $targetUrl = route('collection-entry.index');
            } elseif ($user->can('view monthly summaries')) {
                $targetUrl = route('monthly-summary.index');
            } else {
                $targetUrl = route('settings.index');
            }

            // Sanitize session url.intended so background AJAX/JSON/avatar calls don't hijack login landing page
            $intendedUrl = session('url.intended');
            if ($intendedUrl) {
                $path = parse_url($intendedUrl, PHP_URL_PATH) ?? '';
                if (
                    str_contains($path, 'employee-avatar-json') ||
                    str_contains($path, 'api/') ||
                    str_contains($path, '.json') ||
                    str_contains($path, 'drilldown') ||
                    str_contains($path, 'preview') ||
                    str_contains($path, 'export')
                ) {
                    session()->forget('url.intended');
                }
            }

            return redirect()->intended($targetUrl);
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
