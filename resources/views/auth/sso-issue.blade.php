@php
    $variant = in_array($status ?? 0, [401, 403, 409]) ? 'danger' : 'warning';
    $icon = match ($status ?? 0) {
        503 => 'mdi-cloud-off-outline',
        401 => 'mdi-account-lock-outline',
        403 => 'mdi-account-cancel-outline',
        409 => 'mdi-account-alert-outline',
        default => 'mdi-alert-circle-outline',
    };
@endphp
@extends('errors.layout', ['variant' => $variant])

@section('title', 'Sign-in Unavailable - NPA VWAC')
@section('icon', $icon)
@section('title-text', 'Unable to sign in')
@section('message', $message)

@section('actions')
<a href="{{ \App\Services\JumuishiUrl::central('/') }}" class="status-btn status-btn-primary">
    <i class="mdi mdi-arrow-left"></i> Return to Jumuishi
</a>
@endsection
