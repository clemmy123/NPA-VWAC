<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use Illuminate\Database\Eloquent\Builder;

class IndicatorPerformanceService
{
    /**
     * Target vs. approved-actual for one indicator in one financial year, aggregated
     * according to the indicator's own `aggregation_method` — dashboards must never
     * assume every indicator sums its entries.
     *
     * @return array{target_value: float|null, actual_value: float|null, achievement_percent: float|null, aggregation_method: string}
     */
    public function summarize(Indicator $indicator, FinancialYear $financialYear): array
    {
        $targetValue = $this->targetValue($indicator, $financialYear);
        $actualValue = $this->actualValue($indicator, $financialYear);

        return [
            'target_value' => $targetValue,
            'actual_value' => $actualValue,
            'achievement_percent' => ($targetValue !== null && $targetValue > 0 && $actualValue !== null)
                ? round(($actualValue / $targetValue) * 100, 2)
                : null,
            'aggregation_method' => $indicator->aggregation_method,
        ];
    }

    private function targetValue(Indicator $indicator, FinancialYear $financialYear): ?float
    {
        $query = IndicatorTarget::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id);

        return (clone $query)->exists() ? (float) (clone $query)->sum('target_value') : null;
    }

    private function actualValue(Indicator $indicator, FinancialYear $financialYear): ?float
    {
        $query = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved');

        if (! (clone $query)->exists()) {
            return $indicator->aggregation_method === 'count' ? 0.0 : null;
        }

        return $this->aggregate(clone $query, $indicator->aggregation_method);
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
