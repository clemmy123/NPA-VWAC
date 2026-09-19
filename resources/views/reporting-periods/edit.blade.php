@extends('components.main-layout')
@section('title', __('Edit Reporting Period'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Reporting Period') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reporting-periods.index') }}">{{ __('Reporting Periods') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('reporting-periods.update', $reportingPeriod) }}" method="POST">
        @csrf
        @method('PUT')
        @include('reporting-periods.form', ['reportingPeriod' => $reportingPeriod, 'financialYears' => $financialYears, 'periodTypes' => $periodTypes])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('reporting-periods.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
