@extends('components.main-layout')
@section('title', 'New Data Entry')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Data Entry</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">Data Entries</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-data-entries.store') }}" method="POST">
        @csrf
        @include('indicator-data-entries.form', [
            'indicators' => $indicators,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'organizations' => $organizations,
            'dataSources' => $dataSources,
            'locationLevels' => $locationLevels,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save as Draft</button>
            <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
