@extends('components.main-layout')
@section('title', 'Interventions')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Interventions</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Interventions</li>
            </ol>
        </nav>
    </div>
    @can('intervention.create')
    <a href="{{ route('interventions.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Intervention</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Thematic Area</th>
                <th>Indicators</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($interventions as $intervention)
            <tr>
                <td>{{ ($interventions->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $intervention->name }}</td>
                <td>{{ $intervention->thematicArea?->name ?? '—' }}</td>
                <td>{{ $intervention->indicators->count() }}</td>
                <td>
                    <span class="s-badge {{ match ($intervention->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        default => 's-default',
                    } }}">{{ ucfirst($intervention->status) }}</span>
                </td>
                <td>
                    @can('intervention.update')
                    <a href="{{ route('interventions.edit', $intervention) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('intervention.delete')
                    <form action="{{ route('interventions.destroy', $intervention) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete intervention &quot;{{ $intervention->name }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-hand-heart-outline"></i>
                        <p>No interventions found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($interventions->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $interventions->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($interventions as $intervention)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $intervention->name }}</span>
            <span class="s-badge {{ match ($intervention->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                default => 's-default',
            } }}">{{ ucfirst($intervention->status) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-sitemap-outline"></i> {{ $intervention->thematicArea?->name ?? '—' }}</span>
            <span><i class="mdi mdi-chart-box-outline"></i> {{ $intervention->indicators->count() }} indicators</span>
        </div>
        <div class="mob-card-footer">
            @can('intervention.update')
            <a href="{{ route('interventions.edit', $intervention) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('intervention.delete')
            <form action="{{ route('interventions.destroy', $intervention) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete intervention &quot;{{ $intervention->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-hand-heart-outline"></i>
        <p>No interventions found.</p>
    </div>
    @endforelse

    @if ($interventions->hasPages())
    <div class="mt-3">
        {{ $interventions->links() }}
    </div>
    @endif
</div>
@endsection
