<div class="chart-card me-visual-card" id="visualization">
    <div class="me-visual-head">
        <div>
            <div class="chart-card-title">{{ $visualization['title'] }}</div>
            @if ($visualization['caption'] !== '')
            <p class="text-muted small mb-0">{{ $visualization['caption'] }}</p>
            @endif
        </div>
        <div class="me-visual-actions">
            <span class="me-visual-type">{{ __($visualizationTypes[$visualization['type']]) }}</span>
            <form method="POST" action="{{ route('reports.general.pdf') }}" id="visualization-pdf-form">
                @csrf
                @foreach (array_filter([
                    'frequency' => $frequency,
                    'month' => $frequency === 'monthly' ? $month->format('Y-m') : null,
                    'reporting_period_id' => $frequency === 'quarterly' ? $selectedReportingPeriod?->id : null,
                    'financial_year_id' => $frequency === 'yearly' ? $selectedFinancialYear?->id : null,
                    'project_id' => $selectedProject?->id,
                    'thematic_area_id' => $selectedThematicArea?->id,
                    'indicator_id' => $selectedIndicator?->id,
                    'visualization' => $visualization['type'],
                ]) as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="chart_image" value="">
                <button type="submit" class="me-visual-export"><i class="mdi mdi-file-pdf-box"></i> {{ __('Export PDF') }}</button>
            </form>
        </div>
    </div>
    @if ($visualization['empty'])
    <p class="mb-0 text-muted">{{ $visualization['empty'] }}</p>
    @elseif ($visualization['type'] === 'map')
    @php($map = $visualization['map'])
    <div class="me-map">
        <svg class="me-map-svg" viewBox="0 0 {{ $map['width'] }} {{ $map['height'] }}" role="img" aria-label="{{ __('Map of Tanzania by region') }}">
            @foreach ($map['regions'] as $region)
            <path d="{{ $region['path'] }}"
                  class="me-map-region {{ $region['tracked'] ? '' : 'is-untracked' }}"
                  fill="{{ $region['tracked'] ? \App\Services\ReportVisualizationService::MAP_SHADES[$region['shade']] : '#f8fafc' }}"
                  data-region="{{ $region['name'] }}"
                  data-tracked="{{ $region['tracked'] ? 1 : 0 }}"
                  data-collections="{{ $region['collections'] }}"
                  data-indicators="{{ $region['indicators'] }}"
                  data-share="{{ $region['share'] }}"
                  data-rank="{{ $region['rank'] }}"
                  data-shade="{{ $region['shade'] }}"></path>
            @endforeach
            @foreach ($map['lakes'] as $lake)
            <path d="{{ $lake['path'] }}" class="me-map-lake"></path>
            @endforeach
            @foreach ($map['lakes'] as $lake)
            <text x="{{ $lake['label'][0] }}" y="{{ $lake['label'][1] }}" class="me-map-lake-label">{{ __($lake['name']) }}</text>
            @endforeach
            @foreach ($map['regions'] as $region)
            @if ($region['tracked'])
            @php($badgeWidth = 18 + strlen((string) $region['collections']) * 9)
            <g class="me-map-score {{ $region['collections'] > 0 ? 'has-score' : '' }}" transform="translate({{ $region['label'][0] }} {{ $region['label'][1] }})">
                <text y="-9" class="me-map-label {{ $region['shade'] >= 4 ? 'is-light' : '' }}">{{ $region['name'] }}</text>
                <rect x="{{ -$badgeWidth / 2 }}" y="3" width="{{ $badgeWidth }}" height="20" rx="10" class="me-map-badge"></rect>
                <text y="13.5" class="me-map-badge-value">{{ number_format($region['collections']) }}</text>
            </g>
            @endif
            @endforeach
        </svg>

        <div class="me-map-side">
            <div class="me-map-legend">
                <div class="me-map-legend-scale">
                    @foreach (\App\Services\ReportVisualizationService::MAP_SHADES as $shade)
                    <span style="background: {{ $shade }}"></span>
                    @endforeach
                </div>
                <div class="me-map-legend-labels">
                    <span>0</span>
                    <span>{{ $map['max'] }}</span>
                </div>
                <p class="text-muted small mb-0">{{ __('Approved collections') }}</p>
            </div>

            <div class="me-map-ranking">
                <div class="me-section-title mb-2">{{ __('Top regions') }}</div>
                @forelse (array_slice($map['ranking'], 0, 10) as $region)
                <div class="me-visual-item">
                    <span class="me-visual-item-label">{{ $region['name'] }}</span>
                    <span class="s-badge s-achievement">{{ $region['collections'] }}</span>
                </div>
                @empty
                <p class="text-muted small mb-0">{{ __('No approved collections have a region in this period yet.') }}</p>
                @endforelse
            </div>

            @if ($map['unplaced'] > 0)
            <p class="text-muted small mb-0 me-map-note">{{ trans_choice(':count approved collection has no location set.|:count approved collections have no location set.', $map['unplaced'], ['count' => $map['unplaced']]) }}</p>
            @endif
        </div>
    </div>
    @elseif ($visualization['type'] === 'list')
    <div class="me-visual-list">
        @foreach ($visualization['groups'] as $group)
        <div class="me-visual-group">
            <div class="me-visual-group-head me-status-legend">
                <span class="is-{{ $group['key'] }}"><i></i>{{ $group['label'] }} <strong>{{ count($group['items']) }}</strong></span>
            </div>
            @php($hiddenItems = array_slice($group['items'], \App\Services\ReportVisualizationService::LIST_PREVIEW))
            @forelse (array_slice($group['items'], 0, \App\Services\ReportVisualizationService::LIST_PREVIEW) as $item)
            @include('reports.partials.visualization-list-item')
            @empty
            <p class="text-muted small mb-0">{{ __('None') }}</p>
            @endforelse
            @if ($hiddenItems !== [])
            <details class="me-visual-more">
                <summary>
                    <span class="me-visual-more-open">{{ __('Show :count more', ['count' => count($hiddenItems)]) }}</span>
                    <span class="me-visual-more-close">{{ __('Show less') }}</span>
                </summary>
                <div class="me-visual-more-items">
                    @foreach ($hiddenItems as $item)
                    @include('reports.partials.visualization-list-item')
                    @endforeach
                </div>
            </details>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="me-visual-chart is-{{ $visualization['type'] }}" @if ($visualization['type'] === 'bar') style="height: {{ $visualization['chart']['height'] ?? 320 }}px" @endif>
        <canvas id="report-visual-chart"></canvas>
    </div>
    @endif
</div>
