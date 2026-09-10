@extends('components.main-layout')
@section('title', 'New Reporting Period')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">New Reporting Period</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reporting-periods.index') }}">Reporting Periods</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('reporting-periods.store') }}" method="POST">
        @csrf
        @include('reporting-periods.form', ['financialYears' => $financialYears, 'periodTypes' => $periodTypes])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <a href="{{ route('reporting-periods.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
