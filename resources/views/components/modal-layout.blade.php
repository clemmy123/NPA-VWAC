<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | NPA-VWAC</title>
    <link href="{{ asset('app-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('app-assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('app-assets/select2/css/select2.min.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
    <main class="p-3">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
    <script src="{{ asset('app-assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('app-assets/bootstrap-5.0.2/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2Init.js') }}"></script>
    @stack('scripts')
</body>
</html>
