@extends('components.main-layout')
@section('title', 'New Assignment')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Assignment</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-assignments.index') }}">Data Assignments</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-data-assignments.store') }}" method="POST">
        @csrf
        @include('indicator-data-assignments.form', [
            'indicators' => $indicators, 'users' => $users, 'organizations' => $organizations,
            'locationLevels' => $locationLevels,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <a href="{{ route('indicator-data-assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
