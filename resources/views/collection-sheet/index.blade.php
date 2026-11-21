@extends('components.main-layout')
@section('title', __('Data Collections'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Data Collections') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Data Collections') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-dark btn-sm" title="{{ __('Everything already submitted, with its review status') }}"><i class="mdi mdi-format-list-bulleted"></i> {{ __('All records') }}</a>
</div>

<p class="text-muted mb-3">{{ __('Pick a thematic area to report on — the plan is laid out for you, you only fill in what was implemented.') }}</p>

@if ($thematicAreas->isNotEmpty() && $thematicAreas->sum('approved_plan_count') === 0)
<div class="alert alert-info mb-3">{{ __('Nothing can be reported on yet — an indicator opens for data collection once its annual plan is approved. In the Plan Builder: submit the plan, the lead institution confirms it, then the thematic area coordinator approves it.') }}</div>
@endif

<div class="plan-builder-grid">
    @foreach ($thematicAreas as $thematicArea)
    @php
        $pct = $thematicArea->collectable_count > 0
            ? (int) round(($thematicArea->approved_plan_count / $thematicArea->collectable_count) * 100)
            : 0;
    @endphp
    <a href="{{ route('collection-sheet.show', $thematicArea) }}" class="plan-pick-card">
        <div class="plan-pick-project">{{ $thematicArea->project?->name ?? __('No project') }}</div>
        <div class="plan-pick-name">{{ $thematicArea->localized_name }}</div>
        <div class="plan-pick-meta">
            <div class="plan-pick-progress"><span style="width: {{ $pct }}%"></span></div>
            <span>{{ __(':ready of :total indicators have an approved plan', ['ready' => $thematicArea->approved_plan_count, 'total' => $thematicArea->collectable_count]) }}</span>
        </div>
    </a>
    @endforeach

    @if ($thematicAreas->isEmpty())
    <div class="tbl-empty" style="grid-column: 1 / -1;">
        <i class="mdi mdi-clipboard-text-outline"></i>
        <p>{{ __('No indicators are assigned to you yet.') }}</p>
    </div>
    @endif
</div>
@endsection
