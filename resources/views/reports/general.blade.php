@extends('components.main-layout')
@section('title', __(ucfirst($frequency).' Reports'))

@php
    $freqQuery = array_filter([
        'project_id' => $selectedProject?->id,
        'thematic_area_id' => $selectedThematicArea?->id,
        'indicator_id' => $selectedIndicator?->id,
        'apply' => $applied ? 1 : null,
    ]);
@endphp

@section('content')
<div class="report-freq-wrap">
    <div class="report-freq" role="tablist" aria-label="{{ __('Report frequency') }}">
        <a href="{{ route('reports.general', ['frequency' => 'monthly'] + $freqQuery) }}" class="{{ $frequency === 'monthly' ? 'is-active' : '' }}">{{ __('Monthly') }}</a>
        <a href="{{ route('reports.general', ['frequency' => 'quarterly'] + $freqQuery) }}" class="{{ $frequency === 'quarterly' ? 'is-active' : '' }}">{{ __('Quarterly') }}</a>
        <a href="{{ route('reports.general', ['frequency' => 'yearly'] + $freqQuery) }}" class="{{ $frequency === 'yearly' ? 'is-active' : '' }}">{{ __('Yearly') }}</a>
    </div>
</div>

<div class="page-header">
    <div>
        <h4 class="page-title">{{ __(ucfirst($frequency).' Reports') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item">{{ __('Reports') }}</li>
                <li class="breadcrumb-item active">{{ __('General Report') }}</li>
            </ol>
        </nav>
    </div>
</div>

<form method="GET" action="{{ route('reports.general') }}" class="filter-card report-filters" id="general-report-filters">
    <input type="hidden" name="frequency" value="{{ $frequency }}">
    <input type="hidden" name="apply" value="1">

    @if ($frequency === 'monthly')
    <div class="rf-field">
        <label for="month">{{ __('Month') }}</label>
        <input type="month" name="month" id="month" class="form-control" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
    </div>
    @elseif ($frequency === 'quarterly')
    <div class="rf-field">
        <label for="reporting_period_id">{{ __('Quarter') }}</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control">
            @forelse ($reportingPeriods as $period)
            <option value="{{ $period->id }}" @selected($selectedReportingPeriod?->id === $period->id)>
                {{ $period->financialYear?->name }} · {{ $period->name }}
            </option>
            @empty
            <option value="">{{ __('No quarters yet') }}</option>
            @endforelse
        </select>
    </div>
    @else
    <div class="rf-field">
        <label for="financial_year_id">{{ __('Year') }}</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control">
            @forelse ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected($selectedFinancialYear?->id === $financialYear->id)>
                {{ $financialYear->name }}{{ $financialYear->is_current ? ' '.__('(current)') : '' }}
            </option>
            @empty
            <option value="">{{ __('No financial years yet') }}</option>
            @endforelse
        </select>
    </div>
    @endif

    <div class="rf-field">
        <label for="project_id">{{ __('Plan') }}</label>
        <select name="project_id" id="project_id" class="form-control">
            @forelse ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->name }}</option>
            @empty
            <option value="">{{ __('No plans yet') }}</option>
            @endforelse
        </select>
    </div>

    <div class="rf-field">
        <label for="thematic_area_id">{{ __('Thematic Area') }}</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control">
            <option value="" @selected(! $selectedThematicArea)>{{ __('All Thematic Areas') }}</option>
            @forelse ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" data-project-id="{{ $thematicArea->project_id }}" @selected($selectedThematicArea?->id === $thematicArea->id)>
                {{ $thematicArea->name }}
            </option>
            @empty
            <option value="">{{ __('No thematic areas yet') }}</option>
            @endforelse
        </select>
    </div>

    <div class="rf-field">
        <label for="indicator_id">{{ __('Indicator') }}</label>
        <select name="indicator_id" id="indicator_id" class="form-control">
            <option value="" @selected(! $selectedIndicator)>{{ __('All Indicators') }}</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" data-thematic-area-id="{{ $indicator->thematic_area_id }}" @selected($selectedIndicator?->id === $indicator->id)>
                {{ $indicator->code ? $indicator->code.' · ' : '' }}{{ $indicator->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="rf-actions">
        <button type="submit" class="btn btn-dark">{{ __('Filter') }}</button>
        <a href="{{ route('reports.general', ['frequency' => $frequency]) }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
    </div>
</form>

@if (! $applied)
<div class="chart-card">
    <p class="mb-0 text-muted">{{ __('Apply filters to see analysis.') }}</p>
</div>
@elseif ($projects->isEmpty())
<div class="chart-card">
    <p class="mb-0 text-muted">{{ __('No plans are visible to you yet.') }}</p>
</div>
@elseif (! $selectedFinancialYear)
<div class="chart-card">
    <p class="mb-0 text-muted">{{ __('No financial year covers this period. Create one in Settings.') }}</p>
</div>
@else
@include('reports.partials.analysis')
@include('reports.partials.indicator-results')
@endif
@endsection

@push('scripts')
@include('reports.partials.filter-selects-script', ['formId' => 'general-report-filters'])
@include('reports.partials.charts-script')
@endpush
