@extends('components.main-layout')
@section('title', 'Edit Indicator')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Indicator</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicators.index') }}">Indicators</a></li>
                <li class="breadcrumb-item active">{{ $indicator->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicators.update', $indicator) }}" method="POST">
        @csrf
        @method('PUT')
        @include('indicators.form', [
            'indicator' => $indicator,
            'statusOptions' => $statusOptions,
            'thematicAreas' => $thematicAreas,
            'measurementTypes' => $measurementTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('indicators.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
