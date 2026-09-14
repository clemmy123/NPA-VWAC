<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\ReportingPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class IndicatorPerformanceService
{
    /**
     * Target vs. approved-actual for one indicator in one financial year, aggregated
     * according to the indicator's own `aggregation_method` — dashboards must never
     * assume every indicator sums its entries.
     *
     * Optional `$reportingPeriod` / `$from`+`$to` narrow actuals (and, for a
     * quarter, prefer a period-specific target when one exists).
     *
     * @return array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}
     */
    public function summarize(
        Indicator $indicator,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $targetValue = $this->targetValue($indicator, $financialYear, $reportingPeriod);
        $actualValue = $this->actualValue($indicator, $financialYear, $reportingPeriod, $from, $to);

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
    ): ?float {
        $query = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved');

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

        if (! (clone $query)->exists()) {
            return $indicator->aggregation_method === 'count' ? 0.0 : null;
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
     *     chart: array{labels: list<string>, values: list<float>, status_labels: list<string>, status_values: list<int>}
     * }
     */
    public function analyse(array $rows): array
    {
        $onTrack = 0;
        $atRisk = 0;
        $offTrack = 0;
        $noData = 0;
        $percents = [];
        $labels = [];
        $values = [];
        $alerts = [];

        foreach ($rows as $row) {
            $indicator = $row['indicator'];
            $performance = $row['performance'];
            $label = trim(($indicator->code ? $indicator->code.' · ' : '').$indicator->name);
            $percent = $performance['achievement_percent'];

            $labels[] = $label;
            $values[] = $percent === null ? 0.0 : (float) $percent;

            if ($percent === null) {
                $noData++;

                if ($performance['target_value'] === null) {
                    $alerts[] = [
                        'level' => 'warning',
                        'title' => 'No target',
                        'detail' => $label.' has no target for this period.',
                    ];
                } elseif ($performance['actual_value'] === null) {
                    $alerts[] = [
                        'level' => 'danger',
                        'title' => 'No approved actuals',
                        'detail' => $label.' has a target but no approved collections yet.',
                    ];
                } else {
                    $alerts[] = [
                        'level' => 'warning',
                        'title' => 'Achievement not computed',
                        'detail' => $label.' cannot be scored for this period.',
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
                    'detail' => $label.' is at '.$formatted.' of target.',
                ];
            } else {
                $offTrack++;
                $alerts[] = [
                    'level' => 'danger',
                    'title' => 'Off track',
                    'detail' => $label.' is at '.$formatted.' of target.',
                ];
            }
        }

        $total = count($rows);
        usort($alerts, fn (array $left, array $right): int => ($left['level'] === 'danger' ? 0 : 1) <=> ($right['level'] === 'danger' ? 0 : 1));

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
            'alerts' => $alerts,
            'chart' => [
                'labels' => $labels,
                'values' => $values,
                'status_labels' => ['On track', 'At risk', 'Off track', 'No data'],
                'status_values' => [$onTrack, $atRisk, $offTrack, $noData],
            ],
        ];
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
}
