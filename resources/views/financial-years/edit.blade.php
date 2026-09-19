@extends('components.main-layout')
@section('title', __('Edit Financial Year'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Financial Year') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('financial-years.index') }}">{{ __('Financial Years') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
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
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('financial-years.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
