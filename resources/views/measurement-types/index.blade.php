@extends('components.main-layout')
@section('title', __('Measurement Types'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Measurement Types') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Measurement Types') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('measurement-types.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Type') }}</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($measurementTypes as $measurementType)
            <tr>
                <td>{{ ($measurementTypes->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $measurementType->code }}</span></td>
                <td>{{ $measurementType->name }}</td>
                <td><span class="s-badge {{ $measurementType->is_active ? 's-active' : 's-inactive' }}">{{ $measurementType->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td>
<x-row-actions>
                    <x-row-action href="{{ route('measurement-types.edit', $measurementType) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    <x-row-action action="{{ route('measurement-types.destroy', $measurementType) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this measurement type?') }}" />
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-ruler"></i>
                        <p>{{ __('No measurement types found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($measurementTypes->hasPages())
    <div class="px-3 py-2 border-top">{{ $measurementTypes->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($measurementTypes as $measurementType)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $measurementType->name }}</span>
            <span class="s-badge {{ $measurementType->is_active ? 's-active' : 's-inactive' }}">{{ $measurementType->is_active ? __('Active') : __('Inactive') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-pound"></i> {{ $measurementType->code }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            <x-row-action href="{{ route('measurement-types.edit', $measurementType) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            <x-row-action action="{{ route('measurement-types.destroy', $measurementType) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this measurement type?') }}" />
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-ruler"></i>
        <p>{{ __('No measurement types found.') }}</p>
    </div>
    @endforelse
    @if ($measurementTypes->hasPages())
    <div class="mt-3">{{ $measurementTypes->links() }}</div>
    @endif
</div>
@endsection
