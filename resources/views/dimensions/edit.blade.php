@extends('components.main-layout')
@section('title', __('Edit Dimension'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Dimension') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dimensions.index') }}">{{ __('Dimensions') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card mb-4">
    <form action="{{ route('dimensions.update', $dimension) }}" method="POST">
        @csrf
        @method('PUT')
        @include('dimensions.form', ['dimension' => $dimension])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            <a href="{{ route('dimensions.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ __('Options') }}</h5>
        <a href="{{ route('dimensions.options.create', $dimension) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> {{ __('Add Option') }}</a>
    </div>

    <table class="table table-sm mb-0">
        <thead>
            <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Sort') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($options as $option)
            <tr>
                <td><span class="ref-pill">{{ $option->code }}</span></td>
                <td>{{ $option->name }}</td>
                <td>{{ $option->sort_order }}</td>
                <td><span class="s-badge {{ $option->is_active ? 's-active' : 's-inactive' }}">{{ $option->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td class="text-end">
                    <a href="{{ route('dimensions.options.edit', [$dimension, $option]) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('dimensions.options.destroy', [$dimension, $option]) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this option?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-format-list-bulleted"></i>
                        <p>{{ __('No options yet for this dimension.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
