@extends('components.main-layout')
@section('title', __('Edit Unit of Measure'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Unit of Measure') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('units-of-measure.index') }}">{{ __('Units of Measure') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('units-of-measure.update', $unitOfMeasure) }}" method="POST">
        @csrf
        @method('PUT')
        @include('units-of-measure.form', ['unitOfMeasure' => $unitOfMeasure])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('units-of-measure.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
