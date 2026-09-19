@extends('components.main-layout')
@section('title', __('Dimensions'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Dimensions') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Dimensions') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dimensions.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Dimension') }}</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Options') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($dimensions as $dimension)
            <tr>
                <td>{{ ($dimensions->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $dimension->code }}</span></td>
                <td>{{ $dimension->name }}</td>
                <td>{{ $dimension->options_count }}</td>
                <td><span class="s-badge {{ $dimension->is_active ? 's-active' : 's-inactive' }}">{{ $dimension->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td>
                    <a href="{{ route('dimensions.edit', $dimension) }}" class="btn-icon" title="Edit / manage options"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('dimensions.destroy', $dimension) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this dimension and all its options?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-shape-outline"></i>
                        <p>{{ __('No dimensions found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($dimensions->hasPages())
    <div class="px-3 py-2 border-top">{{ $dimensions->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($dimensions as $dimension)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $dimension->name }}</span>
            <span class="s-badge {{ $dimension->is_active ? 's-active' : 's-inactive' }}">{{ $dimension->is_active ? __('Active') : __('Inactive') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-pound"></i> {{ $dimension->code }}</span>
            <span><i class="mdi mdi-format-list-bulleted"></i> {{ $dimension->options_count }} options</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('dimensions.edit', $dimension) }}" class="btn-icon" title="Edit / manage options"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('dimensions.destroy', $dimension) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('Delete this dimension and all its options?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-shape-outline"></i>
        <p>{{ __('No dimensions found.') }}</p>
    </div>
    @endforelse
    @if ($dimensions->hasPages())
    <div class="mt-3">{{ $dimensions->links() }}</div>
    @endif
</div>
@endsection
