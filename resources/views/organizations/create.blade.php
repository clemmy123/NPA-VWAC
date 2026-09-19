@extends('components.main-layout')
@section('title', __('New Organization'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Organization') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('organizations.index') }}">{{ __('Organizations') }}</a></li>
                <li class="breadcrumb-item active">{{ __('New') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('organizations.store') }}" method="POST">
        @csrf
        @include('organizations.form', ['organizationTypes' => $organizationTypes])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save') }}</button>
            <a href="{{ route('organizations.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
