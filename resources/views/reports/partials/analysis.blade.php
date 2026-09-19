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
    </p>
</div>

<div class="me-kpis">
    <div class="me-kpi">
        <div class="me-kpi-label">{{ __('Indicators') }}</div>
        <div class="me-kpi-value">{{ $analysis['total'] }}</div>
        <div class="me-kpi-meta">
            @if ($selectedIndicator ?? null)
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
    <div class="me-kpi is-on-track">
        <div class="me-kpi-label">{{ __('On track') }}</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['on_track_percent']) }}%</div>
        <div class="me-kpi-meta">{{ __(':count at or above target', ['count' => $analysis['on_track']]) }}</div>
    </div>
    <div class="me-kpi is-at-risk">
        <div class="me-kpi-label">{{ __('At risk') }}</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['at_risk_percent']) }}%</div>
        <div class="me-kpi-meta">{{ __(':count between 50% and 99%', ['count' => $analysis['at_risk']]) }}</div>
    </div>
    <div class="me-kpi is-off-track">
        <div class="me-kpi-label">{{ __('Off track') }}</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['off_track_percent']) }}%</div>
        <div class="me-kpi-meta">{{ __(':count below 50%', ['count' => $analysis['off_track']]) }}</div>
    </div>
</div>

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
