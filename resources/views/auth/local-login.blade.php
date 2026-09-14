<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('app-assets/images/logo-sm.png') }}">
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
            max-width: 400px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 2px 8px rgba(0,0,0,.2);
            padding: 40px 32px;
        }
        .login-brand { text-align: center; margin-bottom: 24px; }
        .login-brand img { height: 44px; }
        .login-title { font-size: 1.1rem; font-weight: 700; text-align: center; margin-bottom: 4px; color: #1e293b; }
        .login-subtitle { font-size: 0.82rem; text-align: center; color: #64748b; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-brand">
                <img src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            <p class="login-title">Organization Sign-In</p>
            <p class="login-subtitle">For reporting-organization accounts (e.g. banks). Government staff should use the main Sign In instead.</p>

            @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('local-login.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1">
                    <label for="remember" class="form-check-label">Remember me</label>
                </div>
                <button type="submit" class="btn btn-dark w-100">Sign In</button>
            </form>

            <p class="text-center mt-3 mb-0" style="font-size:0.78rem;">
                <a href="{{ route('login') }}">Government staff? Sign in with Jumuishi</a>
            </p>
        </div>
    </div>
</body>
</html>
