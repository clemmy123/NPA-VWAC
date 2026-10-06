<div class="chart-card mb-3">
    <h5 class="me-section-title mb-1">{{ __('Monitoring & Evaluation') }}</h5>
    <p class="text-muted small mb-0">
        {{ __(':frequency analysis', ['frequency' => __(ucfirst($frequency))]) }}
        @if ($periodLabel) · {{ $periodLabel }} @endif
        @if ($selectedProject) · {{ $selectedProject->name }} @endif
        @if ($selectedThematicArea) · {{ $selectedThematicArea->name }} @endif
        @if ($selectedIndicator ?? null) · {{ $selectedIndicator->code ? $selectedIndicator->code.' · ' : '' }}{{ $selectedIndicator->name }} @endif
        @if ($selectedOrganization ?? null) · {{ $selectedOrganization->name }}
        @elseif ($workstationScopeLabel ?? null) · {{ $workstationScopeLabel }}
        @endif
        @if ($locationScopeLabel ?? null) · {{ $locationScopeLabel }} @endif
    </p>
</div>

@php
    $statusSegments = [
        ['key' => 'on-track', 'label' => __('On track'), 'count' => $analysis['on_track'], 'percent' => $analysis['on_track_percent']],
        ['key' => 'at-risk', 'label' => __('At risk'), 'count' => $analysis['at_risk'], 'percent' => $analysis['at_risk_percent']],
        ['key' => 'off-track', 'label' => __('Off track'), 'count' => $analysis['off_track'], 'percent' => $analysis['off_track_percent']],
        ['key' => 'no-data', 'label' => __('No data'), 'count' => $analysis['no_data'], 'percent' => $analysis['no_data_percent']],
    ];
@endphp

<div class="me-kpis">
    <div class="me-kpi">
        <div class="me-kpi-label">{{ __('Indicators') }}</div>
        <div class="me-kpi-value">{{ $analysis['total'] }}</div>
        <div class="me-kpi-meta">
            @if (isset($indicatorsReported))
            {{ __(':reported of :total reported', ['reported' => $indicatorsReported, 'total' => $analysis['total']]) }}
            @elseif ($selectedIndicator ?? null)
            {{ __('this indicator') }}
            @elseif ($selectedThematicArea ?? null)
            {{ __('in this thematic area') }}
            @else
            {{ __('across all thematic areas') }}
            @endif
        </div>
    </div>
    <div class="me-kpi">
        <div class="me-kpi-label">{{ __('Average achievement') }}</div>
        <div class="me-kpi-value">{{ $analysis['average_achievement'] === null ? '—' : \App\Support\DisplayNumber::format($analysis['average_achievement']).'%' }}</div>
        <div class="me-kpi-meta">{{ __('of scored indicators') }}</div>
    </div>
    <div class="me-kpi me-kpi-status">
        <div class="me-kpi-label">{{ __('Indicator status') }}</div>
        <div class="me-status-bar" role="img" aria-label="{{ collect($statusSegments)->map(fn ($segment) => $segment['label'].' '.$segment['count'])->implode(', ') }}">
            @foreach ($statusSegments as $segment)
            @if ($segment['count'] > 0)
            <span class="is-{{ $segment['key'] }}" style="flex-grow: {{ $segment['count'] }}" title="{{ $segment['label'] }}: {{ $segment['count'] }} ({{ \App\Support\DisplayNumber::format($segment['percent']) }}%)"></span>
            @endif
            @endforeach
        </div>
        <div class="me-status-legend">
            @foreach ($statusSegments as $segment)
            <span class="is-{{ $segment['key'] }}"><i></i>{{ $segment['label'] }} <strong>{{ $segment['count'] }}</strong></span>
            @endforeach
        </div>
    </div>
    @foreach (($extraKpis ?? []) as $kpi)
    <div class="me-kpi is-insight">
        <div class="me-kpi-label">{{ $kpi['label'] }}</div>
        <div class="me-kpi-value">{{ $kpi['value'] }}</div>
        <div class="me-kpi-meta">{{ $kpi['meta'] }}</div>
    </div>
    @endforeach
</div>

@unless ($hideAnalysisCharts ?? false)
<div class="me-charts">
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">{{ $analysis['chart']['title'] ?? __('Achievement by indicator') }}</div>
        @if (($analysis['chart']['caption'] ?? '') !== '')
        <p class="text-muted small mb-2">{{ $analysis['chart']['caption'] }}</p>
        @endif
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">{{ __('No indicators to chart.') }}</p>
        @elseif (($analysis['chart']['labels'] ?? []) === [])
        <p class="mb-0 text-muted">{{ __('No scored indicators to chart yet.') }}</p>
        @else
        <div class="me-bar-chart">
            <canvas id="report-achievement-chart"></canvas>
        </div>
        @endif
    </div>
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">{{ __('Status mix') }}</div>
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">{{ __('No status data yet.') }}</p>
        @else
        <div class="me-status-chart">
            <canvas id="report-status-chart"></canvas>
        </div>
        @endif
    </div>
</div>
@endunless
