@extends('errors.layout', ['variant' => 'info'])

@section('title', '404 - Page Not Found')
@section('icon', 'mdi-file-search-outline')
@section('code', '404')
@section('title-text', 'Page not found')
@section('message', "The page you're looking for doesn't exist or may have been moved.")

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-view-dashboard-outline"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In' }}
</a>
@endsection
