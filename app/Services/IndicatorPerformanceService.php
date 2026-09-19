<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class IndicatorPerformanceService
{
    /**
     * Target vs. approved-actual for one indicator in one financial year, aggregated
     * according to the indicator's own `aggregation_method` — dashboards must never
     * assume every indicator sums its entries.
     *
     * Optional `$reportingPeriod` / `$from`+`$to` narrow actuals (and, for a
     * quarter, prefer a period-specific target when one exists). Optional
     * `$organizationId` limits actuals to one workstation, or a list of them.
     *
     * @param  int|list<int>|null  $organizationId
     * @param  list<array{location_level: string|null, location_id: int|null, organization_id: int|null}>|null  $locationScopes
     * @return array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}
     */
    public function summarize(
        Indicator $indicator,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int|array|null $organizationId = null,
        ?array $locationScopes = null,
    ): array {
        $targetValue = $this->targetValue($indicator, $financialYear, $reportingPeriod);
        $actualValue = $this->actualValue($indicator, $financialYear, $reportingPeriod, $from, $to, $organizationId, $locationScopes);

        return [
            'target_value' => $targetValue,
            'actual_value' => $actualValue,
            'achievement_percent' => ($targetValue !== null && $targetValue > 0 && $actualValue !== null)
                ? round(($actualValue / $targetValue) * 100, 2)
                : null,
            'aggregation_method' => $indicator->aggregation_method,
        ];
    }

    private function targetValue(Indicator $indicator, FinancialYear $financialYear, ?ReportingPeriod $reportingPeriod = null): ?float
    {
        $query = IndicatorTarget::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id);

        if ($reportingPeriod !== null) {
            $periodQuery = (clone $query)->where('reporting_period_id', $reportingPeriod->id);

            if ($periodQuery->exists()) {
                return (float) $periodQuery->sum('target_value');
            }
        }

        return (clone $query)->exists() ? (float) (clone $query)->sum('target_value') : null;
    }

    private function actualValue(
        Indicator $indicator,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int|array|null $organizationId = null,
        ?array $locationScopes = null,
    ): ?float {
        $query = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved');

        $organizationIds = match (true) {
            is_array($organizationId) => array_values($organizationId),
            $organizationId !== null => [$organizationId],
            default => null,
        };

        if ($organizationIds !== null) {
            $query->whereIn('organization_id', $organizationIds);
        }

        if ($reportingPeriod !== null) {
            $query->where(function (Builder $inner) use ($reportingPeriod): void {
                $inner->where('reporting_period_id', $reportingPeriod->id)
                    ->orWhere(function (Builder $byDate) use ($reportingPeriod): void {
                        $byDate->whereNull('reporting_period_id')
                            ->whereDate('entry_date', '>=', $reportingPeriod->start_date)
                            ->whereDate('entry_date', '<=', $reportingPeriod->end_date);
                    });
            });
        }

        if ($from !== null && $to !== null) {
            $query->whereDate('entry_date', '>=', $from->toDateString())
                ->whereDate('entry_date', '<=', $to->toDateString());
        }

        $entries = $locationScopes === null ? null : $query->get()->filter(function (IndicatorDataEntry $entry) use ($locationScopes): bool {
            return collect($locationScopes)->contains(function (array $scope) use ($entry): bool {
                $organizationMatches = ($scope['organization_id'] ?? null) === null
                    || (int) $entry->organization_id === (int) $scope['organization_id'];
                if (! $organizationMatches) {
                    return false;
                }

                if (($scope['location_level'] ?? null) === null || ($scope['location_id'] ?? null) === null) {
                    return true;
                }

                return $entry->location_level !== null && $entry->location_id !== null
                    && AdminLocationLevel::isWithin(
                        $entry->location_level,
                        (int) $entry->location_id,
                        $scope['location_level'],
                        (int) $scope['location_id'],
                    );
            });
        });

        if (($entries !== null && $entries->isEmpty()) || ($entries === null && ! (clone $query)->exists())) {
            return $indicator->aggregation_method === 'count' ? 0.0 : null;
        }

        if ($indicator->aggregation_method === 'latest' && ($indicator->requires_location || in_array($indicator->collection_scope, ['institutional', 'mixed'], true))) {
            $entries ??= (clone $query)->orderByDesc('entry_date')->orderByDesc('id')->get();
            $latestByReportingUnit = $entries->groupBy(function (IndicatorDataEntry $entry) use ($indicator): string {
                if ($indicator->requires_location && $entry->location_level && $entry->location_id) {
                    return 'location:'.$entry->location_level.':'.$entry->location_id;
                }

                return 'organization:'.($entry->organization_id ?? 'national');
            })->map(fn ($group) => (float) $group->first()->actual_value);

            $measurementCode = $indicator->measurementType()->value('code');

            return in_array($measurementCode, ['percentage', 'ratio'], true)
                ? (float) $latestByReportingUnit->avg()
                : (float) $latestByReportingUnit->sum();
        }

        if ($entries !== null) {
            return $this->aggregateCollection($entries, $indicator->aggregation_method);
        }

        return $this->aggregate(clone $query, $indicator->aggregation_method);
    }

    /**
     * Roll indicator rows into M&E summary cards, chart series, and alerts.
     *
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>  $rows
     * @return array{
     *     total: int,
     *     on_track: int,
     *     at_risk: int,
     *     off_track: int,
     *     no_data: int,
     *     on_track_percent: float,
     *     at_risk_percent: float,
     *     off_track_percent: float,
     *     no_data_percent: float,
     *     average_achievement: float|null,
     *     alerts: list<array{level: string, title: string, detail: string}>,
     *     alert_summary: array{off_track: int, at_risk: int, no_target: int, no_actuals: int, not_computed: int},
     *     chart: array{title: string, caption: string, height: int, labels: list<string>, values: list<float>, bar_colors: list<string>, status_labels: list<string>, status_values: list<int>}
     * }
     */
    public function analyse(array $rows): array
    {
        $indicators = collect($rows)->pluck('indicator')->filter();
        (new EloquentCollection($indicators->all()))->loadMissing('thematicArea');

        $onTrack = 0;
        $atRisk = 0;
        $offTrack = 0;
        $noData = 0;
        $noTarget = 0;
        $noActuals = 0;
        $notComputed = 0;
        $percents = [];
        $alerts = [];

        foreach ($rows as $row) {
            $indicator = $row['indicator'];
            $performance = $row['performance'];
            $label = trim(($indicator->code ? $indicator->code.' · ' : '').$indicator->name);
            $percent = $performance['achievement_percent'];

            if ($percent === null) {
                $noData++;

                if ($performance['target_value'] === null) {
                    $noTarget++;
                    $alerts[] = [
                        'level' => 'warning',
                        'title' => 'No target',
                        'detail' => __(':label has no target for this period.', ['label' => $label]),
                    ];
                } elseif ($performance['actual_value'] === null) {
                    $noActuals++;
                    $alerts[] = [
                        'level' => 'danger',
                        'title' => 'No approved actuals',
                        'detail' => __(':label has a target but no approved collections yet.', ['label' => $label]),
                    ];
                } else {
                    $notComputed++;
                    $alerts[] = [
                        'level' => 'warning',
                        'title' => 'Achievement not computed',
                        'detail' => __(':label cannot be scored for this period.', ['label' => $label]),
                    ];
                }

                continue;
            }

            $percents[] = $percent;
            $formatted = $this->formatPercent($percent);

            if ($percent >= 100) {
                $onTrack++;
            } elseif ($percent >= 50) {
                $atRisk++;
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'At risk',
                    'detail' => __(':label is at :percent of target.', ['label' => $label, 'percent' => $formatted]),
                    'percent' => $percent,
                ];
            } else {
                $offTrack++;
                $alerts[] = [
                    'level' => 'danger',
                    'title' => 'Off track',
                    'detail' => __(':label is at :percent of target.', ['label' => $label, 'percent' => $formatted]),
                    'percent' => $percent,
                ];
            }
        }

        $total = count($rows);
        usort($alerts, $this->alertSort(...));

        return [
            'total' => $total,
            'on_track' => $onTrack,
            'at_risk' => $atRisk,
            'off_track' => $offTrack,
            'no_data' => $noData,
            'on_track_percent' => $this->share($onTrack, $total),
            'at_risk_percent' => $this->share($atRisk, $total),
            'off_track_percent' => $this->share($offTrack, $total),
            'no_data_percent' => $this->share($noData, $total),
            'average_achievement' => $percents === [] ? null : round(array_sum($percents) / count($percents), 1),
            'alerts' => array_map(fn (array $alert): array => [
                'level' => $alert['level'],
                'title' => __($alert['title']),
                'detail' => $alert['detail'],
            ], $alerts),
            'alert_summary' => [
                'off_track' => $offTrack,
                'at_risk' => $atRisk,
                'no_target' => $noTarget,
                'no_actuals' => $noActuals,
                'not_computed' => $notComputed,
            ],
            'chart' => $this->chartSeries($rows) + [
                'status_labels' => [__('On track'), __('At risk'), __('Off track'), __('No data')],
                'status_values' => [$onTrack, $atRisk, $offTrack, $noData],
            ],
        ];
    }

    /**
     * Put the weakest results first so the summary table opens on off-track indicators.
     *
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>  $rows
     * @return list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>
     */
    public function prioritizeOffTrackRows(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            $compared = $this->statusRank($left['performance']['achievement_percent'] ?? null)
                <=> $this->statusRank($right['performance']['achievement_percent'] ?? null);

            if ($compared !== 0) {
                return $compared;
            }

            $leftPercent = $left['performance']['achievement_percent'] ?? null;
            $rightPercent = $right['performance']['achievement_percent'] ?? null;

            if ($leftPercent !== null && $rightPercent !== null) {
                $compared = $leftPercent <=> $rightPercent;

                if ($compared !== 0) {
                    return $compared;
                }
            }

            $leftCode = mb_strtolower((string) ($left['indicator']->code ?? ''));
            $rightCode = mb_strtolower((string) ($right['indicator']->code ?? ''));
            $compared = $leftCode <=> $rightCode;

            if ($compared !== 0) {
                return $compared;
            }

            $leftName = mb_strtolower((string) $left['indicator']->name);
            $rightName = mb_strtolower((string) $right['indicator']->name);

            return $leftName <=> $rightName;
        });

        return array_values($rows);
    }

    /**
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>  $rows
     * @return array{title: string, caption: string, height: int, labels: list<string>, values: list<float>, bar_colors: list<string>}
     */
    private function chartSeries(array $rows): array
    {
        $areaIds = collect($rows)->map(fn (array $row): int => (int) ($row['indicator']->thematic_area_id ?? 0))->unique();
        $indicatorBars = $areaIds->count() <= 1;

        if ($indicatorBars) {
            $items = $this->indicatorChartItems($rows);
            $title = __('Achievement by indicator');
            $caption = '';
        } else {
            $items = $this->thematicAreaChartItems($rows);
            $title = __('Average achievement by thematic area');
            $caption = __('Mean of scored indicators in each area. Open one area for indicator bars.');
        }

        $labels = array_column($items, 'label');
        $values = array_column($items, 'value');

        return [
            'title' => $title,
            'caption' => $caption,
            'height' => max(140, min(280, max(1, count($labels)) * 36 + 48)),
            'labels' => $labels,
            'values' => $values,
            'bar_colors' => $indicatorBars
                ? array_fill(0, count($values), '#188ae2')
                : array_map(fn (float $value): string => $this->barColor($value), $values),
        ];
    }

    /**
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>  $rows
     * @return list<array{label: string, value: float}>
     */
    private function thematicAreaChartItems(array $rows): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $area = $row['indicator']->thematicArea;
            $id = $area?->id ?? 0;
            $buckets[$id] ??= ['label' => $area?->name ?? 'Unassigned', 'percents' => []];

            if ($row['performance']['achievement_percent'] !== null) {
                $buckets[$id]['percents'][] = (float) $row['performance']['achievement_percent'];
            }
        }

        $items = [];

        foreach ($buckets as $bucket) {
            $items[] = [
                'label' => $this->shortenChartLabel($bucket['label']),
                'value' => $bucket['percents'] === []
                    ? 0.0
                    : round(array_sum($bucket['percents']) / count($bucket['percents']), 1),
            ];
        }

        usort($items, fn (array $left, array $right): int => $left['value'] <=> $right['value']);

        return $items;
    }

    /**
     * @param  list<array{indicator: Indicator, performance: array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}}>  $rows
     * @return list<array{label: string, value: float}>
     */
    private function indicatorChartItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $percent = $row['performance']['achievement_percent'];

            if ($percent === null) {
                continue;
            }

            $indicator = $row['indicator'];
            $name = trim(($indicator->code ? $indicator->code.' · ' : '').$indicator->name);

            $items[] = [
                'label' => $this->shortenChartLabel($name, 34).' · '.__($this->achievementStatus($percent)),
                'value' => (float) $percent,
            ];
        }

        return $items;
    }

    private function achievementStatus(float $percent): string
    {
        if ($percent >= 100) {
            return 'On track';
        }

        if ($percent >= 50) {
            return 'At risk';
        }

        return 'Off track';
    }

    private function shortenChartLabel(string $label, int $max = 40): string
    {
        if (mb_strlen($label) <= $max) {
            return $label;
        }

        return rtrim(mb_substr($label, 0, $max - 1)).'…';
    }

    private function statusRank(?float $percent): int
    {
        if ($percent === null) {
            return 3;
        }

        if ($percent < 50) {
            return 0;
        }

        if ($percent < 100) {
            return 1;
        }

        return 2;
    }

    /**
     * @param  array{level: string, title: string, detail: string, percent?: float}  $left
     * @param  array{level: string, title: string, detail: string, percent?: float}  $right
     */
    private function alertSort(array $left, array $right): int
    {
        $rank = [
            'Off track' => 0,
            'At risk' => 1,
            'No approved actuals' => 2,
            'No target' => 3,
            'Achievement not computed' => 4,
        ];

        $compared = ($rank[$left['title']] ?? 9) <=> ($rank[$right['title']] ?? 9);

        if ($compared !== 0) {
            return $compared;
        }

        return ($left['percent'] ?? 999) <=> ($right['percent'] ?? 999);
    }

    private function barColor(float $percent): string
    {
        if ($percent <= 0) {
            return '#94a3b8';
        }

        if ($percent >= 100) {
            return '#22c55e';
        }

        return $percent >= 50 ? '#d97706' : '#dc2626';
    }

    private function share(int $count, int $total): float
    {
        return $total === 0 ? 0.0 : round(($count / $total) * 100, 1);
    }

    private function formatPercent(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2), '0'), '.').'%';
    }

    private function aggregate(Builder $query, ?string $method): ?float
    {
        $value = match ($method) {
            'average' => $query->avg('actual_value'),
            'latest' => $query->orderByDesc('entry_date')->orderByDesc('id')->value('actual_value'),
            'count' => $query->count(),
            'max' => $query->max('actual_value'),
            'min' => $query->min('actual_value'),
            default => $query->sum('actual_value'),
        };

        return $value === null ? null : (float) $value;
    }

    /** @param Collection<int, IndicatorDataEntry> $entries */
    private function aggregateCollection(Collection $entries, ?string $method): ?float
    {
        $value = match ($method) {
            'average' => $entries->avg('actual_value'),
            'latest' => $entries->sortByDesc(fn (IndicatorDataEntry $entry) => $entry->entry_date?->getTimestamp() ?? 0)->first()?->actual_value,
            'count' => $entries->count(),
            'max' => $entries->max('actual_value'),
            'min' => $entries->min('actual_value'),
            default => $entries->sum('actual_value'),
        };

        return $value === null ? null : (float) $value;
    }
}
