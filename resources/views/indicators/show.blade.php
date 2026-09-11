@extends('components.main-layout')
@section('title', $indicator->name)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ $indicator->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicators.index') }}">Indicators</a></li>
                @if ($indicator->thematicArea)
                <li class="breadcrumb-item"><a href="{{ route('thematic-areas.show', $indicator->thematicArea) }}">{{ $indicator->thematicArea->name }}</a></li>
                @endif
                <li class="breadcrumb-item active">{{ $indicator->name }}</li>
            </ol>
        </nav>
    </div>
    @can('indicator.update')
    <a href="{{ route('indicators.edit', $indicator) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-pencil-outline"></i> Edit Indicator</a>
    @endcan
</div>

<div class="chart-card mb-4">
    <div class="row">
        <div class="col-md-3 mb-2"><span class="text-muted small">Code</span><div><span class="ref-pill">{{ $indicator->code }}</span></div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Status</span><div>
            <span class="s-badge {{ match ($indicator->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($indicator->status) }}</span>
        </div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Measurement Type</span><div>{{ $indicator->measurementType?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Unit of Measure</span><div>{{ $indicator->unitOfMeasure?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Reporting Frequency</span><div>{{ $indicator->reporting_frequency ? ucfirst($indicator->reporting_frequency) : '—' }}</div></div>
        @if ($indicator->description)
        <div class="col-12 mt-2"><span class="text-muted small">Description</span><p class="mb-0">{{ $indicator->description }}</p></div>
        @endif
    </div>
</div>

{{-- Baselines --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Baselines</h5>
    @can('indicator.set-baseline')
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-baseline" data-quick-add-title="Add Baseline for &quot;{{ $indicator->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Baseline
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>Value</th><th>Financial Year</th><th>Date</th><th>Source</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($baselines as $baseline)
            <tr>
                <td>{{ $baseline->baseline_value }}</td>
                <td>{{ $baseline->financialYear?->name ?? '—' }}</td>
                <td>{{ $baseline->baseline_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $baseline->source ?? '—' }}</td>
                <td>
                    @can('indicator.set-baseline')
                    <a href="{{ route('indicator-baselines.edit', $baseline) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-baselines.destroy', $baseline) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this baseline?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="tbl-empty"><i class="mdi mdi-target"></i><p>No baselines yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($baselines as $baseline)
    <div class="mob-card">
        <div class="mob-card-top"><span class="fw-600">{{ $baseline->baseline_value }}</span><span class="text-muted small">{{ $baseline->financialYear?->name ?? '—' }}</span></div>
        <div class="mob-card-meta"><span>{{ $baseline->source ?? '—' }}</span></div>
        <div class="mob-card-footer">
            @can('indicator.set-baseline')
            <a href="{{ route('indicator-baselines.edit', $baseline) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-baselines.destroy', $baseline) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this baseline?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-target"></i><p>No baselines yet.</p></div>
    @endforelse
</div>

{{-- Targets --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Targets</h5>
    @can('indicator.set-target')
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-target" data-quick-add-title="Add Target for &quot;{{ $indicator->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Target
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>Value</th><th>Financial Year</th><th>Reporting Period</th><th>Dimension</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($targets as $target)
            <tr>
                <td>{{ $target->target_value }}</td>
                <td>{{ $target->financialYear?->name ?? '—' }}</td>
                <td>{{ $target->reportingPeriod?->name ?? 'Whole year' }}</td>
                <td>{{ $target->dimensionOption?->name ?? 'Aggregate' }}</td>
                <td>
                    @can('indicator.set-target')
                    <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this target?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="tbl-empty"><i class="mdi mdi-bullseye-arrow"></i><p>No targets yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($targets as $target)
    <div class="mob-card">
        <div class="mob-card-top"><span class="fw-600">{{ $target->target_value }}</span><span class="text-muted small">{{ $target->financialYear?->name ?? '—' }}</span></div>
        <div class="mob-card-meta"><span>{{ $target->reportingPeriod?->name ?? 'Whole year' }}</span></div>
        <div class="mob-card-footer">
            @can('indicator.set-target')
            <a href="{{ route('indicator-targets.edit', $target) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-targets.destroy', $target) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this target?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-bullseye-arrow"></i><p>No targets yet.</p></div>
    @endforelse
</div>

{{-- Data Assignments --}}
@can('indicator.assign-user')
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Data Assignments</h5>
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-assignment" data-quick-add-title="Assign a Reporter to &quot;{{ $indicator->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Assignment
    </button>
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>User</th><th>Organization</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($assignments as $assignment)
            <tr>
                <td>{{ $assignment->user?->name ?? '—' }}</td>
                <td>{{ $assignment->organization?->name ?? '—' }}</td>
                <td>
                    @if ($assignment->location_level)
                        {{ ucfirst(str_replace('_', ' ', $assignment->location_level)) }}:
                        {{ $assignment->locationName() ?? '#'.$assignment->location_id }}
                    @else
                        <span class="text-muted">All locations</span>
                    @endif
                </td>
                <td><span class="s-badge {{ $assignment->is_active ? 's-active' : 's-inactive' }}">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('indicator-data-assignments.edit', $assignment) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-data-assignments.destroy', $assignment) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this assignment?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="tbl-empty"><i class="mdi mdi-account-arrow-right-outline"></i><p>No one assigned to report on this indicator yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($assignments as $assignment)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $assignment->user?->name ?? '—' }}</span>
            <span class="s-badge {{ $assignment->is_active ? 's-active' : 's-inactive' }}">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta"><span>{{ $assignment->organization?->name ?? '—' }}</span></div>
        <div class="mob-card-footer">
            <a href="{{ route('indicator-data-assignments.edit', $assignment) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-data-assignments.destroy', $assignment) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this assignment?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-account-arrow-right-outline"></i><p>No one assigned to report on this indicator yet.</p></div>
    @endforelse
</div>
@endcan

@can('indicator.set-baseline')
<template id="tpl-add-baseline">
    <form method="POST" action="{{ route('indicator-baselines.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('indicator-baselines.form', [
            'baseline' => new \App\Models\IndicatorBaseline(['indicator_id' => $indicator->id]),
            'indicators' => $allIndicators,
            'financialYears' => $financialYears,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan

@can('indicator.set-target')
<template id="tpl-add-target">
    <form method="POST" action="{{ route('indicator-targets.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('indicator-targets.form', [
            'target' => new \App\Models\IndicatorTarget(['indicator_id' => $indicator->id]),
            'indicators' => $allIndicators,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'dimensionOptions' => $dimensionOptions,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan

@can('indicator.assign-user')
<template id="tpl-add-assignment">
    <form method="POST" action="{{ route('indicator-data-assignments.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('indicator-data-assignments.form', [
            'assignment' => new \App\Models\IndicatorDataAssignment(['indicator_id' => $indicator->id]),
            'indicators' => $allIndicators,
            'users' => $users,
            'organizations' => $organizations,
            'dataSources' => $dataSources,
            'locationLevels' => $locationLevels,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan
@endsection
