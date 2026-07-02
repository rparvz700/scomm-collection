<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SCOMM Collection</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --panel: #ffffff;
            --ink: #172033;
            --muted: #687386;
            --line: #dce3ed;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --danger: #b42318;
            --shadow: 0 24px 70px rgba(20, 31, 51, .14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, .16), transparent 34rem),
                linear-gradient(135deg, #f8fbff 0%, var(--bg) 100%);
        }

        .login-shell {
            display: grid;
            min-height: 100vh;
            grid-template-columns: minmax(0, 1.05fr) minmax(380px, .95fr);
        }

        .brand-panel {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 54px;
            
            color: #ffffff;
        }

        .brand-mark {
            width: max-content;
            
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .brand-copy {
            max-width: 620px;
        }

        .brand-copy h1 {
            margin: 0 0 18px;
            font-size: clamp(38px, 5vw, 72px);
            line-height: .98;
            letter-spacing: 0;
        }

        .brand-copy p {
            margin: 0;
            max-width: 520px;
            color: rgba(255, 255, 255, .82);
            font-size: 18px;
            line-height: 1.7;
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px;
        }

        .login-card {
            width: min(100%, 430px);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 34px;
            background: rgba(255, 255, 255, .92);
            box-shadow: var(--shadow);
        }

        .login-card h2 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.2;
        }

        .login-card .hint {
            margin: 0 0 26px;
            color: var(--muted);
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .field {
            margin-bottom: 18px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px 14px;
            color: var(--ink);
            font: inherit;
            background: #ffffff;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        .error {
            margin-top: 8px;
            color: var(--danger);
            font-size: 13px;
        }

        .options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 4px 0 24px;
            color: var(--muted);
            font-size: 14px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: background .16s ease, transform .16s ease;
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 330px;
                padding: 34px;
            }
        }

        @media (max-width: 520px) {
            .login-panel {
                padding: 20px;
            }

            .login-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-label="SCOMM Collection" style="background: linear-gradient(135deg, rgba(80, 103, 116, 0.7), rgba(15, 118, 109, 0.7)), url('{{ asset('/image/login_bg.png') }}');background-size: cover;background-position: center;background-repeat: no-repeat;">
            <div class="brand-mark"></div>
            <div class="brand-copy">
                <h1>Collection control room</h1>
                <p>Track customer exposure, monthly recovery movement, payment commitments, and risk signals from one focused workspace.</p>
            </div>
        </section>

        <section class="login-panel">
            <form class="login-card" method="POST" action="{{ route('login.store') }}">
                @csrf

                <h2>Welcome back</h2>
                <p class="hint">Sign in to continue to the collection dashboard.</p>

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="options">
                    <label class="remember">
                        <input name="remember" type="checkbox" value="1">
                        Remember me
                    </label>
                </div>

                <button type="submit">Sign in</button>
            </form>
        </section>
    </main>

    <!-- Hidden Video Loader (triggers on submit) -->
    <div id="login-video-loader">
        <video id="login-loader-video" muted playsinline loop style="width: 100%; height: 100%; object-fit: cover;">
            <source src="{{ asset('image/login_bg.mp4') }}" type="video/mp4">
        </video>
        <!-- Animated Loading Text Overlay -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: 20px; z-index: 100000; pointer-events: none;">
            <div style="width: 52px; height: 52px; border: 5px solid rgba(255, 255, 255, 0.15); border-top-color: #10b981; border-radius: 50%; animation: loader-spin 0.8s linear infinite;"></div>
            <div style="color: #ffffff; font-family: Inter, system-ui, sans-serif; font-size: 22px; font-weight: 900; letter-spacing: 6px; text-transform: uppercase; text-shadow: 0 4px 8px rgba(0,0,0,0.6); animation: loader-pulse 1.5s ease-in-out infinite;">Loading...</div>
        </div>
    </div>

    <style>
        #login-video-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #0f172a;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            transform: scale(0.5);
            visibility: hidden;
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #login-video-loader.active {
            pointer-events: auto;
            opacity: 1;
            transform: scale(1);
            visibility: visible;
        }

        @keyframes loader-spin {
            to { transform: rotate(360deg); }
        }
        @keyframes loader-pulse {
            0%, 100% { opacity: 0.6; transform: scale(0.98); }
            50% { opacity: 1; transform: scale(1.02); }
        }
    </style>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const loader = document.getElementById('login-video-loader');
            const video = document.getElementById('login-loader-video');
            
            // Trigger active class for zoom-in and fade-in transition
            loader.classList.add('active');
            
            // Play the video
            video.play().catch(err => {
                // Ignore autoplay block errors
            });
        });
    </script>
</body>
</html>
