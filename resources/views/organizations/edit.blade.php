@extends('components.main-layout')
@section('title', 'Edit Organization')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Organization</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('organizations.index') }}">Organizations</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('organizations.update', $organization) }}" method="POST">
        @csrf
        @method('PUT')
        @include('organizations.form', ['organization' => $organization, 'organizationTypes' => $organizationTypes])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('organizations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
