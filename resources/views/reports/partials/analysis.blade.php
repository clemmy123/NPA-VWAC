<div class="chart-card mb-3">
    <h5 class="me-section-title mb-1">Monitoring &amp; Evaluation</h5>
    <p class="text-muted small mb-0">
        {{ ucfirst($frequency) }} analysis
        @if ($periodLabel) · {{ $periodLabel }} @endif
        @if ($selectedProject) · {{ $selectedProject->name }} @endif
        @if ($selectedThematicArea) · {{ $selectedThematicArea->name }} @endif
        @if ($selectedOrganization ?? null) · {{ $selectedOrganization->name }}
        @elseif ($workstationScopeLabel ?? null) · {{ $workstationScopeLabel }}
        @endif
    </p>
</div>

<div class="me-kpis">
    <div class="me-kpi">
        <div class="me-kpi-label">Indicators</div>
        <div class="me-kpi-value">{{ $analysis['total'] }}</div>
        <div class="me-kpi-meta">in this thematic area</div>
    </div>
    <div class="me-kpi">
        <div class="me-kpi-label">Average achievement</div>
        <div class="me-kpi-value">{{ $analysis['average_achievement'] === null ? '—' : rtrim(rtrim(number_format($analysis['average_achievement'], 1), '0'), '.').'%' }}</div>
        <div class="me-kpi-meta">of scored indicators</div>
    </div>
    <div class="me-kpi is-on-track">
        <div class="me-kpi-label">On track</div>
        <div class="me-kpi-value">{{ rtrim(rtrim(number_format($analysis['on_track_percent'], 1), '0'), '.') }}%</div>
        <div class="me-kpi-meta">{{ $analysis['on_track'] }} at or above target</div>
    </div>
    <div class="me-kpi is-at-risk">
        <div class="me-kpi-label">At risk</div>
        <div class="me-kpi-value">{{ rtrim(rtrim(number_format($analysis['at_risk_percent'], 1), '0'), '.') }}%</div>
        <div class="me-kpi-meta">{{ $analysis['at_risk'] }} between 50% and 99%</div>
    </div>
    <div class="me-kpi is-off-track">
        <div class="me-kpi-label">Off track</div>
        <div class="me-kpi-value">{{ rtrim(rtrim(number_format($analysis['off_track_percent'], 1), '0'), '.') }}%</div>
        <div class="me-kpi-meta">{{ $analysis['off_track'] }} below 50%</div>
    </div>
</div>

<div class="me-charts">
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">Achievement by indicator</div>
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">No indicators to chart.</p>
        @else
        <canvas id="report-achievement-chart" height="280"></canvas>
        @endif
    </div>
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">Status mix</div>
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">No status data yet.</p>
        @else
        <canvas id="report-status-chart" height="280"></canvas>
        @endif
    </div>
</div>

<div class="me-alerts">
    <h5 class="me-section-title">Alerts</h5>
    @forelse ($analysis['alerts'] as $alert)
    <div class="alert alert-{{ $alert['level'] }} me-alert-item">
        <strong>{{ $alert['title'] }}.</strong> {{ $alert['detail'] }}
    </div>
    @empty
    <div class="alert {{ $analysis['total'] === 0 ? 'alert-warning' : 'alert-success' }} me-alert-item mb-0">
        @if ($analysis['total'] === 0)
        <strong>No indicators.</strong> Add indicators to this thematic area to run analysis.
        @else
        <strong>No alerts.</strong> All scored indicators are on track for this period.
        @endif
    </div>
    @endforelse
</div>
