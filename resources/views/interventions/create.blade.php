@extends('components.main-layout')
@section('title', 'New Intervention')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Intervention</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('interventions.index') }}">Interventions</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('interventions.store') }}" method="POST">
        @csrf
        @include('interventions.form', ['statusOptions' => $statusOptions, 'thematicAreas' => $thematicAreas, 'indicators' => $indicators])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Intervention</button>
            <a href="{{ route('interventions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
