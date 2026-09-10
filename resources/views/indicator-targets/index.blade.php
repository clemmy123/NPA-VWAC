@extends('components.main-layout')
@section('title', 'Indicator Targets')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Indicator Targets</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Targets</li>
            </ol>
        </nav>
    </div>
    @can('indicator.set-target')
    <a href="{{ route('indicator-targets.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Target</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Indicator</th>
                <th>Financial Year</th>
                <th>Period</th>
                <th>Dimension</th>
                <th>Target</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($targets as $target)
            <tr>
                <td>{{ ($targets->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $target->indicator?->name ?? '—' }}</td>
                <td>{{ $target->financialYear?->name ?? '—' }}</td>
                <td>{{ $target->reportingPeriod?->name ?? 'Whole year' }}</td>
                <td>{{ $target->dimensionOption?->name ?? 'Aggregate' }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $target->target_value, 4), '0'), '.') }}</td>
                <td>
                    @can('indicator.set-target')
                    <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this target?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-flag-outline"></i>
                        <p>No targets found.</p>
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
            <span class="s-badge s-default">{{ rtrim(rtrim(number_format((float) $target->target_value, 4), '0'), '.') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $target->financialYear?->name ?? '—' }} · {{ $target->reportingPeriod?->name ?? 'Whole year' }}</span>
            <span><i class="mdi mdi-shape-outline"></i> {{ $target->dimensionOption?->name ?? 'Aggregate' }}</span>
        </div>
        <div class="mob-card-footer">
            @can('indicator.set-target')
            <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this target?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-flag-outline"></i>
        <p>No targets found.</p>
    </div>
    @endforelse

    @if ($targets->hasPages())
    <div class="mt-3">
        {{ $targets->links() }}
    </div>
    @endif
</div>
@endsection
