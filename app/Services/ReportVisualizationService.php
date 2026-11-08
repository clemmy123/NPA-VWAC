<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Region;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportVisualizationService
{
    /** @var array<string, string> */
    public const array TYPES = [
        'map' => 'Map',
        'bar' => 'Bar chart',
        'list' => 'List',
        'histogram' => 'Histogram',
        'pie' => 'Pie chart',
        'doughnut' => 'Doughnut chart',
        'line' => 'Line chart',
        'area' => 'Area chart',
        'radar' => 'Radar chart',
    ];

    public const string DEFAULT_TYPE = 'map';

    /** Items shown per list group before the rest collapse behind "Show more". */
    public const int LIST_PREVIEW = 5;

    /** Fill for map shades 0 (no collections) to 5 (most collections). */
    public const array MAP_SHADES = ['#eef2f7', '#dbeafe', '#93c5fd', '#60a5fa', '#2563eb', '#1e40af'];

    private const string MAP_FILE = 'data/tanzania-regions.json';

    /** @var array{width: int, height: int, regions: list<array{name: string, path: string, label: array{0: int, 1: int}}>, lakes: list<array{name: string, path: string, label: array{0: int, 1: int}}>}|null */
    private ?array $mapShapes = null;

    private const array HISTOGRAM_BANDS = [
        ['label' => '0–24%', 'min' => 0, 'max' => 25, 'color' => '#dc2626'],
        ['label' => '25–49%', 'min' => 25, 'max' => 50, 'color' => '#f87171'],
        ['label' => '50–74%', 'min' => 50, 'max' => 75, 'color' => '#d97706'],
        ['label' => '75–99%', 'min' => 75, 'max' => 100, 'color' => '#fbbf24'],
        ['label' => '100%+', 'min' => 100, 'max' => null, 'color' => '#22c55e'],
    ];

    private const int RADAR_MAX_POINTS = 12;

    /**
     * Summary visual for the general report in the shape the chosen type needs.
     *
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string|null}}>  $rows
     * @param  array<string, mixed>  $analysis
     * @return array{type: string, title: string, caption: string, empty: string|null, chart: array<string, mixed>, groups: list<array{key: string, label: string, items: list<array{label: string, percent: float|null}>}>, map: array<string, mixed>|null}
     */
    public function build(
        string $type,
        array $rows,
        array $analysis,
        FinancialYear $financialYear,
        CarbonInterface $until,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $type = array_key_exists($type, self::TYPES) ? $type : self::DEFAULT_TYPE;

        $visual = match ($type) {
            'map' => $this->regionMapVisual($rows, $financialYear, $reportingPeriod, $from, $to),
            'list' => $this->listVisual($rows),
            'histogram' => $this->histogramVisual($rows),
            'pie', 'doughnut' => $this->statusVisual($analysis),
            'line' => $this->achievementTrendVisual($rows, $financialYear, $until),
            'area' => $this->collectionsTrendVisual($rows, $financialYear, $until),
            'radar' => $this->radarVisual($analysis),
            default => $this->barVisual($analysis),
        };

        return $visual + ['type' => $type, 'empty' => null, 'chart' => [], 'groups' => [], 'map' => null];
    }

    /**
     * Approved collections per Tanzania region for the report period. Each collection
     * is placed by its own location, falling back to its workstation's location.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function regionMapVisual(array $rows, FinancialYear $financialYear, ?ReportingPeriod $reportingPeriod, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $query = IndicatorDataEntry::query()
            ->with('organization:id,location_level,location_id')
            ->whereIn('indicator_id', collect($rows)->pluck('indicator.id')->all())
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved');

        if ($reportingPeriod) {
            $query->where(function (Builder $inner) use ($reportingPeriod): void {
                $inner->where('reporting_period_id', $reportingPeriod->id)
                    ->orWhere(function (Builder $dated) use ($reportingPeriod): void {
                        $dated->whereNull('reporting_period_id')
                            ->whereBetween('entry_date', [$reportingPeriod->start_date, $reportingPeriod->end_date]);
                    });
            });
        }

        if ($from && $to) {
            $query->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);
        }

        $regionNames = Region::query()->pluck('name', 'region_id');
        $regionIdsByLocation = [];
        $stats = [];
        $unplaced = 0;

        foreach ($query->get(['id', 'indicator_id', 'organization_id', 'location_level', 'location_id']) as $entry) {
            [$level, $id] = $entry->location_level && $entry->location_id
                ? [$entry->location_level, (int) $entry->location_id]
                : [$entry->organization?->location_level, (int) $entry->organization?->location_id];

            $regionId = null;

            if ($level && $id && AdminLocationLevel::isValidLevel($level)) {
                $regionIdsByLocation[$level.':'.$id] ??= AdminLocationLevel::ancestorChain($level, $id)['region']['id'] ?? null;
                $regionId = $regionIdsByLocation[$level.':'.$id];
            }

            if ($regionId === null || ! $regionNames->has($regionId)) {
                $unplaced++;

                continue;
            }

            $key = mb_strtolower($regionNames[$regionId]);
            $stats[$key]['collections'] = ($stats[$key]['collections'] ?? 0) + 1;
            $stats[$key]['indicators'][$entry->indicator_id] = true;
        }

        $trackedNames = $regionNames->map(fn (string $name): string => mb_strtolower($name))->flip();
        $maxCollections = max([0, ...array_column($stats, 'collections')]);
        $shapes = $this->mapShapes();

        $regions = array_map(function (array $shape) use ($stats, $trackedNames, $maxCollections): array {
            $key = mb_strtolower($shape['name']);
            $collections = $stats[$key]['collections'] ?? 0;

            return [
                'name' => $shape['name'],
                'path' => $shape['path'],
                'label' => $shape['label'],
                'tracked' => $trackedNames->has($key),
                'collections' => $collections,
                'indicators' => count($stats[$key]['indicators'] ?? []),
                'shade' => $collections === 0 ? 0 : max(1, (int) ceil($collections / $maxCollections * 5)),
            ];
        }, $shapes['regions']);

        $ranking = collect($regions)->where('collections', '>', 0)
            ->sortBy([['collections', 'desc'], ['name', 'asc']])
            ->map(fn (array $region): array => [
                'name' => $region['name'],
                'collections' => $region['collections'],
                'indicators' => $region['indicators'],
            ])
            ->values()->all();

        $placed = array_sum(array_column($ranking, 'collections'));
        $ranks = array_flip(array_column($ranking, 'name'));
        $regions = array_map(fn (array $region): array => $region + [
            'rank' => isset($ranks[$region['name']]) ? $ranks[$region['name']] + 1 : null,
            'share' => $placed > 0 ? round($region['collections'] / $placed * 100, 1) : 0.0,
        ], $regions);

        return [
            'title' => __('Collections by region'),
            'caption' => __('Approved collections in each region for this period. Darker regions have more collections.'),
            'map' => [
                'width' => $shapes['width'],
                'height' => $shapes['height'],
                'regions' => $regions,
                'lakes' => $shapes['lakes'] ?? [],
                'ranking' => $ranking,
                'placed' => $placed,
                'unplaced' => $unplaced,
                'max' => $maxCollections,
            ],
        ];
    }

    /** @return array{width: int, height: int, regions: list<array{name: string, path: string, label: array{0: int, 1: int}}>, lakes: list<array{name: string, path: string, label: array{0: int, 1: int}}>} */
    private function mapShapes(): array
    {
        return $this->mapShapes ??= json_decode(file_get_contents(resource_path(self::MAP_FILE)), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param  array<string, mixed>  $analysis */
    private function barVisual(array $analysis): array
    {
        return [
            'title' => $analysis['chart']['title'],
            'caption' => $analysis['chart']['caption'],
            'empty' => $analysis['chart']['labels'] === [] ? __('No scored indicators to chart yet.') : null,
            'chart' => [
                'labels' => $analysis['chart']['labels'],
                'titles' => $analysis['chart']['full_labels'] ?? $analysis['chart']['labels'],
                'values' => $analysis['chart']['values'],
                'colors' => $analysis['chart']['bar_colors'],
                'height' => $analysis['chart']['height'],
            ],
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function listVisual(array $rows): array
    {
        $groups = [
            'off-track' => ['key' => 'off-track', 'label' => __('Off track'), 'items' => []],
            'at-risk' => ['key' => 'at-risk', 'label' => __('At risk'), 'items' => []],
            'on-track' => ['key' => 'on-track', 'label' => __('On track'), 'items' => []],
            'no-data' => ['key' => 'no-data', 'label' => __('No data'), 'items' => []],
        ];

        foreach ($rows as $row) {
            $percent = $row['performance']['achievement_percent'];
            $key = match (true) {
                $percent === null => 'no-data',
                $percent >= 100 => 'on-track',
                $percent >= 50 => 'at-risk',
                default => 'off-track',
            };
            $groups[$key]['items'][] = [
                'label' => $this->indicatorLabel($row['indicator']),
                'percent' => $percent,
            ];
        }

        return [
            'title' => __('Indicator summary'),
            'caption' => __('Indicators grouped by status, weakest first.'),
            'empty' => $rows === [] ? __('No indicators to chart.') : null,
            'groups' => array_values($groups),
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function histogramVisual(array $rows): array
    {
        $counts = array_fill(0, count(self::HISTOGRAM_BANDS), 0);
        $unscored = 0;

        foreach ($rows as $row) {
            $percent = $row['performance']['achievement_percent'];

            if ($percent === null) {
                $unscored++;

                continue;
            }

            foreach (self::HISTOGRAM_BANDS as $index => $band) {
                if ($percent >= $band['min'] && ($band['max'] === null || $percent < $band['max'])) {
                    $counts[$index]++;
                    break;
                }
            }
        }

        return [
            'title' => __('Achievement distribution'),
            'caption' => __('Number of indicators in each achievement band. :count have no score yet.', ['count' => $unscored]),
            'empty' => array_sum($counts) === 0 ? __('No scored indicators to chart yet.') : null,
            'chart' => [
                'labels' => array_column(self::HISTOGRAM_BANDS, 'label'),
                'values' => $counts,
                'colors' => array_column(self::HISTOGRAM_BANDS, 'color'),
            ],
        ];
    }

    /** @param  array<string, mixed>  $analysis */
    private function statusVisual(array $analysis): array
    {
        return [
            'title' => __('Status mix'),
            'caption' => __('Share of indicators by status.'),
            'empty' => $analysis['total'] === 0 ? __('No status data yet.') : null,
            'chart' => [
                'labels' => $analysis['chart']['status_labels'],
                'values' => $analysis['chart']['status_values'],
                'colors' => ['#22c55e', '#d97706', '#dc2626', '#94a3b8'],
            ],
        ];
    }

    /** @param  array<string, mixed>  $analysis */
    private function radarVisual(array $analysis): array
    {
        $labels = array_slice($analysis['chart']['labels'], 0, self::RADAR_MAX_POINTS);

        return [
            'title' => __('Achievement radar'),
            'caption' => $analysis['chart']['title'],
            'empty' => count($labels) < 3 ? __('A radar chart needs at least three scored items.') : null,
            'chart' => [
                'labels' => array_map(fn (string $label): string => mb_strimwidth($label, 0, 28, '…'), $labels),
                'titles' => array_slice($analysis['chart']['full_labels'] ?? $labels, 0, self::RADAR_MAX_POINTS),
                'values' => array_slice($analysis['chart']['values'], 0, self::RADAR_MAX_POINTS),
            ],
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function achievementTrendVisual(array $rows, FinancialYear $financialYear, CarbonInterface $until): array
    {
        $months = $this->months($financialYear, $until);
        $indicators = collect($rows)->pluck('indicator');
        $targets = IndicatorTarget::query()
            ->whereIn('indicator_id', $indicators->pluck('id'))
            ->where('financial_year_id', $financialYear->id)
            ->selectRaw('indicator_id, SUM(target_value) as total_target')
            ->groupBy('indicator_id')
            ->pluck('total_target', 'indicator_id')
            ->map(fn ($value): float => (float) $value)
            ->filter(fn (float $value): bool => $value > 0);
        $entriesByIndicator = $this->approvedEntries($indicators->pluck('id')->all(), $financialYear, $until)
            ->groupBy('indicator_id');

        $values = [];

        foreach ($months as $month) {
            $monthEnd = $month->copy()->endOfMonth();
            $percents = [];

            foreach ($indicators as $indicator) {
                $target = $targets->get($indicator->id);
                $entries = ($entriesByIndicator->get($indicator->id) ?? collect())
                    ->filter(fn (IndicatorDataEntry $entry): bool => $entry->entry_date !== null && $entry->entry_date->lessThanOrEqualTo($monthEnd));

                if ($target === null || $entries->isEmpty()) {
                    continue;
                }

                $percents[] = ($this->aggregate($entries, $indicator->aggregation_method) / $target) * 100;
            }

            $values[] = $percents === [] ? null : round(array_sum($percents) / count($percents), 1);
        }

        return [
            'title' => __('Achievement trend'),
            'caption' => __('Average cumulative achievement of scored indicators by month, :year.', ['year' => $financialYear->name]),
            'empty' => array_filter($values, fn (?float $value): bool => $value !== null) === [] ? __('No approved collections in this financial year yet.') : null,
            'chart' => [
                'labels' => $months->map(fn (Carbon $month): string => $month->translatedFormat('M Y'))->all(),
                'values' => $values,
            ],
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function collectionsTrendVisual(array $rows, FinancialYear $financialYear, CarbonInterface $until): array
    {
        $months = $this->months($financialYear, $until);
        $byMonth = $this->approvedEntries(collect($rows)->pluck('indicator.id')->all(), $financialYear, $until)
            ->groupBy(fn (IndicatorDataEntry $entry): string => $entry->entry_date?->format('Y-m') ?? '');
        $values = $months->map(fn (Carbon $month): int => ($byMonth->get($month->format('Y-m')) ?? collect())->count())->all();

        return [
            'title' => __('Collections trend'),
            'caption' => __('Approved collections per month, :year.', ['year' => $financialYear->name]),
            'empty' => array_sum($values) === 0 ? __('No approved collections in this financial year yet.') : null,
            'chart' => [
                'labels' => $months->map(fn (Carbon $month): string => $month->translatedFormat('M Y'))->all(),
                'values' => $values,
            ],
        ];
    }

    /** @return Collection<int, Carbon> */
    private function months(FinancialYear $financialYear, CarbonInterface $until): Collection
    {
        $start = Carbon::parse($financialYear->start_date)->startOfMonth();
        $end = Carbon::parse($financialYear->end_date)->startOfMonth()
            ->min(Carbon::parse($until)->startOfMonth())
            ->min(now()->startOfMonth());
        $months = collect();

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $months->push($month->copy());
        }

        return $months;
    }

    /**
     * @param  list<int>  $indicatorIds
     * @return Collection<int, IndicatorDataEntry>
     */
    private function approvedEntries(array $indicatorIds, FinancialYear $financialYear, CarbonInterface $until): Collection
    {
        return IndicatorDataEntry::query()
            ->whereIn('indicator_id', $indicatorIds)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved')
            ->whereDate('entry_date', '<=', $until->toDateString())
            ->get(['id', 'indicator_id', 'actual_value', 'entry_date']);
    }

    /** @param  Collection<int, IndicatorDataEntry>  $entries */
    private function aggregate(Collection $entries, ?string $method): float
    {
        return (float) match ($method) {
            'average' => $entries->avg('actual_value'),
            'latest' => $entries->sortBy([['entry_date', 'desc'], ['id', 'desc']])->first()?->actual_value,
            'count' => $entries->count(),
            'max' => $entries->max('actual_value'),
            'min' => $entries->min('actual_value'),
            default => $entries->sum('actual_value'),
        };
    }

    private function indicatorLabel(Indicator $indicator): string
    {
        return trim(($indicator->code ? $indicator->code.' · ' : '').$indicator->name);
    }
}
