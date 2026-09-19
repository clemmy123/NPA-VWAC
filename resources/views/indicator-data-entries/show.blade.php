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
    <h5 class="collection-summary-thematic mb-2"><span>{{ $entry->indicator->thematicArea->name }}</span></h5>
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

@foreach ($relatedReports as $report)
    @include('indicator-data-entries.partials.report-detail', [
        'entry' => $report,
        'number' => $loop->iteration,
        'locationNames' => $locationNames,
    ])
@endforeach
@endsection
