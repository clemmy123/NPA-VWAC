@extends('components.main-layout')
@section('title', __('Edit Organization Type'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Organization Type') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('organization-types.index') }}">{{ __('Organization Types') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('organization-types.update', $organizationType) }}" method="POST">
        @csrf
        @method('PUT')
        @include('organization-types.form', ['organizationType' => $organizationType])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('organization-types.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
