@extends('errors.layout', ['variant' => 'warning'])

@section('title', '401 - Sign-in Required')
@section('icon', 'mdi-account-lock-outline')
@section('code', '401')
@section('title-text', 'Sign-in required')
@section('message', 'You need to sign in to view this page.')

@section('actions')
<a href="{{ route('login') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-login"></i> Sign In
</a>
@endsection
