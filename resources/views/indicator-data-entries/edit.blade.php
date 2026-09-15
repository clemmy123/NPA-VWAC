@extends('components.main-layout')
@section('title', 'Edit Data Collection')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Data Collection</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">Data Collections</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-data-entries.update', $entry) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('indicator-data-entries.form', [
            'entry' => $entry,
            'indicators' => $indicators,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'organizations' => $organizations,
            'locationLevels' => $locationLevels,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
