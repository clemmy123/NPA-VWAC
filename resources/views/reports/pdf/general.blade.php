@use('App\Support\DisplayNumber')
@use('App\Support\GradientImage')
@php
    $brandGradient = GradientImage::dataUri(['#0f6fbf', '#188ae2', '#3b82f6'], 800, 4);
    $softGradient = GradientImage::dataUri(['#e0efff', '#ffffff'], 4, 140, false);
    $statusOf = function (?float $percent): array {
        return match (true) {
            $percent === null => ['key' => 'no-data', 'label' => __('No data')],
            $percent >= 100 => ['key' => 'on-track', 'label' => __('On track')],
            $percent >= 50 => ['key' => 'at-risk', 'label' => __('At risk')],
            default => ['key' => 'off-track', 'label' => __('Off track')],
        };
    };
    $statusCounts = [
        ['key' => 'on-track', 'label' => __('On track'), 'count' => $analysis['on_track']],
        ['key' => 'at-risk', 'label' => __('At risk'), 'count' => $analysis['at_risk']],
        ['key' => 'off-track', 'label' => __('Off track'), 'count' => $analysis['off_track']],
        ['key' => 'no-data', 'label' => __('No data'), 'count' => $analysis['no_data']],
    ];
    $type = $visualization['type'];
    $chart = $visualization['chart'];
    $chartTotal = array_sum(array_map('floatval', array_filter($chart['values'] ?? [], 'is_numeric')));
    $imageWidth = match ($type) {
        'map' => '470px',
        'pie', 'doughnut', 'radar' => '400px',
        default => '100%',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('General Report') }} · {{ $periodLabel }}</title>
