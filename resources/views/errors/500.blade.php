@extends('errors.layout', ['variant' => 'danger'])

@section('title', '500 - Server Error')
@section('icon', 'mdi-alert-octagon-outline')
@section('code', '500')
@section('title-text', 'Something went wrong')
@section('message', 'An unexpected error occurred on our end. Please try again, and contact support if the problem persists.')

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-view-dashboard-outline"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In' }}
</a>
@endsection
