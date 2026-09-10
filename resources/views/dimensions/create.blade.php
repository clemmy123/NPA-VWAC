@extends('components.main-layout')
@section('title', 'New Dimension')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Dimension</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.index') }}">Dimensions</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('dimensions.store') }}" method="POST">
        @csrf
        @include('dimensions.form')

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <a href="{{ route('dimensions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
