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
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Indicator</th>
                <th>Unit</th>
                <th class="text-end">Target</th>
                <th class="text-end">Actual (approved)</th>
                <th class="text-end">Achievement</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            @php($perf = $row['performance'])
            <tr>
                <td>
                    <div class="fw-semibold">{{ $row['indicator']->name }}</div>
                    <div class="text-muted small">{{ $row['indicator']->code }}</div>
                </td>
                <td>{{ $row['indicator']->unitOfMeasure?->name ?? '—' }}</td>
                <td class="text-end">{{ $perf['target_value'] === null ? '—' : rtrim(rtrim(number_format($perf['target_value'], 2), '0'), '.') }}</td>
                <td class="text-end">{{ $perf['actual_value'] === null ? '—' : rtrim(rtrim(number_format($perf['actual_value'], 2), '0'), '.') }}</td>
                <td class="text-end">
                    @if ($perf['achievement_percent'] === null)
                    <span class="text-muted">—</span>
                    @else
                    <span class="s-badge {{ $perf['achievement_percent'] >= 100 ? 's-active' : ($perf['achievement_percent'] >= 50 ? 's-investigation' : 's-inactive') }}">
                        {{ rtrim(rtrim(number_format($perf['achievement_percent'], 2), '0'), '.') }}%
                    </span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-file-chart-outline"></i>
                        <p>No indicators in this thematic area.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none">
    @forelse ($rows as $row)
    @php($perf = $row['performance'])
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="mob-card-title">{{ $row['indicator']->name }}</span>
            @if ($perf['achievement_percent'] !== null)
            <span class="s-badge {{ $perf['achievement_percent'] >= 100 ? 's-active' : ($perf['achievement_percent'] >= 50 ? 's-investigation' : 's-inactive') }}">
                {{ rtrim(rtrim(number_format($perf['achievement_percent'], 2), '0'), '.') }}%
            </span>
            @endif
        </div>
        <div class="mob-card-meta">
            <span>Target {{ $perf['target_value'] === null ? '—' : rtrim(rtrim(number_format($perf['target_value'], 2), '0'), '.') }}</span>
            <span>Actual {{ $perf['actual_value'] === null ? '—' : rtrim(rtrim(number_format($perf['actual_value'], 2), '0'), '.') }}</span>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-file-chart-outline"></i>
        <p>No indicators in this thematic area.</p>
    </div>
    @endforelse
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('general-report-filters');
    if (!form || !window.jQuery) {
        return;
    }

    var emptyValue = '__none__';

    function enhance(select) {
        var $select = jQuery(select);

        Array.from(select.options).forEach(function (option) {
            if (option.value === '') {
                option.value = emptyValue;
            }
        });

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            dropdownParent: $select.closest('.rf-field'),
            minimumResultsForSearch: 8,
            matcher: function (params, data) {
                if (data.element && data.element.disabled && data.element.getAttribute('data-project-id')) {
                    return null;
                }

                if (jQuery.trim(params.term) === '') {
                    return data;
                }

                return data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1 ? data : null;
            },
        });
    }

    form.querySelectorAll('select.form-control').forEach(enhance);

    form.addEventListener('submit', function () {
        form.querySelectorAll('select.form-control').forEach(function (select) {
            if (select.value === emptyValue) {
                select.disabled = true;
            }
        });
    });

    var projectSelect = document.getElementById('project_id');
    var areaSelect = document.getElementById('thematic_area_id');
    if (!projectSelect || !areaSelect) {
        return;
    }

    function filterAreas() {
        var projectId = projectSelect.value === emptyValue ? '' : projectSelect.value;
        var options = areaSelect.querySelectorAll('option[data-project-id]');
        var firstVisible = null;
        options.forEach(function (option) {
            var match = !projectId || option.getAttribute('data-project-id') === projectId;
            option.hidden = !match;
            option.disabled = !match;
            if (match && !firstVisible) {
                firstVisible = option;
            }
        });
        var selected = areaSelect.options[areaSelect.selectedIndex];
        if (selected && selected.hidden && firstVisible) {
            areaSelect.value = firstVisible.value;
        }
        jQuery(areaSelect).trigger('change.select2');
    }

    jQuery(projectSelect).on('change', filterAreas);
    filterAreas();
})();
</script>
@if ($applied && $analysis && $analysis['total'] > 0)
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
<script>
(function () {
    var chart = @json($analysis['chart']);
    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';
    Chart.defaults.scale.gridLines.color = 'rgba(100, 116, 139, 0.12)';

    var bar = document.getElementById('report-achievement-chart');
    if (bar) {
        new Chart(bar.getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: 'Achievement %',
                    data: chart.values,
                    backgroundColor: 'rgba(59, 130, 246, 0.35)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, suggestedMax: 100, callback: function (value) { return value + '%'; } } }],
                    yAxes: [{ ticks: { autoSkip: false } }]
                },
                tooltips: {
                    callbacks: {
                        label: function (item) { return item.xLabel + '%'; }
                    }
                }
            }
        });
    }

    var status = document.getElementById('report-status-chart');
    if (status) {
        new Chart(status.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chart.status_labels,
                datasets: [{
                    data: chart.status_values,
                    backgroundColor: ['#22c55e', '#d97706', '#dc2626', '#94a3b8'],
                    hoverBackgroundColor: ['#16a34a', '#b45309', '#b91c1c', '#64748b'],
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                cutoutPercentage: 62
            }
        });
    }
})();
</script>
@endif
@endpush
