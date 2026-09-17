<div class="chart-card mb-3">
    <h5 class="me-section-title mb-1">Monitoring &amp; Evaluation</h5>
    <p class="text-muted small mb-0">
        {{ ucfirst($frequency) }} analysis
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
        <div class="me-kpi-label">Indicators</div>
        <div class="me-kpi-value">{{ $analysis['total'] }}</div>
        <div class="me-kpi-meta">
            @if ($selectedIndicator ?? null)
            this indicator
            @elseif ($selectedThematicArea ?? null)
            in this thematic area
            @else
            across all thematic areas
            @endif
        </div>
    </div>
    <div class="me-kpi">
        <div class="me-kpi-label">Average achievement</div>
        <div class="me-kpi-value">{{ $analysis['average_achievement'] === null ? '—' : \App\Support\DisplayNumber::format($analysis['average_achievement']).'%' }}</div>
        <div class="me-kpi-meta">of scored indicators</div>
    </div>
    <div class="me-kpi is-on-track">
        <div class="me-kpi-label">On track</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['on_track_percent']) }}%</div>
        <div class="me-kpi-meta">{{ $analysis['on_track'] }} at or above target</div>
    </div>
    <div class="me-kpi is-at-risk">
        <div class="me-kpi-label">At risk</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['at_risk_percent']) }}%</div>
        <div class="me-kpi-meta">{{ $analysis['at_risk'] }} between 50% and 99%</div>
    </div>
    <div class="me-kpi is-off-track">
        <div class="me-kpi-label">Off track</div>
        <div class="me-kpi-value">{{ \App\Support\DisplayNumber::format($analysis['off_track_percent']) }}%</div>
        <div class="me-kpi-meta">{{ $analysis['off_track'] }} below 50%</div>
    </div>
</div>

<div class="me-charts">
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">{{ $analysis['chart']['title'] ?? 'Achievement by indicator' }}</div>
        @if (($analysis['chart']['caption'] ?? '') !== '')
        <p class="text-muted small mb-2">{{ $analysis['chart']['caption'] }}</p>
        @endif
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">No indicators to chart.</p>
        @else
        <div class="me-bar-chart" style="height: {{ $analysis['chart']['height'] ?? 280 }}px">
            <canvas id="report-achievement-chart"></canvas>
        </div>
        @endif
    </div>
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">Status mix</div>
        @if ($analysis['total'] === 0)
        <p class="mb-0 text-muted">No status data yet.</p>
        @else
        <div class="me-status-chart">
            <canvas id="report-status-chart"></canvas>
        </div>
        @endif
    </div>
</div>
