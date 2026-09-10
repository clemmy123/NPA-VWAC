@extends('components.main-layout')
@section('title', 'Edit Measurement Type')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Measurement Type</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('measurement-types.index') }}">Measurement Types</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('measurement-types.update', $measurementType) }}" method="POST">
        @csrf
        @method('PUT')
        @include('measurement-types.form', ['measurementType' => $measurementType])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('measurement-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
