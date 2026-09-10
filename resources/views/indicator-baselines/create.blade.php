@extends('components.main-layout')
@section('title', 'New Baseline')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Baseline</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-baselines.index') }}">Baselines</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-baselines.store') }}" method="POST">
        @csrf
        @include('indicator-baselines.form', ['indicators' => $indicators, 'financialYears' => $financialYears])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Baseline</button>
            <a href="{{ route('indicator-baselines.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