<style>
    @page { margin: 28px 34px 56px 34px; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1e293b; margin: 0; }
    .brand-bar { height: 6px; background-color: #188ae2; background-image: url("{{ $brandGradient }}"); background-repeat: repeat-y; border-radius: 3px; }
    .letterhead { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .letterhead td { vertical-align: middle; }
    .letterhead .coat { width: 82px; }
    .letterhead .coat img { width: 74px; height: auto; }
    .letterhead .spacer { width: 82px; }
    .ministry { text-align: center; }
    .ministry-name { font-size: 14px; font-weight: bold; color: #0f3d6e; letter-spacing: 0.4px; line-height: 1.35; }
    .system-name { margin-top: 4px; font-size: 10px; font-weight: bold; color: #188ae2; letter-spacing: 2px; }
    .rule { height: 2px; margin: 10px 0 14px; background-color: #188ae2; background-image: url("{{ $brandGradient }}"); background-repeat: repeat-y; }

    .banner { padding: 14px 18px; border-radius: 10px; color: #ffffff; background-color: #188ae2; background-image: url("{{ $brandGradient }}"); background-repeat: repeat-y; }
    .banner-kicker { font-size: 8.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #dbeafe; }
    .banner-title { margin-top: 3px; font-size: 16px; font-weight: bold; }
    .banner-meta { margin-top: 4px; font-size: 9px; color: #e0f2fe; }

    .filters { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 12px; border: 1px solid #dbeafe; border-radius: 8px; background-color: #f8fbff; }
    .filters td { padding: 6px 10px; width: 25%; vertical-align: top; }
    .filters .label { display: block; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; }
    .filters .value { display: block; margin-top: 2px; font-size: 9.5px; font-weight: bold; color: #0f172a; }

    .kpis { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .kpis td { padding: 0 0 0 8px; vertical-align: top; }
    .kpis td:first-child { padding-left: 0; }
    .kpi { padding: 10px 12px; border: 1px solid #cfe3fb; border-radius: 10px; background-color: #ffffff; background-image: url("{{ $softGradient }}"); background-repeat: repeat-x; vertical-align: top; }
    .kpi-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; color: #475569; }
    .kpi-value { margin-top: 4px; font-size: 20px; font-weight: bold; color: #188ae2; }
    .kpi-meta { margin-top: 2px; font-size: 8px; color: #64748b; }
    .status-list { margin-top: 8px; }
    .status-item { width: 46%; margin: 0 0 5px; font-size: 9.5px; }
    .results { page-break-before: always; }
    .status-item { display: inline-block; margin-right: 10px; font-size: 9px; color: #334155; }
    .dot { display: inline-block; width: 7px; height: 7px; border-radius: 4px; margin-right: 3px; }

    .section { margin-top: 18px; }
    .section-title { padding: 6px 10px; border-left: 4px solid #188ae2; border-radius: 0 6px 6px 0; background-color: #eff6ff; font-size: 11px; font-weight: bold; color: #0f3d6e; text-transform: uppercase; letter-spacing: 0.6px; }
    .section-caption { margin: 6px 0 0; font-size: 9px; color: #64748b; }
    .visual { margin-top: 10px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; text-align: center; }
    .visual img { height: auto; }
    .muted { color: #64748b; }

    table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.data th { padding: 6px 8px; font-size: 8.5px; text-align: left; color: #ffffff; background-color: #188ae2; }
    table.data th:first-child { border-top-left-radius: 6px; }
    table.data th:last-child { border-top-right-radius: 6px; }
    table.data td { padding: 5px 8px; font-size: 9px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    table.data tr:nth-child(even) td { background-color: #f8fbff; }
    table.data .num { text-align: right; white-space: nowrap; }
    table.data .code { display: block; font-size: 7.5px; color: #64748b; }
    .achievement { color: #188ae2; font-weight: bold; }
    .chip { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 8px; font-weight: bold; white-space: nowrap; }

    .is-on-track { color: #15803d; }
    .is-at-risk { color: #b45309; }
    .is-off-track { color: #b91c1c; }
    .is-no-data { color: #64748b; }
    .chip.is-on-track { background-color: #dcfce7; }
    .chip.is-at-risk { background-color: #fef3c7; }
    .chip.is-off-track { background-color: #fee2e2; }
    .chip.is-no-data { background-color: #f1f5f9; }
    .dot.is-on-track { background-color: #22c55e; }
    .dot.is-at-risk { background-color: #d97706; }
    .dot.is-off-track { background-color: #dc2626; }
    .dot.is-no-data { background-color: #94a3b8; }

    .group-title { margin-top: 12px; font-size: 10px; font-weight: bold; }
    .note { margin-top: 8px; font-size: 8.5px; color: #64748b; }

    .footer { position: fixed; left: 0; right: 0; bottom: -36px; padding-top: 6px; border-top: 1px solid #dbeafe; font-size: 7.5px; color: #64748b; }
</style>
</head>
<body>
<div class="footer">
    JAMII FUATILIA · Wizara ya Maendeleo ya Jamii, Jinsia, Wanawake na Makundi Maalum ·
    {{ __('Generated :date by :name', ['date' => $generatedAt->translatedFormat('d M Y, H:i'), 'name' => $generatedBy]) }}
</div>

<div class="brand-bar"></div>
<table class="letterhead">
    <tr>
        <td class="coat"><img src="{{ $coatImage }}" alt=""></td>
        <td class="ministry">
            <div class="ministry-name">WIZARA YA MAENDELEO YA JAMII, JINSIA,<br>WANAWAKE NA MAKUNDI MAALUM</div>
            <div class="system-name">JAMII FUATILIA</div>
        </td>
        <td class="spacer"></td>
    </tr>
</table>
<div class="rule"></div>

<div class="banner">
    <div class="banner-kicker">{{ __('General Report') }} · {{ __(ucfirst($frequency)) }}</div>
    <div class="banner-title">{{ $visualization['title'] }}</div>
    <div class="banner-meta">{{ $periodLabel }} · {{ __($visualizationTypes[$type]) }}</div>
</div>

<table class="filters">
    <tr>
        <td><span class="label">{{ __('Plan') }}</span><span class="value">{{ $selectedProject?->name ?? '—' }}</span></td>
        <td><span class="label">{{ __('Thematic Area') }}</span><span class="value">{{ $selectedThematicArea?->name ?? __('All Thematic Areas') }}</span></td>
        <td><span class="label">{{ __('Indicator') }}</span><span class="value">{{ $selectedIndicator ? trim(($selectedIndicator->code ? $selectedIndicator->code.' · ' : '').$selectedIndicator->name) : __('All Indicators') }}</span></td>
        <td><span class="label">{{ __('Period') }}</span><span class="value">{{ $periodLabel ?: '—' }}</span></td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td style="width: 25%">
            <div class="kpi">
                <div class="kpi-label">{{ __('Indicators') }}</div>
                <div class="kpi-value">{{ $analysis['total'] }}</div>
                <div class="kpi-meta">{{ $selectedIndicator ? __('this indicator') : ($selectedThematicArea ? __('in this thematic area') : __('across all thematic areas')) }}</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="kpi-label">{{ __('Average achievement') }}</div>
                <div class="kpi-value">{{ $analysis['average_achievement'] === null ? '—' : DisplayNumber::format($analysis['average_achievement'], 1).'%' }}</div>
                <div class="kpi-meta">{{ __('of scored indicators') }}</div>
            </div>
        </td>
        <td>
            <div class="kpi">
                <div class="kpi-label">{{ __('Indicator status') }}</div>
                <div class="status-list">
                    @foreach ($statusCounts as $status)
                    <span class="status-item"><span class="dot is-{{ $status['key'] }}"></span>{{ $status['label'] }} <strong>{{ $status['count'] }}</strong></span>
                    @endforeach
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">{{ $visualization['title'] }}</div>
    @if ($visualization['caption'] !== '')
    <p class="section-caption">{{ $visualization['caption'] }}</p>
    @endif

    @if ($visualization['empty'])
    <div class="visual muted">{{ $visualization['empty'] }}</div>
    @elseif ($type === 'list')
        @foreach ($visualization['groups'] as $group)
        <div class="group-title is-{{ $group['key'] }}"><span class="dot is-{{ $group['key'] }}"></span>{{ $group['label'] }} ({{ count($group['items']) }})</div>
        @if ($group['items'] === [])
        <div class="note">{{ __('None') }}</div>
        @else
        <table class="data">
            <thead><tr><th style="width: 28px">#</th><th>{{ __('Indicator') }}</th><th class="num">{{ __('Achievement') }}</th></tr></thead>
            <tbody>
                @foreach ($group['items'] as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item['label'] }}</td>
                    <td class="num">@if ($item['percent'] === null)<span class="muted">—</span>@else<span class="achievement">{{ DisplayNumber::format($item['percent'], 1) }}%</span>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endforeach
    @else
        <div class="visual">
            @if ($chartImage)
            <img src="{{ $chartImage }}" style="width: {{ $imageWidth }}" alt="">
            @else
            <span class="muted">{{ __('The chart image could not be captured. The figures are listed below.') }}</span>
            @endif
        </div>

        @if ($type === 'map')
        <table class="data">
            <thead><tr><th style="width: 40px">{{ __('Rank') }}</th><th>{{ __('Region') }}</th><th class="num">{{ __('Approved collections') }}</th><th class="num">{{ __('Indicators reported') }}</th><th class="num">{{ __('Share of collections') }}</th></tr></thead>
            <tbody>
                @forelse ($visualization['map']['ranking'] as $region)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $region['name'] }}</td>
                    <td class="num achievement">{{ number_format($region['collections']) }}</td>
                    <td class="num">{{ number_format($region['indicators']) }}</td>
                    <td class="num">{{ $visualization['map']['placed'] > 0 ? DisplayNumber::format($region['collections'] / $visualization['map']['placed'] * 100, 1) : 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="5" class="muted">{{ __('No approved collections have a region in this period yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($visualization['map']['unplaced'] > 0)
        <div class="note">{{ trans_choice(':count approved collection has no location set.|:count approved collections have no location set.', $visualization['map']['unplaced'], ['count' => $visualization['map']['unplaced']]) }}</div>
        @endif
        @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 28px">#</th>
                    <th>{{ match ($type) { 'histogram' => __('Achievement band'), 'pie', 'doughnut' => __('Status'), 'line', 'area' => __('Month'), default => __('Indicator') } }}</th>
                    <th class="num">{{ match ($type) { 'histogram', 'pie', 'doughnut' => __('Indicators'), 'area' => __('Approved collections'), 'line' => __('Average achievement'), default => __('Achievement') } }}</th>
                    @if (in_array($type, ['histogram', 'pie', 'doughnut'], true))
                    <th class="num">{{ __('Share') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($chart['labels'] as $index => $label)
                @php($value = $chart['values'][$index] ?? null)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $chart['titles'][$index] ?? $label }}</td>
                    @if (in_array($type, ['bar', 'radar', 'line'], true))
                    <td class="num">@if ($value === null)<span class="muted">—</span>@else<span class="achievement">{{ DisplayNumber::format($value, 1) }}%</span>@endif</td>
                    @else
                    <td class="num achievement">{{ DisplayNumber::format($value, 0) }}</td>
                    @endif
                    @if (in_array($type, ['histogram', 'pie', 'doughnut'], true))
                    <td class="num">{{ $chartTotal > 0 ? DisplayNumber::format($value / $chartTotal * 100, 1) : 0 }}%</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @endif
</div>

<div class="section results">
    <div class="section-title">{{ __('Indicator results') }}</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">#</th>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="num">{{ __('Target') }}</th>
                <th class="num">{{ __('Actual (approved)') }}</th>
                <th class="num">{{ __('Achievement') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            @php($perf = $row['performance'])
            @php($status = $statusOf($perf['achievement_percent']))
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    {{ $row['indicator']->name }}
                    @if ($row['indicator']->code)<span class="code">{{ $row['indicator']->code }}@if (! $selectedThematicArea && $row['indicator']->thematicArea) · {{ $row['indicator']->thematicArea->name }}@endif</span>@endif
                </td>
                <td>{{ $row['indicator']->unitOfMeasure?->name ?? '—' }}</td>
                <td class="num">{{ DisplayNumber::format($perf['target_value']) }}</td>
                <td class="num">{{ DisplayNumber::format($perf['actual_value']) }}</td>
                <td class="num">@if ($perf['achievement_percent'] === null)<span class="muted">—</span>@else<span class="achievement">{{ DisplayNumber::format($perf['achievement_percent'], 1) }}%</span>@endif</td>
                <td><span class="chip is-{{ $status['key'] }}">{{ $status['label'] }}</span></td>
            </tr>
            @empty
            <tr><td colspan="7" class="muted">{{ __('No indicators in this thematic area.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
