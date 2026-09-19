@extends('components.main-layout')
@section('title', __('New Measurement Type'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Measurement Type') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('measurement-types.index') }}">{{ __('Measurement Types') }}</a></li>
                <li class="breadcrumb-item active">{{ __('New') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('measurement-types.store') }}" method="POST">
        @csrf
        @include('measurement-types.form')

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save') }}</button>
            <a href="{{ route('measurement-types.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
