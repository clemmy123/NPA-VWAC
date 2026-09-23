<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', __('Sign in')) — {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('app-assets/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('app-assets/logo.png') }}">
    <link href="{{ asset('app-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('app-assets/css/icons.min.css') }}" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ── Page background ─────────────────────────────────────────── */
        .auth-page {
            min-height: 100vh;
            background: #0b1a35;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(24,138,226,.18) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 20%, rgba(14,72,160,.22) 0%, transparent 50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        /* ── Top bar ─────────────────────────────────────────────────── */
        .auth-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: rgba(11,26,53,.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,.07);
            z-index: 100;
        }
        .auth-topbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .auth-topbar-logo img { height: 28px; }
        .auth-topbar-logo-name {
            font-size: .9rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.01em;
        }

        /* ── Center card ─────────────────────────────────────────────── */
        .auth-card {
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 2px 8px rgba(0,0,0,.2);
            margin-top: 56px;
            animation: cardIn .55s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(28px) scale(.98); }
            to   { opacity: 1; transform: none; }
        }

        /* ── Left — slideshow panel ──────────────────────────────────── */
        .auth-slides {
            flex: 0 0 52%;
            position: relative;
            overflow: hidden;
            min-height: 520px;
            background: #f0f4f8;
        }
        .auth-slide {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transition: opacity .9s ease;
            pointer-events: none;
        }
        .auth-slide.active { opacity: 1; pointer-events: auto; }

        .slide-img-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 32px 20px;
            position: relative;
        }
        .auth-slide:nth-child(1) .slide-img-area { background: #eef6ff; }
        .auth-slide:nth-child(2) .slide-img-area { background: #f0faf4; }

        .slide-img-area img {
            max-width: 76%;
            max-height: 240px;
            object-fit: contain;
            filter: drop-shadow(0 8px 24px rgba(0,0,0,.1));
            animation: imgFloat 4s ease-in-out infinite;
        }
        @keyframes imgFloat {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }
        .auth-slide:nth-child(2) .slide-img-area img { animation-delay: -2s; }

        .slide-caption { padding: 22px 32px 48px; position: relative; }
        .auth-slide:nth-child(1) .slide-caption {
            background: linear-gradient(135deg, #0a2558 0%, #1565c0 100%);
        }
        .auth-slide:nth-child(2) .slide-caption {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
        }

        .slide-tag {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 999px;
            padding: 3px 10px;
            font-size: .68rem; font-weight: 700;
            color: rgba(255,255,255,.9);
            text-transform: uppercase; letter-spacing: .07em;
            margin-bottom: 10px;
        }
        .slide-headline {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
            letter-spacing: -.02em;
            margin-bottom: 6px;
        }
        .slide-body {
            font-size: .8rem;
            color: rgba(255,255,255,.72);
            line-height: 1.6;
        }

        .auth-slides > .slide-dots {
            position: absolute;
            bottom: 18px; right: 24px;
            display: flex; gap: 6px;
            z-index: 10;
        }
        .slide-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,.35);
            transition: all .35s;
            cursor: pointer;
            border: none; padding: 0;
        }
        .slide-dot.active { background: #fff; width: 20px; border-radius: 3px; }

        .auth-slides > .slide-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            background: rgba(255,255,255,.2);
            width: 100%;
            overflow: hidden;
            z-index: 10;
        }
        .slide-progress-bar {
            height: 100%;
            background: rgba(255,255,255,.65);
            width: 0%;
            transition: width linear;
        }

        /* ── Right — form panel ──────────────────────────────────────── */
        .auth-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 40px;
            overflow-y: auto;
            animation: formIn .65s cubic-bezier(.22,.68,0,1.1) .12s both;
        }
        @keyframes formIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: none; }
        }

        .auth-brand-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .auth-brand-row img { height: 36px; }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 5px;
            letter-spacing: -.02em;
        }
        .auth-subtitle {
            font-size: .845rem;
            color: #64748b;
            margin-bottom: 28px;
            margin-top: 2px;
            line-height: 1.5;
        }

        .auth-group { margin-bottom: 18px; }
        .auth-label {
            font-size: .8rem; font-weight: 600;
            color: #374151; margin-bottom: 6px; display: block;
        }
        .auth-input {
            width: 100%; padding: 11px 14px;
            font-size: .875rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            color: #111827;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }
        .auth-input:focus {
            border-color: #188ae2;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(24,138,226,.12);
        }
        .auth-input.is-invalid { border-color: #ef4444; }
        .auth-input::placeholder { color: #9ca3af; }

        .invalid-feedback { font-size: .76rem; color: #ef4444; margin-top: 4px; display: block; }

        .auth-input-wrap { position: relative; }
        .auth-input-wrap .auth-input { padding-right: 44px; }
        .auth-eye {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer; color: #9ca3af; font-size: 1.1rem;
            transition: color .15s; user-select: none;
        }
        .auth-eye:hover { color: #188ae2; }

        .auth-check {
            display: flex; align-items: center; gap: 8px;
            font-size: .82rem; color: #4b5563; cursor: pointer;
        }
        .auth-check input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #188ae2; cursor: pointer; flex-shrink: 0;
        }

        .auth-btn {
            width: 100%; padding: 12px 20px;
            font-size: .9rem; font-weight: 700;
            border: none; border-radius: 8px;
            background: #188ae2;
            color: #fff; cursor: pointer;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 4px 14px rgba(24,138,226,.3);
            letter-spacing: .01em;
            margin-top: 4px;
        }
        .auth-btn:hover {
            background: #1278c8;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(24,138,226,.35);
        }
        .auth-btn:active { transform: none; }

        .auth-link { color: #188ae2; font-size: .8rem; font-weight: 600; text-decoration: none; }
        .auth-link:hover { color: #1278c8; text-decoration: underline; }

        .auth-alert { border-radius: 8px; padding: 11px 14px; font-size: .82rem; margin-bottom: 20px; }
        .auth-alert-danger  { background: #fef2f2; color: #b91c1c; border-left: 3px solid #ef4444; }
        .auth-alert-success { background: #f0fdf4; color: #15803d; border-left: 3px solid #22c55e; }
        .auth-alert ul { margin: 0; padding-left: 16px; }
        .auth-alert ul li { margin-bottom: 2px; }

        .auth-form-footer { margin-top: 28px; font-size: .72rem; color: #9ca3af; text-align: center; }

        @media (max-width: 768px) {
            .auth-slides { display: none; }
            .auth-form-panel { padding: 36px 28px; }
            .auth-card { margin-top: 0; border-radius: 12px; }
        }
        @media (max-width: 480px) {
            .auth-form-panel { padding: 28px 20px; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="auth-page">

    {{-- Top bar --}}
    <div class="auth-topbar">
        <div class="auth-topbar-logo">
            <img src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            <span class="auth-topbar-logo-name">{{ config('app.name') }}</span>
        </div>
        @include('components.language-switcher')
    </div>

    {{-- Main card --}}
    <div class="auth-card">

        {{-- ── Left: slideshow ── --}}
        <div class="auth-slides" id="authSlides">

            {{-- Slide 1 — Jamii Agenda 2050 --}}
            <div class="auth-slide active">
                <div class="slide-img-area">
                    <img src="{{ asset('app-assets/images/jamii-logo.png') }}" alt="Jamii Ajenda-Dira 2050">
                </div>
                <div class="slide-caption">
                    <div class="slide-tag"><i class="mdi mdi-circle" style="font-size:.45rem;"></i> {{ __('National Agenda') }}</div>
                    <h2 class="slide-headline">{!! nl2br(e(__("Building a resilient nation, one family at a time"))) !!}</h2>
                    <p class="slide-body">{{ __('NPA-VAWC tracks progress on the National Plan of Action to End Violence Against Women and Children, in support of the Jamii Ajenda-Dira 2050 vision.') }}</p>
                </div>
            </div>

            {{-- Slide 2 — Coat of Arms --}}
            <div class="auth-slide">
                <div class="slide-img-area">
                    <img src="{{ asset('app-assets/images/logo-sm.png') }}" alt="Tanzania Coat of Arms">
                </div>
                <div class="slide-caption">
                    <div class="slide-tag"><i class="mdi mdi-circle" style="font-size:.45rem;"></i> {{ __('United Republic of Tanzania') }}</div>
                    <h2 class="slide-headline">{!! nl2br(e(__("Uhuru na Umoja"))) !!}</h2>
                    <p class="slide-body">{{ __('A single source of truth for indicators, targets, and reported data across every thematic area of the plan.') }}</p>
                </div>
            </div>

            <div class="slide-dots" id="slideDots">
                <button class="slide-dot active" data-index="0"></button>
                <button class="slide-dot" data-index="1"></button>
            </div>
            <div class="slide-progress">
                <div class="slide-progress-bar" id="slideProgressBar"></div>
            </div>

        </div>

        {{-- ── Right: form ── --}}
        <div class="auth-form-panel">

            <div class="auth-brand-row d-md-none">
                <img src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            </div>

            @if (session('success'))
                <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
            @endif
            @if (session('status'))
                <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="auth-alert auth-alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('form')

            <div class="auth-form-footer">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var slides   = document.querySelectorAll('#authSlides .auth-slide');
    var dots     = document.querySelectorAll('#slideDots .slide-dot');
    var bar      = document.getElementById('slideProgressBar');
    var current  = 0;
    var total    = slides.length;
    var duration = 6000;
    var timer    = null;

    function goTo(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (index + total) % total;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
        resetBar();
    }

    function resetBar() {
        if (!bar) { return; }
        bar.style.transition = 'none';
        bar.style.width = '0%';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                bar.style.transition = 'width ' + duration + 'ms linear';
                bar.style.width = '100%';
            });
        });
    }

    function startAuto() {
        timer = setInterval(function () { goTo(current + 1); }, duration);
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            clearInterval(timer);
            goTo(parseInt(this.dataset.index, 10));
            startAuto();
        });
    });

    resetBar();
    startAuto();
})();
</script>
@stack('scripts')
</body>
</html>
