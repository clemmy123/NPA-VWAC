@extends('components.main-layout')
@section('title', __('Organizations'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Organizations') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Organizations') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('organizations.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Organization') }}</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Code') }}</th><th>{{ __('Location') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($organizations as $organization)
            <tr>
                <td>{{ ($organizations->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $organization->name }}</td>
                <td>{{ $organization->organizationType?->name ?? '—' }}</td>
                <td>{{ $organization->code ?? '—' }}</td>
                <td>{{ $organization->locationName() ?? '—' }}</td>
                <td><span class="s-badge {{ $organization->is_active ? 's-active' : 's-inactive' }}">{{ $organization->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td>
                    <a href="{{ route('organizations.edit', $organization) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('organizations.destroy', $organization) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this organization?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-bank-outline"></i>
                        <p>{{ __('No organizations found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($organizations->hasPages())
    <div class="px-3 py-2 border-top">{{ $organizations->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($organizations as $organization)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $organization->name }}</span>
            <span class="s-badge {{ $organization->is_active ? 's-active' : 's-inactive' }}">{{ $organization->is_active ? __('Active') : __('Inactive') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-shape-outline"></i> {{ $organization->organizationType?->name ?? '—' }}</span>
            <span><i class="mdi mdi-map-marker-outline"></i> {{ $organization->locationName() ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('organizations.edit', $organization) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('organizations.destroy', $organization) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('Delete this organization?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-bank-outline"></i>
        <p>{{ __('No organizations found.') }}</p>
    </div>
    @endforelse
    @if ($organizations->hasPages())
    <div class="mt-3">{{ $organizations->links() }}</div>
    @endif
</div>
@endsection
