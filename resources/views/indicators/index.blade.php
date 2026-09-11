@extends('components.main-layout')
@section('title', 'Indicators')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Indicators</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Indicators</li>
            </ol>
        </nav>
    </div>
    @can('indicator.create')
    <a href="{{ route('indicators.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Indicator</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Thematic Area</th>
                <th>Frequency</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($indicators as $indicator)
            <tr>
                <td>{{ ($indicators->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $indicator->code }}</span></td>
                <td><a href="{{ route('indicators.show', $indicator) }}">{{ $indicator->name }}</a></td>
                <td>{{ $indicator->thematicArea?->name ?? '—' }}</td>
                <td>{{ $indicator->reporting_frequency ? ucfirst($indicator->reporting_frequency) : '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($indicator->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        default => 's-default',
                    } }}">{{ ucfirst($indicator->status) }}</span>
                </td>
                <td>
                    <a href="{{ route('indicators.show', $indicator) }}" class="btn-icon" title="View"><i class="mdi mdi-eye-outline"></i></a>
                    @can('indicator.update')
                    <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('indicator.delete')
                    <form action="{{ route('indicators.destroy', $indicator) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete indicator &quot;{{ $indicator->name }}&quot;?');">
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
                        <i class="mdi mdi-chart-box-outline"></i>
                        <p>No indicators found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($indicators->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $indicators->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($indicators as $indicator)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('indicators.show', $indicator) }}" class="fw-600">{{ $indicator->name }}</a>
            <span class="s-badge {{ match ($indicator->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                default => 's-default',
            } }}">{{ ucfirst($indicator->status) }}</span>
        </div>
        <div class="mob-card-body">
            <p class="mob-card-title"><span class="ref-pill">{{ $indicator->code }}</span></p>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-sitemap-outline"></i> {{ $indicator->thematicArea?->name ?? '—' }}</span>
            <span><i class="mdi mdi-calendar-refresh"></i> {{ $indicator->reporting_frequency ? ucfirst($indicator->reporting_frequency) : '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('indicators.show', $indicator) }}" class="btn-icon" title="View"><i class="mdi mdi-eye-outline"></i></a>
            @can('indicator.update')
            <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('indicator.delete')
            <form action="{{ route('indicators.destroy', $indicator) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete indicator &quot;{{ $indicator->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-chart-box-outline"></i>
        <p>No indicators found.</p>
    </div>
    @endforelse

    @if ($indicators->hasPages())
    <div class="mt-3">
        {{ $indicators->links() }}
    </div>
    @endif
</div>
@endsection
