@extends('errors.layout', ['variant' => 'danger'])

@section('title', '403 - Access Denied')
@section('icon', 'mdi-lock-alert-outline')
@section('code', '403')
@section('title-text', 'Access denied')
@section('message', "You don't have permission to view this page. Contact your module administrator if you think this is a mistake.")

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-view-dashboard-outline"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In' }}
</a>
@endsection
