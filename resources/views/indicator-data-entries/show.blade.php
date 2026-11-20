@extends('components.main-layout')
@section('title', __('Collection summary'))

@php
    $historyUrl = route('indicator-data-entries.index', ['indicator_id' => $entry->indicator_id]).'#collection-records';
    $reportCount = $relatedReports->count();
@endphp

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Collection summary') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">{{ __('Data Collections') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Summary') }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex form-actions">
        <a href="{{ $historyUrl }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to records') }}</a>
    </div>
</div>

<div class="chart-card mb-4">
    @if ($entry->indicator?->thematicArea?->name)
    <h5 class="collection-summary-thematic mb-2"><span>{{ $entry->indicator->thematicArea->localized_name }}</span></h5>
    @endif
    <div class="collection-summary-indicator">
        @if ($entry->indicator?->code)
        <span class="ref-pill">{{ $entry->indicator->code }}</span>
        @endif
        <div>{{ $entry->indicator?->name ?? '—' }}</div>
    </div>
    <div class="collection-count-pill mt-3">
        <span class="collection-count-value">{{ $reportCount }}</span>
        <span>{{ __('collected') }}</span>
    </div>
</div>

@if($entry->plan_snapshot)
<div class="chart-card p-3 mb-3">
  <h5>{{ __('Approved annual plan') }}</h5>
  <p class="mb-1">{{ $entry->plan_snapshot['planned_activity'] ?? '—' }}</p>
  <p class="small mb-0">{{ __('Baseline') }}: {{ $entry->plan_snapshot['baseline_value'] ?? '—' }} · {{ __('Target') }}: {{ $entry->plan_snapshot['annual_target'] ?? '—' }} · {{ __('Funding (TZS)') }}: {{ number_format((float)($entry->plan_snapshot['domestic_budget'] ?? 0) + (float)($entry->plan_snapshot['external_budget'] ?? 0),2) }}</p>
</div>
@endif
@if(in_array($entry->status,['submitted','pending_approval']) && ! $entry->institution_confirmed_at
    && (auth()->user()->hasRole('Super Admin') || (auth()->user()->can('institution.confirm') && (int)auth()->user()->organization_id === (int)($entry->organization_id ?: $entry->plan?->lead_organization_id) && (int)auth()->id() !== (int)$entry->entered_by)))
<div class="mb-3"><form method="POST" action="{{ route('indicator-data-entries.institution-confirm',$entry) }}">@csrf
  <button type="submit" class="btn btn-success">{{ __('Confirm institution ownership') }}</button>
</form></div>
@endif
@foreach ($relatedReports as $report)
    @include('indicator-data-entries.partials.report-detail', [
        'entry' => $report,
        'number' => $loop->iteration,
        'locationNames' => $locationNames,
    ])
@endforeach
@endsection
