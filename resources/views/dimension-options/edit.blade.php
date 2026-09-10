@extends('components.main-layout')
@section('title', 'Edit Option')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Option — {{ $dimension->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.index') }}">Dimensions</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.edit', $dimension) }}">{{ $dimension->name }}</a></li>
                <li class="breadcrumb-item active">Edit Option</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('dimensions.options.update', [$dimension, $dimensionOption]) }}" method="POST">
        @csrf
        @method('PUT')
        @include('dimension-options.form', ['dimensionOption' => $dimensionOption])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('dimensions.edit', $dimension) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
