@extends('components.main-layout')
@section('title', 'Edit Financial Year')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Financial Year</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('financial-years.index') }}">Financial Years</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('financial-years.update', $financialYear) }}" method="POST">
        @csrf
        @method('PUT')
        @include('financial-years.form', ['financialYear' => $financialYear])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('financial-years.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
