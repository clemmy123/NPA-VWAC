@extends('components.main-layout')
@section('title', __('New Indicator'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Indicator') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicators.index') }}">{{ __('Indicators') }}</a></li>
                <li class="breadcrumb-item active">{{ __('New') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicators.store') }}" method="POST">
        @csrf
        @include('indicators.form', [
            'statusOptions' => $statusOptions,
            'thematicAreas' => $thematicAreas,
            'measurementTypes' => $measurementTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Indicator') }}</button>
            <a href="{{ route('indicators.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
