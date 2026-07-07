<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SCOMM Collection')</title>
    <link rel="icon" type="image/png" href="{{ asset('image/brand_badge.png') }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --panel: #ffffff;
            --ink: #162033;
            --muted: #64748b;
            --line: #dbe3ef;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --accent: #0369a1;
            --danger: #b42318;
            --success-bg: #dcfce7;
            --success: #166534;
            --shadow: 0 18px 44px rgba(15, 23, 42, .08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 1px solid var(--line);
            padding: 14px clamp(18px, 4vw, 44px);
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 230px;
            color: inherit;
            text-decoration: none;
        }

        .brand-badge {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font-weight: 900;
        }

        .brand-title {
            margin: 0;
            font-size: 16px;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }

        .nav a {
            min-height: 40px;
            border-radius: 8px;
            padding: 10px 13px;
            color: #334155;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav a:hover,
        .nav a.active {
            background: #e6f4f2;
            color: var(--primary-dark);
        }

        .logout-form { margin: 0; }

        .logout-button,
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 0 14px;
            color: var(--ink);
            background: #ffffff;
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .button.primary {
            border-color: var(--primary);
            background: var(--primary);
            color: #ffffff;
        }

        .page {
            width: min(1440px, 100%);
            margin: 0 auto;
            padding: 30px clamp(18px, 4vw, 44px) 44px;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1.06;
        }

        .page-heading p {
            margin: 8px 0 0;
            max-width: 680px;
            color: var(--muted);
            line-height: 1.6;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .grid.two { grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); }
        .grid.four { grid-template-columns: repeat(4, minmax(0, 1fr)); }

        .panel,
        .metric {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .panel { overflow: hidden; }

        .panel-body { padding: 20px; }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--line);
            padding: 18px 20px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 17px;
        }

        .panel-header span,
        .muted {
            color: var(--muted);
            font-size: 13px;
        }

        .metric {
            padding: 20px;
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .metric strong {
            display: block;
            margin-top: 10px;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 20px;
            border-bottom: 1px solid #edf1f7;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        tr:last-child td { border-bottom: 0; }

        .pill {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border-radius: 999px;
            padding: 0 10px;
            background: #e0f2fe;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
        }

        .pill.high {
            background: #fee2e2;
            color: var(--danger);
        }

        .amount {
            font-weight: 800;
            white-space: nowrap;
        }

        .empty {
            padding: 24px 20px;
            color: var(--muted);
        }

        .alert {
            margin-bottom: 18px;
            border-radius: 8px;
            padding: 13px 16px;
            background: var(--success-bg);
            color: var(--success);
            font-weight: 800;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 12px;
            color: var(--ink);
            background: #ffffff;
            font: inherit;
            outline: none;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        .field { margin-bottom: 16px; }
        .error { margin-top: 7px; color: var(--danger); font-size: 13px; }

        @media (max-width: 1100px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav {
                justify-content: flex-start;
                width: 100%;
                overflow-x: auto;
            }

            .grid.two,
            .grid.four {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .panel { overflow-x: auto; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @if (session('just_logged_in'))
        <!-- Video Loader -->
        <style>
            @keyframes loader-spin {
                to { transform: rotate(360deg); }
            }
            @keyframes loader-pulse {
                0%, 100% { opacity: 0.6; transform: scale(0.98); }
                50% { opacity: 1; transform: scale(1.02); }
            }
        </style>
        <div id="video-loader" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0f172a; z-index: 99999; display: flex; align-items: center; justify-content: center; transition: opacity 0.8s ease-in-out;">
            <video id="loader-video" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover;">
                <source src="{{ asset('image/login_bg2.mp4') }}" type="video/mp4">
            </video>
            
            <!-- Animated Loading Text Overlay -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: 20px; z-index: 100000; pointer-events: none;">
                <div style="width: 52px; height: 52px; border: 5px solid rgba(255, 255, 255, 0.15); border-top-color: #10b981; border-radius: 50%; animation: loader-spin 0.8s linear infinite;"></div>
                <div style="color: #ffffff; font-family: Inter, system-ui, sans-serif; font-size: 22px; font-weight: 900; letter-spacing: 6px; text-transform: uppercase; text-shadow: 0 4px 8px rgba(0,0,0,0.6); animation: loader-pulse 1.5s ease-in-out infinite;">Loading...</div>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const loader = document.getElementById('video-loader');
                const video = document.getElementById('loader-video');
                
                // Once the video finishes playing, fade out and remove
                video.addEventListener('ended', fadeOutLoader);
                
                // Keep the loader on screen for at most 2.2 seconds to prevent long delay
                setTimeout(fadeOutLoader, 2200);
                
                function fadeOutLoader() {
                    if (loader && loader.style.opacity !== '0') {
                        loader.style.opacity = '0';
                        setTimeout(() => {
                            loader.remove();
                        }, 800); // match transition duration
                    }
                }
            });
        </script>
    @endif

    <header class="topbar">
        <a class="brand" href="{{ route('dashboard.optimized') }}">
            <div class="brand-badge" style="background:none;"><img src="{{ asset('image/brand_badge.png') }}" alt="Logo" style="width:100%; height:100%; object-fit:contain; border-radius:8px;"></div>
            <div>
                <h1 class="brand-title">SCOMM Collection</h1>
                <p class="brand-subtitle">{{ auth()->user()->name ?? auth()->user()->email }}</p>
            </div>
        </a>

        <nav class="nav" aria-label="Primary navigation">
            @can('view dashboard')
                <a class="{{ request()->routeIs('dashboard.optimized') ? 'active' : '' }}" href="{{ route('dashboard.optimized') }}">Dashboard</a>
            @endcan

            @can('view collections')
                <a class="{{ request()->routeIs('collection-entry.*') ? 'active' : '' }}" href="{{ route('collection-entry.index') }}">Collection Entry</a>
            @endcan

            @can('view monthly summaries')
                <a class="{{ request()->routeIs('monthly-summary.*') ? 'active' : '' }}" href="{{ route('monthly-summary.index') }}">Monthly Summary</a>
                <a class="{{ request()->routeIs('monthly-summary-discontinued.*') ? 'active' : '' }}" href="{{ route('monthly-summary-discontinued.index') }}">Discontinued Summary</a>
            @endcan

            @can('view clients')
                <a class="{{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a>
            @endcan

            @can('manage roles')
                <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">Settings</a>
            @endcan

            <a class="{{ request()->routeIs('reports.builder') ? 'active' : '' }}" href="{{ route('reports.builder') }}" style="background: rgba(30, 41, 59, 0.05);">📊 Report Builder</a>
        </nav>

        <form class="logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-button" type="submit">Logout</button>
        </form>
    </header>

    <main class="page">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
