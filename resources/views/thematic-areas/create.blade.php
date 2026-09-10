@extends('components.main-layout')
@section('title', 'New Thematic Area')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Thematic Area</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('thematic-areas.index') }}">Thematic Areas</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('thematic-areas.store') }}" method="POST">
        @csrf
        @include('thematic-areas.form', ['statusOptions' => $statusOptions, 'projects' => $projects])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Thematic Area</button>
            <a href="{{ route('thematic-areas.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
