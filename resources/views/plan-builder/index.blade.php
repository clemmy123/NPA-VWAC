@extends('components.main-layout')
@section('title', __('Plan Builder'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Plan Builder') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Plan Builder') }}</li>
            </ol>
        </nav>
    </div>
</div>

<p class="text-muted mb-3">{{ __('Pick a thematic area to build — each one is its own workspace.') }}</p>

@if ($filterProjects->count() > 1)
<form method="GET" action="{{ route('plan-builder.index') }}" class="plan-project-filter">
    <label for="project_id">{{ __('Project') }}</label>
    <select name="project_id" id="project_id" class="form-control" onchange="this.form.submit()">
        <option value="">{{ __('All projects') }}</option>
        @foreach ($filterProjects as $project)
        <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
        @endforeach
    </select>
</form>
@endif

<div class="plan-builder-grid">
    @foreach ($thematicAreas as $thematicArea)
    @php
        $pct = $thematicArea->indicator_count > 0
            ? (int) round(($thematicArea->targeted_count / $thematicArea->indicator_count) * 100)
            : 0;
    @endphp
    <a href="{{ route('plan-builder.show', $thematicArea) }}" class="plan-pick-card">
        <div class="plan-pick-project">{{ $thematicArea->project?->name ?? __('No project') }}</div>
        <div class="plan-pick-name">{{ $thematicArea->name }}</div>
        <div class="plan-pick-meta">
            <div class="plan-pick-progress"><span style="width: {{ $pct }}%"></span></div>
            <span>{{ $thematicArea->indicator_count === 1 ? __(':count indicator', ['count' => $thematicArea->indicator_count]) : __(':count indicators', ['count' => $thematicArea->indicator_count]) }}</span>
        </div>
    </a>
    @endforeach

    @can('thematic-area.create')
    <button type="button" class="plan-new-card" data-quick-add="tpl-add-thematic-area" data-quick-add-title="{{ __('New Thematic Area') }}">
        <i class="mdi mdi-plus"></i> {{ __('New thematic area') }}
    </button>
    @endcan

    @if ($thematicAreas->isEmpty() && ! $canCreateThematicArea)
    <div class="tbl-empty" style="grid-column: 1 / -1;">
        <i class="mdi mdi-sitemap-outline"></i>
        <p>{{ __('No thematic areas assigned to you yet.') }}</p>
    </div>
    @endif
</div>

@can('thematic-area.create')
<template id="tpl-add-thematic-area">
    <form method="POST" action="{{ route('thematic-areas.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ route('plan-builder.index') }}">
        @include('thematic-areas.form', [
            'thematicArea' => new \App\Models\ThematicArea(),
            'projects' => $projects,
            'statusOptions' => $statusOptions,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Create') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </form>
</template>
@endcan
@endsection
