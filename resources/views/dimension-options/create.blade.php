@extends('components.main-layout')
@section('title', __('New Option'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Option') }} — {{ $dimension->name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.index') }}">{{ __('Dimensions') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.edit', $dimension) }}">{{ $dimension->name }}</a></li>
                <li class="breadcrumb-item active">{{ __('New Option') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('dimensions.options.store', $dimension) }}" method="POST">
        @csrf
        @include('dimension-options.form')

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save') }}</button>
            <a href="{{ route('dimensions.edit', $dimension) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
