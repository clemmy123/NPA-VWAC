@extends('components.main-layout')
@section('title', __('Edit Thematic Area'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Thematic Area') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('thematic-areas.index') }}">{{ __('Thematic Areas') }}</a></li>
                <li class="breadcrumb-item active">{{ $thematicArea->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('thematic-areas.update', $thematicArea) }}" method="POST">
        @csrf
        @method('PUT')
        @include('thematic-areas.form', ['thematicArea' => $thematicArea, 'statusOptions' => $statusOptions, 'projects' => $projects])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('thematic-areas.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
