@extends('components.main-layout')
@section('title', __('Dashboard'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Dashboard') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">{{ __('General Overview') }}</li>
            </ol>
        </nav>
    </div>
    @can('indicator-data.create')
    <a href="{{ route('collection-sheet.index') }}" class="btn btn-primary btn-sm"><i class="mdi mdi-clipboard-check-outline"></i> {{ __('Collect Data') }}</a>
    @endcan
</div>

@if ($projects->isEmpty())
<div class="chart-card tbl-empty">
    <i class="mdi mdi-view-dashboard-outline"></i>
    <p>{{ __('No plans are visible to you yet.') }}</p>
    <p class="small">{{ __('Once a thematic area is assigned to you and its plan is approved, progress shows up here.') }}</p>
    @can('thematic-area.view')<a href="{{ route('plan-builder.index') }}" class="btn btn-primary btn-sm">{{ __('Open Plan Builder') }}</a>@endcan
</div>
@else
<div class="dash-kpis">
    <a href="{{ route('plan-builder.index') }}" class="dash-kpi is-purple">
        <span class="dash-kpi-icon"><i class="mdi mdi-file-check-outline"></i></span>
        <div><div class="dash-kpi-value">{{ $overview['planned'] }}<small>/{{ $overview['indicators'] }}</small></div><div class="dash-kpi-label">{{ __('Indicators with approved plan') }}</div></div>
    </a>
    <a href="{{ route('plan-builder.index') }}" class="dash-kpi is-amber">
        <span class="dash-kpi-icon"><i class="mdi mdi-timer-sand"></i></span>
        <div><div class="dash-kpi-value">{{ $overview['awaiting_plan'] }}</div><div class="dash-kpi-label">{{ __('Plans awaiting review') }}</div></div>
    </a>
    <a href="{{ route('indicator-data-entries.index') }}" class="dash-kpi is-pink">
        <span class="dash-kpi-icon"><i class="mdi mdi-inbox-arrow-down"></i></span>
        <div><div class="dash-kpi-value">{{ $overview['awaiting_data'] }}</div><div class="dash-kpi-label">{{ __('Reports awaiting review') }}</div></div>
    </a>
    <div class="dash-kpi is-green">
        <span class="dash-kpi-icon"><i class="mdi mdi-check-decagram"></i></span>
        <div><div class="dash-kpi-value">{{ $overview['approved_entries'] }}</div><div class="dash-kpi-label">{{ __('Approved reports') }}</div></div>
    </div>
    <div class="dash-kpi is-blue dash-kpi-wide">
        <span class="dash-kpi-icon"><i class="mdi mdi-cash-multiple"></i></span>
        <div>
            <div class="dash-kpi-value">TZS {{ number_format($overview['budget_spent']) }}<small> {{ __('spent') }}</small></div>
            <div class="dash-kpi-label">{{ __('of :released released · :planned planned', ['released' => number_format($overview['budget_released']), 'planned' => number_format($overview['budget_planned'])]) }}</div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('dashboard') }}" class="filter-card report-filters" id="dashboard-filters">
    <div class="rf-field">
        <label for="project_id">{{ __('Plan') }}</label>
        <select name="project_id" id="project_id" class="form-control">
            @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="rf-field">
        <label for="thematic_area_id">{{ __('Thematic Area') }}</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control">
            @foreach ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" data-project-id="{{ $thematicArea->project_id }}" @selected($selectedThematicArea?->id === $thematicArea->id)>
                {{ $thematicArea->localized_name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="rf-field">
        <label for="indicator_id">{{ __('Indicator') }}</label>
        <select name="indicator_id" id="indicator_id" class="form-control">
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" data-thematic-area-id="{{ $indicator->thematic_area_id }}" @selected($selectedIndicator?->id === $indicator->id)>
                {{ $indicator->name }}
            </option>
            @endforeach
        </select>
    </div>
</form>

@if ($selectedProject)
<div class="chart-card">
    <div class="dash-summary-grid">
        <div class="dash-stat">
            <span class="dash-stat-icon is-blue"><i class="bi bi-calendar3"></i></span>
            <div>
                <div class="dash-stat-label">{{ __('Plan Period') }}</div>
                <div class="dash-stat-value">
                    @if ($financialYear?->start_date && $financialYear?->end_date)
                    {{ $financialYear->name }} · {{ $financialYear->start_date->format('d M Y') }} - {{ $financialYear->end_date->format('d M Y') }}
                    @elseif ($financialYear?->name)
                    {{ $financialYear->name }}
                    @else
                    —
                    @endif
                </div>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon is-green"><i class="bi bi-bullseye"></i></span>
            <div>
                <div class="dash-stat-label">{{ __('Main Target') }}</div>
                <div class="dash-stat-value">{{ $selectedThematicArea?->description ?: '—' }}</div>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon is-purple"><i class="bi bi-graph-up"></i></span>
            <div>
                <div class="dash-stat-label">{{ __('Progress') }}</div>
                <div class="dash-stat-value">{{ $progressPercent === null ? __('0% reached') : __(':percent% reached', ['percent' => (int) round($progressPercent)]) }}</div>
            </div>
        </div>
    </div>
    <div class="dash-stat dash-stat-wide">
        <span class="dash-stat-icon is-orange"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <div class="dash-stat-label">{{ __('Plan Description') }}</div>
            <div class="dash-stat-value">{{ $selectedProject->description ?: '—' }}</div>
        </div>
    </div>

    <div class="dash-progress-row">
        <span class="dash-progress-label">{{ __('Progress') }}</span>
        <div class="dash-progress-track" aria-hidden="true">
            <div class="dash-progress-fill" style="width: {{ $progressPercent === null ? 0 : min(100, max(0, $progressPercent)) }}%"></div>
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-card-title">{{ $breakdown['title'] }}</div>
    @if ($breakdown['labels'] === [])
    <p class="mb-0 text-muted">{{ __('No approved collections for this indicator yet.') }}</p>
    @else
    <div class="dash-region-chart">
        <canvas id="dashboard-region-chart"></canvas>
    </div>
    @if ($breakdown['type'] === 'location' && $breakdown['rows'] !== [])
    <div class="table-responsive mt-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>{{ __('Administrative hierarchy') }}</th><th class="text-end">{{ __('Approved actual') }}</th></tr></thead>
            <tbody>
            @foreach ($breakdown['rows'] as $row)
                <tr><td>{{ $row['path'] }}</td><td class="text-end">{{ \App\Support\DisplayNumber::format($row['value']) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @foreach (($breakdown['rollups'] ?? []) as $rollup)
    <div class="table-responsive mt-3">
        <div class="fw-semibold mb-1">{{ __(':level summary', ['level' => __(str_replace('_', ' ', $rollup['level']))]) }}</div>
        <table class="table table-sm mb-0">
            <thead><tr><th>{{ __('Location') }}</th><th class="text-end">{{ __('Approved actual') }}</th></tr></thead>
            <tbody>
            @foreach ($rollup['rows'] as $rollupRow)
                <tr><td>{{ $rollupRow['name'] }}</td><td class="text-end">{{ \App\Support\DisplayNumber::format($rollupRow['value']) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
    @endif
</div>
@endif
@endif
@endsection

@push('scripts')
@include('reports.partials.filter-selects-script', ['formId' => 'dashboard-filters', 'autoSubmit' => true])
@if ($breakdown['labels'] !== [])
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
<script>
(function () {
    var chart = @json($breakdown);
    var canvas = document.getElementById('dashboard-region-chart');
    if (!canvas) {
        return;
    }

    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chart.labels,
            datasets: [{
                label: @json(__('Approved actual')),
                data: chart.values,
                backgroundColor: '#3b82f6',
                hoverBackgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: true, position: 'bottom' },
            scales: {
                xAxes: [{ ticks: { autoSkip: false }, gridLines: { display: false } }],
                yAxes: [{ ticks: { beginAtZero: true, callback: function (value) { return value; } }, gridLines: { display: false } }]
            },
            tooltips: {
                callbacks: {
                    label: function (item) { return @json(__('Approved actual')).concat(': ', item.yLabel); }
                }
            }
        }
    });
})();
</script>
@endif
@endpush
