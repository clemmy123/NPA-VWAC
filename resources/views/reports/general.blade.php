@extends('components.main-layout')
@section('title', ucfirst($frequency).' Reports')

@php
    $freqQuery = array_filter([
        'project_id' => $selectedProject?->id,
        'thematic_area_id' => $selectedThematicArea?->id,
        'apply' => $applied ? 1 : null,
    ]);
@endphp

@section('content')
<div class="report-freq-wrap">
    <div class="report-freq" role="tablist" aria-label="Report frequency">
        <a href="{{ route('reports.general', ['frequency' => 'monthly'] + $freqQuery) }}" class="{{ $frequency === 'monthly' ? 'is-active' : '' }}">Monthly</a>
        <a href="{{ route('reports.general', ['frequency' => 'quarterly'] + $freqQuery) }}" class="{{ $frequency === 'quarterly' ? 'is-active' : '' }}">Quarterly</a>
        <a href="{{ route('reports.general', ['frequency' => 'yearly'] + $freqQuery) }}" class="{{ $frequency === 'yearly' ? 'is-active' : '' }}">Yearly</a>
    </div>
</div>

<div class="page-header">
    <div>
        <h4 class="page-title">{{ ucfirst($frequency) }} Reports</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active">General Report</li>
            </ol>
        </nav>
    </div>
</div>

<form method="GET" action="{{ route('reports.general') }}" class="filter-card report-filters" id="general-report-filters">
    <input type="hidden" name="frequency" value="{{ $frequency }}">
    <input type="hidden" name="apply" value="1">

    @if ($frequency === 'monthly')
    <div class="rf-field">
        <label for="month">Month</label>
        <input type="date" name="month" id="month" class="form-control" value="{{ $month->toDateString() }}">
    </div>
    @elseif ($frequency === 'quarterly')
    <div class="rf-field">
        <label for="reporting_period_id">Quarter</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control">
            @forelse ($reportingPeriods as $period)
            <option value="{{ $period->id }}" @selected($selectedReportingPeriod?->id === $period->id)>
                {{ $period->financialYear?->name }} · {{ $period->name }}
            </option>
            @empty
            <option value="">No quarters yet</option>
            @endforelse
        </select>
    </div>
    @else
    <div class="rf-field">
        <label for="financial_year_id">Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control">
            @forelse ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected($selectedFinancialYear?->id === $financialYear->id)>
                {{ $financialYear->name }}{{ $financialYear->is_current ? ' (current)' : '' }}
            </option>
            @empty
            <option value="">No financial years yet</option>
            @endforelse
        </select>
    </div>
    @endif

    <div class="rf-field">
        <label for="project_id">Plan</label>
        <select name="project_id" id="project_id" class="form-control">
            @forelse ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->name }}</option>
            @empty
            <option value="">No plans yet</option>
            @endforelse
        </select>
    </div>

    <div class="rf-field">
        <label for="thematic_area_id">Thematic Area</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control">
            @forelse ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" data-project-id="{{ $thematicArea->project_id }}" @selected($selectedThematicArea?->id === $thematicArea->id)>
                {{ $thematicArea->name }}
            </option>
            @empty
            <option value="">No thematic areas yet</option>
            @endforelse
        </select>
    </div>

    <div class="rf-actions">
        <button type="submit" class="btn btn-dark">Filter</button>
        <a href="{{ route('reports.general', ['frequency' => $frequency]) }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

@if (! $applied)
<div class="chart-card">
    <p class="mb-0 text-muted">Apply filters to see analysis.</p>
</div>
@elseif ($projects->isEmpty())
<div class="chart-card">
    <p class="mb-0 text-muted">No plans are visible to you yet.</p>
</div>
@elseif (! $selectedThematicArea)
<div class="chart-card">
    <p class="mb-0 text-muted">Select a plan and thematic area, then click Filter.</p>
</div>
@elseif (! $selectedFinancialYear)
<div class="chart-card">
    <p class="mb-0 text-muted">No financial year covers this period. Create one in Settings.</p>
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
