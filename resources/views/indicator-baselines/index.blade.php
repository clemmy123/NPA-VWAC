@extends('components.main-layout')
@section('title', 'Indicator Baselines')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Indicator Baselines</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Baselines</li>
            </ol>
        </nav>
    </div>
    @can('indicator.set-baseline')
    <a href="{{ route('indicator-baselines.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Baseline</a>
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
                <th>Value</th>
                <th>Date</th>
                <th>Source</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baselines as $baseline)
            <tr>
                <td>{{ ($baselines->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $baseline->indicator?->name ?? '—' }}</td>
                <td>{{ $baseline->financialYear?->name ?? '—' }}</td>
                <td>{{ \App\Support\DisplayNumber::format($baseline->baseline_value) }}</td>
                <td>{{ $baseline->baseline_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $baseline->organization?->name ?? '—' }}</td>
                <td>
                    @can('indicator.set-baseline')
                    <a href="{{ route('indicator-baselines.edit', $baseline) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-baselines.destroy', $baseline) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this baseline?');">
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
                        <i class="mdi mdi-bullseye-arrow"></i>
                        <p>No baselines found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($baselines->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $baselines->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($baselines as $baseline)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $baseline->indicator?->name ?? '—' }}</span>
            <span class="s-badge s-default">{{ \App\Support\DisplayNumber::format($baseline->baseline_value) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $baseline->financialYear?->name ?? '—' }}</span>
            <span><i class="mdi mdi-file-document-outline"></i> {{ $baseline->organization?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @can('indicator.set-baseline')
            <a href="{{ route('indicator-baselines.edit', $baseline) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-baselines.destroy', $baseline) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this baseline?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-bullseye-arrow"></i>
        <p>No baselines found.</p>
    </div>
    @endforelse

    @if ($baselines->hasPages())
    <div class="mt-3">
        {{ $baselines->links() }}
    </div>
    @endif
</div>
@endsection
