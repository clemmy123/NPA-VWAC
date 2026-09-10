@extends('components.main-layout')
@section('title', 'New Data Source')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Data Source</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('data-sources.index') }}">Data Sources</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('data-sources.store') }}" method="POST">
        @csrf
        @include('data-sources.form', ['collectionMethods' => $collectionMethods])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <a href="{{ route('data-sources.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
