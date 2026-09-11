@extends('components.main-layout')
@section('title', $project->name)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ $project->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                <li class="breadcrumb-item active">{{ $project->name }}</li>
            </ol>
        </nav>
    </div>
    @can('project.update')
    <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-pencil-outline"></i> Edit Project</a>
    @endcan
</div>

<div class="chart-card mb-4">
    <div class="row">
        <div class="col-md-3 mb-2"><span class="text-muted small">Code</span><div><span class="ref-pill">{{ $project->code }}</span></div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Status</span><div>
            <span class="s-badge {{ match ($project->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($project->status) }}</span>
        </div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">Start Date</span><div>{{ $project->start_date?->format('d M Y') ?? '—' }}</div></div>
        <div class="col-md-3 mb-2"><span class="text-muted small">End Date</span><div>{{ $project->end_date?->format('d M Y') ?? '—' }}</div></div>
        @if ($project->description)
        <div class="col-12 mt-2"><span class="text-muted small">Description</span><p class="mb-0">{{ $project->description }}</p></div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Thematic Areas</h5>
    @can('thematic-area.create')
    <button type="button" class="btn btn-dark btn-sm" data-quick-add="tpl-add-thematic-area" data-quick-add-title="Add Thematic Area to &quot;{{ $project->name }}&quot;">
        <i class="mdi mdi-plus"></i> Add Thematic Area
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead>
            <tr><th>Name</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($thematicAreas as $thematicArea)
            <tr>
                <td><a href="{{ route('thematic-areas.show', $thematicArea) }}">{{ $thematicArea->name }}</a></td>
                <td>
                    <span class="s-badge {{ match ($thematicArea->status) {
                        'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
                    } }}">{{ ucfirst($thematicArea->status) }}</span>
                </td>
                <td>
                    @can('thematic-area.update')
                    <a href="{{ route('thematic-areas.edit', $thematicArea) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('thematic-area.delete')
                    <form action="{{ route('thematic-areas.destroy', $thematicArea) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete thematic area &quot;{{ $thematicArea->name }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="3"><div class="tbl-empty"><i class="mdi mdi-sitemap-outline"></i><p>No thematic areas yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($thematicAreas as $thematicArea)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('thematic-areas.show', $thematicArea) }}" class="fw-600">{{ $thematicArea->name }}</a>
            <span class="s-badge {{ match ($thematicArea->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ ucfirst($thematicArea->status) }}</span>
        </div>
        <div class="mob-card-footer">
            @can('thematic-area.update')
            <a href="{{ route('thematic-areas.edit', $thematicArea) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('thematic-area.delete')
            <form action="{{ route('thematic-areas.destroy', $thematicArea) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete thematic area &quot;{{ $thematicArea->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-sitemap-outline"></i><p>No thematic areas yet.</p></div>
    @endforelse
</div>

@can('thematic-area.create')
<template id="tpl-add-thematic-area">
    <form method="POST" action="{{ route('thematic-areas.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('thematic-areas.form', [
            'thematicArea' => new \App\Models\ThematicArea(['project_id' => $project->id]),
            'statusOptions' => $thematicAreaStatusOptions,
            'projects' => $thematicAreaProjects,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>
@endcan
@endsection
