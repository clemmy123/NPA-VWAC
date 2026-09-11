@extends('components.main-layout')
@section('title', $thematicArea->name)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ $thematicArea->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                @if ($thematicArea->project)
                <li class="breadcrumb-item"><a href="{{ route('projects.show', $thematicArea->project) }}">{{ $thematicArea->project->name }}</a></li>
                @endif
                <li class="breadcrumb-item active">{{ $thematicArea->name }}</li>
            </ol>
        </nav>
    </div>
    @can('thematic-area.update')
    <a href="{{ route('thematic-areas.edit', $thematicArea) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-pencil-outline"></i> Edit Thematic Area</a>
    @endcan
</div>

<div class="chart-card mb-4">
    <div class="row">
        <div class="col-md-4 mb-2"><span class="text-muted small">Project</span><div>{{ $thematicArea->project?->name ?? '—' }}</div></div>
        <div class="col-md-4 mb-2"><span class="text-muted small">Status</span><div>
            <span class="s-badge {{ match ($thematicArea->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($thematicArea->status) }}</span>
        </div></div>
        @if ($thematicArea->description)
        <div class="col-12 mt-2"><span class="text-muted small">Description</span><p class="mb-0">{{ $thematicArea->description }}</p></div>
        @endif
    </div>
</div>

{{-- Indicators --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Indicators</h5>
    @can('indicator.create')
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-indicator" data-quick-add-title="Add Indicator to &quot;{{ $thematicArea->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Indicator
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>Code</th><th>Name</th><th>Frequency</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($indicators as $indicator)
            <tr>
                <td><span class="ref-pill">{{ $indicator->code }}</span></td>
                <td><a href="{{ route('indicators.show', $indicator) }}">{{ $indicator->name }}</a></td>
                <td>{{ $indicator->reporting_frequency ? ucfirst($indicator->reporting_frequency) : '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($indicator->status) {
                        'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
                    } }}">{{ ucfirst($indicator->status) }}</span>
                </td>
                <td>
                    @can('indicator.update')
                    <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('indicator.delete')
                    <form action="{{ route('indicators.destroy', $indicator) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete indicator &quot;{{ $indicator->name }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="tbl-empty"><i class="mdi mdi-chart-box-outline"></i><p>No indicators yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($indicators as $indicator)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('indicators.show', $indicator) }}" class="fw-600">{{ $indicator->name }}</a>
            <span class="s-badge {{ match ($indicator->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($indicator->status) }}</span>
        </div>
        <div class="mob-card-body"><p class="mob-card-title"><span class="ref-pill">{{ $indicator->code }}</span></p></div>
        <div class="mob-card-footer">
            @can('indicator.update')
            <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('indicator.delete')
            <form action="{{ route('indicators.destroy', $indicator) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete indicator &quot;{{ $indicator->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-chart-box-outline"></i><p>No indicators yet.</p></div>
    @endforelse
</div>

{{-- Interventions --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Interventions</h5>
    @can('intervention.create')
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-intervention" data-quick-add-title="Add Intervention to &quot;{{ $thematicArea->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Intervention
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>Name</th><th>Linked Indicators</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($interventions as $intervention)
            <tr>
                <td>{{ $intervention->name }}</td>
                <td>{{ $intervention->indicators->pluck('name')->implode(', ') ?: '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($intervention->status) {
                        'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
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
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="tbl-empty"><i class="mdi mdi-rocket-launch-outline"></i><p>No interventions yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($interventions as $intervention)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $intervention->name }}</span>
            <span class="s-badge {{ match ($intervention->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($intervention->status) }}</span>
        </div>
        <div class="mob-card-meta"><span>{{ $intervention->indicators->pluck('name')->implode(', ') ?: '—' }}</span></div>
        <div class="mob-card-footer">
            @can('intervention.update')
            <a href="{{ route('interventions.edit', $intervention) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('intervention.delete')
            <form action="{{ route('interventions.destroy', $intervention) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete intervention &quot;{{ $intervention->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-rocket-launch-outline"></i><p>No interventions yet.</p></div>
    @endforelse
</div>

@can('indicator.create')
<template id="tpl-add-indicator">
    <form method="POST" action="{{ route('indicators.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('indicators.form', [
            'indicator' => new \App\Models\Indicator(['thematic_area_id' => $thematicArea->id]),
            'statusOptions' => $indicatorStatusOptions,
            'thematicAreas' => collect([$thematicArea]),
            'measurementTypes' => $measurementTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan

@can('intervention.create')
<template id="tpl-add-intervention">
    <form method="POST" action="{{ route('interventions.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('interventions.form', [
            'intervention' => new \App\Models\Intervention(['thematic_area_id' => $thematicArea->id]),
            'statusOptions' => $interventionStatusOptions,
            'thematicAreas' => collect([$thematicArea]),
            'indicators' => $allIndicators,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan
@endsection
