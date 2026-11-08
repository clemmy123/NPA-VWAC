@php
    $trend = $insights['trend'];
    $comparisonChart = $insights['comparisonChart'];
    $comparisonPaginator = $insights['comparisonPaginator'];
@endphp

<div class="me-charts">
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">{{ __('Collections trend') }}</div>
        <p class="text-muted small mb-2">{{ __('Approved and pending collections per month in :year.', ['year' => $selectedFinancialYear->name]) }}</p>
        @if (array_sum($trend['approved']) + array_sum($trend['pending']) === 0)
        <p class="mb-0 text-muted">{{ __('No collections in this financial year yet.') }}</p>
        @else
        <div class="me-bar-chart">
            <canvas id="report-trend-chart"></canvas>
        </div>
        @endif
    </div>
    <div class="chart-card me-chart-card">
        <div class="chart-card-title">{{ __('Workstation comparison') }}</div>
        <p class="text-muted small mb-2">{{ __('Top workstations by approved collections in this period.') }}</p>
        @if ($comparisonChart['labels'] === [])
        <p class="mb-0 text-muted">{{ __('No workstation has collections in this period.') }}</p>
        @else
        <div class="me-bar-chart">
            <canvas id="report-workstation-chart"></canvas>
        </div>
        @endif
    </div>
</div>

<h5 class="me-section-title" id="workstations">{{ __('Workstations') }}</h5>
<div class="table-card d-none d-md-block">
    <table class="table mb-0 ws-compare-table">
        <thead>
            <tr>
                <th>{{ __('Workstation') }}</th>
                <th>{{ __('Location') }}</th>
                <th class="text-end">{{ __('Approved') }}</th>
                <th class="text-end">{{ __('Pending') }}</th>
                <th class="text-end">{{ __('Rejected') }}</th>
                <th class="text-end">{{ __('Indicators') }}</th>
                <th class="text-end">{{ __('Last approved') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comparisonPaginator as $workstation)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $workstation['organization']->name }}</div>
                    <div class="text-muted small">{{ $workstation['organization']->organizationType?->name ?? '—' }}</div>
                </td>
                <td>{{ $workstation['organization']->locationName() ?? '—' }}</td>
                <td class="text-end">
                    <span class="s-badge {{ $workstation['approved'] > 0 ? 's-achievement' : 's-default' }}">{{ $workstation['approved'] }}</span>
                </td>
                <td class="text-end">{{ $workstation['pending'] }}</td>
                <td class="text-end">{{ $workstation['rejected'] }}</td>
                <td class="text-end">{{ $workstation['indicators_reported'] }} / {{ $insights['comparison']['totals']['indicators'] }}</td>
                <td class="text-end">{{ $workstation['last_approved_at']?->format('d M Y') ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-domain"></i>
                        <p>{{ __('No workstations match these filters.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($comparisonPaginator->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $comparisonPaginator->links() }}
    </div>
    @endif
</div>

<div class="d-md-none mb-3">
    @foreach ($comparisonPaginator as $workstation)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="mob-card-title">{{ $workstation['organization']->name }}</span>
            <span class="s-badge {{ $workstation['approved'] > 0 ? 's-achievement' : 's-default' }}">{{ $workstation['approved'] }} {{ __('Approved') }}</span>
        </div>
        <div class="mob-card-meta">
            <span>{{ $workstation['organization']->locationName() ?? '—' }}</span>
            <span>{{ __('Pending') }} {{ $workstation['pending'] }}</span>
            <span>{{ __('Indicators') }} {{ $workstation['indicators_reported'] }} / {{ $insights['comparison']['totals']['indicators'] }}</span>
        </div>
    </div>
    @endforeach
    @if ($comparisonPaginator->hasPages())
    <div class="mt-3">
        {{ $comparisonPaginator->links() }}
    </div>
    @endif
</div>
