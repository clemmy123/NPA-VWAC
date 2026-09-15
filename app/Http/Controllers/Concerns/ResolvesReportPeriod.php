<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FinancialYear;
use App\Models\Project;
use App\Models\Indicator;
use App\Models\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesReportPeriod
{
    private function visibleProjects(Request $request): Builder
    {
        $query = Project::query();

        if (! $request->user()->hasRole('Super Admin')) {
            $user = $request->user();
            $visibleThematicAreaIds = $this->visibleThematicAreaIds($request);
            $query->where(function (Builder $inner) use ($user, $visibleThematicAreaIds): void {
                $inner->whereIn('id', $user->assignedProjectIds())
                    ->orWhereHas('thematicAreas', fn (Builder $areas) => $areas
                        ->whereIn('id', $visibleThematicAreaIds));
            });
        }

        return $query;
    }

    /** @return list<int> */
    private function visibleThematicAreaIds(Request $request): array
    {
        $user = $request->user();
        $viaIndicators = Indicator::query()
            ->whereIn('id', $user->assignedIndicatorIds())
            ->pluck('thematic_area_id')
            ->all();

        return array_values(array_unique([...$user->visibleThematicAreaIds(), ...$viaIndicators]));
    }

    /**
     * @param  Collection<int, FinancialYear>  $financialYears
     */
    private function financialYearCovering(Collection $financialYears, Carbon $date): ?FinancialYear
    {
        return $financialYears->first(
            fn (FinancialYear $year): bool => $year->start_date && $year->end_date
                && $date->betweenIncluded($year->start_date, $year->end_date)
        );
    }

    /**
     * @param  Collection<int, ReportingPeriod>  $reportingPeriods
     */
    private function currentReportingPeriod(Collection $reportingPeriods): ?ReportingPeriod
    {
        $today = now()->startOfDay();

        return $reportingPeriods->first(
            fn (ReportingPeriod $period): bool => $period->start_date && $period->end_date
                && $today->betweenIncluded($period->start_date, $period->end_date)
        );
    }
}
