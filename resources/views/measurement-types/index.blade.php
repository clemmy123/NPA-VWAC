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
                    <a href="{{ route('measurement-types.edit', $measurementType) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('measurement-types.destroy', $measurementType) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this measurement type?') }}">
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
            <a href="{{ route('measurement-types.edit', $measurementType) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('measurement-types.destroy', $measurementType) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('Delete this measurement type?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
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
