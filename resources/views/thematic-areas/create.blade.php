@extends('components.main-layout')
@section('title', __('New Thematic Area'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Thematic Area') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('thematic-areas.index') }}">{{ __('Thematic Areas') }}</a></li>
                <li class="breadcrumb-item active">{{ __('New') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('thematic-areas.store') }}" method="POST">
        @csrf
        @include('thematic-areas.form', ['statusOptions' => $statusOptions, 'projects' => $projects])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Thematic Area') }}</button>
            <a href="{{ route('thematic-areas.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
