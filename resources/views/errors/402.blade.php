@extends('errors.layout', ['variant' => 'warning'])

@section('title', '402 - Access Restricted')
@section('icon', 'mdi-credit-card-off-outline')
@section('code', '402')
@section('title-text', 'Access restricted')
@section('message', 'This part of the module is not available for your account. Contact your module administrator for access.')

@section('actions')
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-view-dashboard-outline"></i> {{ auth()->check() ? 'Back to Dashboard' : 'Sign In' }}
</a>
@endsection
