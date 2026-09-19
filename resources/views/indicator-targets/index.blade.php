@extends('components.main-layout')
@section('title', __('Indicator Targets'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Indicator Targets') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Targets') }}</li>
            </ol>
        </nav>
    </div>
    @can('indicator.set-target')
    <a href="{{ route('indicator-targets.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Target') }}</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Financial Year') }}</th>
                <th>{{ __('Period') }}</th>
                <th>{{ __('Dimension') }}</th>
                <th>{{ __('Target') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($targets as $target)
            <tr>
                <td>{{ ($targets->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $target->indicator?->name ?? '—' }}</td>
                <td>{{ $target->financialYear?->name ?? '—' }}</td>
                <td>{{ $target->reportingPeriod?->name ?? __('Whole year') }}</td>
                <td>{{ $target->dimensionOption?->name ?? __('Aggregate') }}</td>
                <td>{{ \App\Support\DisplayNumber::format($target->target_value) }}</td>
                <td>
                    @can('indicator.set-target')
                    <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this target?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-flag-outline"></i>
                        <p>{{ __('No targets found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($targets->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $targets->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($targets as $target)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $target->indicator?->name ?? '—' }}</span>
            <span class="s-badge s-default">{{ \App\Support\DisplayNumber::format($target->target_value) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $target->financialYear?->name ?? '—' }} · {{ $target->reportingPeriod?->name ?? __('Whole year') }}</span>
            <span><i class="mdi mdi-shape-outline"></i> {{ $target->dimensionOption?->name ?? __('Aggregate') }}</span>
        </div>
        <div class="mob-card-footer">
            @can('indicator.set-target')
            <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('Delete this target?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-flag-outline"></i>
        <p>{{ __('No targets found.') }}</p>
    </div>
    @endforelse

    @if ($targets->hasPages())
    <div class="mt-3">
        {{ $targets->links() }}
    </div>
    @endif
</div>
@endsection
