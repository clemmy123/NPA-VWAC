<div class="table-card d-none d-md-block" id="results">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="text-end">{{ __('Target') }}</th>
                <th class="text-end">{{ __('Previous Result') }}</th>
                <th class="text-end">{{ __('Actual (approved)') }}</th>
                <th class="text-end">{{ __('Achievement') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowPaginator as $row)
            @php($perf = $row['performance'])
            <tr>
                <td>
                    <div class="fw-semibold">{{ $row['indicator']->name }}</div>
                    <div class="text-muted small">{{ $row['indicator']->code }}</div>
                    @if (! ($selectedThematicArea ?? null) && $row['indicator']->thematicArea)
                    <div class="text-muted small">{{ $row['indicator']->thematicArea->name }}</div>
                    @endif
                    @foreach (($row['breakdowns'] ?? []) as $breakdownType => $breakdownRows)
                        @if ($breakdownRows !== [])
                        <details class="small mt-1">
                            <summary>{{ __(ucfirst($breakdownType)) }} ({{ count($breakdownRows) }})</summary>
                            @foreach ($breakdownRows as $breakdownRow)
                            <div class="text-muted">{{ $breakdownRow['label'] }}: {{ \App\Support\DisplayNumber::format($breakdownRow['value']) }}</div>
                            @endforeach
                        </details>
                        @endif
                    @endforeach
                </td>
                <td>{{ $row['indicator']->unitOfMeasure?->name ?? '—' }}</td>
                <td class="text-end">{{ \App\Support\DisplayNumber::format($perf['target_value']) }}</td>
                <td class="text-end">{{ \App\Support\DisplayNumber::format($perf['previous_actual_value'] ?? null) }}</td>
                <td class="text-end">{{ \App\Support\DisplayNumber::format($perf['actual_value']) }}</td>
                <td class="text-end">
                    @if ($perf['achievement_percent'] === null)
                    <span class="text-muted">—</span>
                    @else
                    <span class="s-badge s-achievement">
                        {{ \App\Support\DisplayNumber::format($perf['achievement_percent']) }}%
                    </span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-file-chart-outline"></i>
                        <p>{{ __('No indicators in this thematic area.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($rowPaginator->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $rowPaginator->links() }}
    </div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($rowPaginator as $row)
    @php($perf = $row['performance'])
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="mob-card-title">{{ $row['indicator']->name }}</span>
            @if ($perf['achievement_percent'] !== null)
            <span class="s-badge s-achievement">
                {{ \App\Support\DisplayNumber::format($perf['achievement_percent']) }}%
            </span>
            @endif
        </div>
        <div class="mob-card-meta">
            <span>Target {{ \App\Support\DisplayNumber::format($perf['target_value']) }}</span>
            <span>Previous {{ \App\Support\DisplayNumber::format($perf['previous_actual_value'] ?? null) }}</span>
            <span>Actual {{ \App\Support\DisplayNumber::format($perf['actual_value']) }}</span>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-file-chart-outline"></i>
        <p>{{ __('No indicators in this thematic area.') }}</p>
    </div>
    @endforelse
    @if ($rowPaginator->hasPages())
    <div class="mt-3">
        {{ $rowPaginator->links() }}
    </div>
    @endif
</div>
