@extends('components.main-layout')
@section('title', __('Edit Intervention'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Intervention') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('interventions.index') }}">{{ __('Interventions') }}</a></li>
                <li class="breadcrumb-item active">{{ $intervention->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('interventions.update', $intervention) }}" method="POST">
        @csrf
        @method('PUT')
        @include('interventions.form', ['intervention' => $intervention, 'statusOptions' => $statusOptions, 'thematicAreas' => $thematicAreas, 'indicators' => $indicators])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('interventions.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
