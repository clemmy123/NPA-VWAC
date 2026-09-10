@extends('errors.layout', ['variant' => 'warning'])

@section('title', '429 - Too Many Requests')
@section('icon', 'mdi-speedometer')
@section('code', '429')
@section('title-text', 'Too many requests')
@section('message', "You've made too many requests in a short time. Please wait a moment and try again.")

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-view-dashboard-outline"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In' }}
</a>
@endsection
