@extends('components.main-layout')
@section('title', 'New Target')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Target</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-targets.index') }}">Targets</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-targets.store') }}" method="POST">
        @csrf
        @include('indicator-targets.form', [
            'indicators' => $indicators,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'dimensionOptions' => $dimensionOptions,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Target</button>
            <a href="{{ route('indicator-targets.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
