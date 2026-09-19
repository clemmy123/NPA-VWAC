<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dev Login — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('app-assets/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('app-assets/logo.png') }}">
    <link href="{{ asset('app-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('app-assets/css/icons.min.css') }}" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #0b1a35;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(24,138,226,.18) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 20%, rgba(14,72,160,.22) 0%, transparent 50%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 2px 8px rgba(0,0,0,.2);
            padding: 40px 32px;
        }
        .login-brand { text-align: center; margin-bottom: 24px; }
        .login-brand img { height: 44px; }
        .login-title { font-size: 1.1rem; font-weight: 700; text-align: center; margin-bottom: 4px; color: #1e293b; }
        .login-subtitle { font-size: 0.82rem; text-align: center; color: #64748b; margin-bottom: 24px; }
        .role-list { display: flex; flex-direction: column; gap: 10px; }
        .role-list a { text-align: left; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-brand">
                <img src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            <p class="login-title">Dev Login</p>
            <p class="login-subtitle">Local-only. Pick a seeded test account to sign in as — never available outside <code>APP_ENV=local</code>.</p>

            <div class="role-list">
                @foreach ($roles as $role)
                <a href="{{ route('dev-login', ['role' => $role]) }}" class="btn btn-outline-dark">{{ $role }}</a>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>
