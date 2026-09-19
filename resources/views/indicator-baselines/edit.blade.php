@extends('components.main-layout')
@section('title', __('Edit Baseline'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Baseline') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-baselines.index') }}">Baselines</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-baselines.update', $baseline) }}" method="POST">
        @csrf
        @method('PUT')
        @include('indicator-baselines.form', ['baseline' => $baseline, 'indicators' => $indicators, 'financialYears' => $financialYears, 'organizations' => $organizations])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('indicator-baselines.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
