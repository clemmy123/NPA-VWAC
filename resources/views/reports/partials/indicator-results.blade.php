<div class="table-card d-none d-md-block" id="results">
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
                            <summary>{{ ucfirst($breakdownType) }} ({{ count($breakdownRows) }})</summary>
                            @foreach ($breakdownRows as $breakdownRow)
                            <div class="text-muted">{{ $breakdownRow['label'] }}: {{ rtrim(rtrim(number_format($breakdownRow['value'], 2), '0'), '.') }}</div>
                            @endforeach
                        </details>
                        @endif
                    @endforeach
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
    @if ($rowPaginator->hasPages())
    <div class="mt-3">
        {{ $rowPaginator->links() }}
    </div>
    @endif
</div>
