@extends('components.main-layout')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">General Overview</li>
            </ol>
        </nav>
    </div>
</div>

@if ($projects->isEmpty())
<div class="chart-card">
    <p class="mb-0 text-muted">No plans are visible to you yet.</p>
</div>
@else
<form method="GET" action="{{ route('dashboard') }}" class="filter-card report-filters" id="dashboard-filters">
    <div class="rf-field">
        <label for="project_id">Plan</label>
        <select name="project_id" id="project_id" class="form-control">
            @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="rf-field">
        <label for="thematic_area_id">Thematic Area</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control">
            @foreach ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" data-project-id="{{ $thematicArea->project_id }}" @selected($selectedThematicArea?->id === $thematicArea->id)>
                {{ $thematicArea->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="rf-field">
        <label for="indicator_id">Indicator</label>
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
                <div class="dash-stat-label">Plan Period</div>
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
                <div class="dash-stat-label">Main Target</div>
                <div class="dash-stat-value">{{ $selectedThematicArea?->description ?: '—' }}</div>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon is-purple"><i class="bi bi-graph-up"></i></span>
            <div>
                <div class="dash-stat-label">Progress</div>
                <div class="dash-stat-value">{{ $progressPercent === null ? '—' : (int) round($progressPercent).'% reached' }}</div>
            </div>
        </div>
    </div>
    <div class="dash-stat dash-stat-wide">
        <span class="dash-stat-icon is-orange"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <div class="dash-stat-label">Plan Description</div>
            <div class="dash-stat-value">{{ $selectedProject->description ?: '—' }}</div>
        </div>
    </div>

    <div class="dash-progress-row">
        <span class="dash-progress-label">Progress</span>
        <div class="dash-progress-track" aria-hidden="true">
            <div class="dash-progress-fill" style="width: {{ $progressPercent === null ? 0 : min(100, max(0, $progressPercent)) }}%"></div>
        </div>
        <span class="dash-progress-pct">({{ $progressPercent === null ? '—' : (int) round($progressPercent).'%' }})</span>
    </div>
</div>

<div class="chart-card">
    <div class="chart-card-title">{{ $breakdown['title'] }}</div>
    @if ($breakdown['labels'] === [])
    <p class="mb-0 text-muted">No approved collections for this indicator yet.</p>
    @else
    <div class="dash-region-chart">
        <canvas id="dashboard-region-chart"></canvas>
    </div>
    @if ($breakdown['type'] === 'location' && $breakdown['rows'] !== [])
    <div class="table-responsive mt-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Administrative hierarchy</th><th class="text-end">Approved actual</th></tr></thead>
            <tbody>
            @foreach ($breakdown['rows'] as $row)
                <tr><td>{{ $row['path'] }}</td><td class="text-end">{{ number_format($row['value'], 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
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
    Chart.defaults.scale.gridLines.color = 'rgba(100, 116, 139, 0.12)';

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chart.labels,
            datasets: [{
                label: 'Approved actual',
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
                yAxes: [{ ticks: { beginAtZero: true, callback: function (value) { return value; } } }]
            },
            tooltips: {
                callbacks: {
                    label: function (item) { return 'Approved actual: ' + item.yLabel; }
                }
            }
        }
    });
})();
</script>
@endif
@endpush
