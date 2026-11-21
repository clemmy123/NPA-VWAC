@extends('components.main-layout')
@section('title', __('Organization Types'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Organization Types') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Organization Types') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('organization-types.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Type') }}</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($organizationTypes as $organizationType)
            <tr>
                <td>{{ ($organizationTypes->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $organizationType->code }}</span></td>
                <td>{{ $organizationType->name }}</td>
                <td><span class="s-badge {{ $organizationType->is_active ? 's-active' : 's-inactive' }}">{{ $organizationType->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td>
<x-row-actions>
                    <x-row-action href="{{ route('organization-types.edit', $organizationType) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    <x-row-action action="{{ route('organization-types.destroy', $organizationType) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this organization type?') }}" />
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-domain"></i>
                        <p>{{ __('No organization types found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($organizationTypes->hasPages())
    <div class="px-3 py-2 border-top">{{ $organizationTypes->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($organizationTypes as $organizationType)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $organizationType->name }}</span>
            <span class="s-badge {{ $organizationType->is_active ? 's-active' : 's-inactive' }}">{{ $organizationType->is_active ? __('Active') : __('Inactive') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-pound"></i> {{ $organizationType->code }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            <x-row-action href="{{ route('organization-types.edit', $organizationType) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            <x-row-action action="{{ route('organization-types.destroy', $organizationType) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this organization type?') }}" />
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-domain"></i>
        <p>{{ __('No organization types found.') }}</p>
    </div>
    @endforelse
    @if ($organizationTypes->hasPages())
    <div class="mt-3">{{ $organizationTypes->links() }}</div>
    @endif
</div>
@endsection
