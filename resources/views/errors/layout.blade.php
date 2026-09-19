<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('app-assets/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('app-assets/logo.png') }}">

    {{-- Deliberately not @vite-dependent: this page must still render correctly
         even when the asset build itself is the thing that's broken. --}}
    <link href="{{ asset('app-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('app-assets/css/icons.min.css') }}" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .status-page {
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
        .status-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 2px 8px rgba(0,0,0,.2);
            padding: 44px 36px;
            text-align: center;
        }
        .status-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
        }
        .status-icon.danger { background: #fdeceb; color: #e3342f; }
        .status-icon.warning { background: #fef6e6; color: #f7b84b; }
        .status-icon.info { background: #eef6ff; color: #188ae2; }
        .status-code {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .status-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.02em;
            margin: 0 0 10px;
        }
        .status-message {
            font-size: .875rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0 0 26px;
        }
        .status-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .status-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .status-btn-primary { background: #188ae2; color: #fff; }
        .status-btn-primary:hover { background: #1278c8; color: #fff; }
        .status-btn-outline { background: #fff; color: #475569; border-color: #e2e8f0; }
        .status-btn-outline:hover { background: #f8fafc; color: #475569; }
    </style>

    @stack('styles')
</head>
<body>
    <div class="status-page">
        <div class="status-card">
            <div class="status-icon {{ $variant ?? 'danger' }}"><i class="mdi @yield('icon', 'mdi-alert-circle-outline')"></i></div>
            @hasSection('code')<div class="status-code">@yield('code')</div>@endif
            <h1 class="status-title">@yield('title-text')</h1>
            <p class="status-message">@yield('message')</p>
            <div class="status-actions">@yield('actions')</div>
        </div>
    </div>
</body>
</html>
