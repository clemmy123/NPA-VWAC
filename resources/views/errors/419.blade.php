@extends('errors.layout', ['variant' => 'warning'])

@section('title', '419 - Session Expired')
@section('icon', 'mdi-timer-sand-empty')
@section('code', '419')
@section('title-text', 'Session expired')
@section('message', 'Your session has expired, most likely because this page was open for too long. Please go back and try again.')

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-refresh"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In Again' }}
</a>
@endsection
