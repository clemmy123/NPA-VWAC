<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesReportRows;
use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Services\IndicatorPerformanceService;
use App\Support\AdminLocationLevel;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GeneralReportController extends Controller
{
    use PaginatesReportRows;
    use ResolvesReportPeriod;

    public const array FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'frequency' => ['nullable', 'in:'.implode(',', self::FREQUENCIES)],
            'month' => ['nullable', 'date', 'before_or_equal:today'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'thematic_area_id' => ['nullable', 'integer', 'exists:thematic_areas,id'],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $frequency = $data['frequency'] ?? 'monthly';
        $applied = $request->boolean('apply');
        $projects = $this->visibleProjects($request)->with('thematicAreas')->orderBy('name')->get();
        $visibleAreaIds = $request->user()->hasRole('Super Admin') ? null : $this->visibleThematicAreaIds($request);
        $thematicAreas = $projects->flatMap->thematicAreas
            ->when($visibleAreaIds !== null, fn ($areas) => $areas->whereIn('id', $visibleAreaIds))
            ->sortBy('name')->values();

        $selectedProject = $projects->firstWhere('id', (int) ($data['project_id'] ?? 0))
            ?? $projects->first();

        $areasForPlan = $selectedProject
            ? $thematicAreas->where('project_id', $selectedProject->id)->values()
            : $thematicAreas;

        $selectedThematicArea = $areasForPlan->firstWhere('id', (int) ($data['thematic_area_id'] ?? 0));
        $areasToAnalyse = $selectedThematicArea ? collect([$selectedThematicArea]) : $areasForPlan;

        $indicators = Indicator::query()
            ->whereIn('thematic_area_id', $thematicAreas->pluck('id')->filter())
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $selectedIndicator = $selectedThematicArea
            ? $indicators->where('thematic_area_id', $selectedThematicArea->id)
                ->firstWhere('id', (int) ($data['indicator_id'] ?? 0))
            : null;

        $month = isset($data['month']) ? Carbon::parse($data['month'])->startOfMonth() : now()->startOfMonth();
        $financialYears = FinancialYear::query()->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->get();
        $reportingPeriods = ReportingPeriod::query()->with('financialYear')->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())->orderBy('start_date')->get();

        $selectedFinancialYear = null;
        $selectedReportingPeriod = null;
        $from = null;
        $to = null;

        if ($frequency === 'monthly') {
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();
            $selectedFinancialYear = $this->financialYearCovering($financialYears, $from)
                ?? $financialYears->firstWhere('is_current', true)
                ?? $financialYears->first();
        } elseif ($frequency === 'quarterly') {
            $selectedReportingPeriod = $reportingPeriods->firstWhere('id', (int) ($data['reporting_period_id'] ?? 0))
                ?? $this->currentReportingPeriod($reportingPeriods)
                ?? $reportingPeriods->last();
            $selectedFinancialYear = $selectedReportingPeriod?->financialYear
                ?? $financialYears->firstWhere('is_current', true)
                ?? $financialYears->first();
        } else {
            $selectedFinancialYear = $financialYears->firstWhere('id', (int) ($data['financial_year_id'] ?? 0))
                ?? $financialYears->firstWhere('is_current', true)
                ?? $financialYears->first();
        }

        $rows = [];
        $analysis = null;

        if ($applied && $areasToAnalyse->isNotEmpty() && $selectedFinancialYear) {
            $indicatorList = Indicator::query()->with(['unitOfMeasure', 'thematicArea'])
                ->whereIn('thematic_area_id', $areasToAnalyse->pluck('id'))
                ->when($selectedIndicator, fn ($query) => $query->whereKey($selectedIndicator->id))
                ->orderBy('code')->orderBy('name')->get();

            foreach ($indicatorList as $indicator) {
                $rows[] = [
                    'indicator' => $indicator,
                    'performance' => $this->performanceService->summarize(
                        $indicator,
                        $selectedFinancialYear,
                        $selectedReportingPeriod,
                        $from,
                        $to,
                    ),
                    'breakdowns' => $this->indicatorBreakdowns($indicator, $selectedFinancialYear, $selectedReportingPeriod, $from, $to),
                ];
            }

            $analysis = $this->performanceService->analyse($rows);
        }

        $periodLabel = match ($frequency) {
            'monthly' => $month->format('F Y'),
            'quarterly' => trim(($selectedReportingPeriod?->financialYear?->name ?? '').' · '.($selectedReportingPeriod?->name ?? ''), ' ·'),
            default => $selectedFinancialYear?->name,
        };

        return view('reports.general', [
            'frequency' => $frequency,
            'month' => $month,
            'applied' => $applied,
            'projects' => $projects,
            'thematicAreas' => $thematicAreas,
            'indicators' => $indicators,
            'selectedProject' => $selectedProject,
            'selectedThematicArea' => $selectedThematicArea,
            'selectedIndicator' => $selectedIndicator,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'selectedFinancialYear' => $selectedFinancialYear,
            'selectedReportingPeriod' => $selectedReportingPeriod,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'analysis' => $analysis,
            'rowPaginator' => $this->paginateRows($request, $rows),
        ]);
    }

    /** @return array{locations: list<array{label: string, value: float}>, organizations: list<array{label: string, value: float}>, activities: list<array{label: string, value: float}>} */
    private function indicatorBreakdowns(
        Indicator $indicator,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod,
        ?CarbonInterface $from,
        ?CarbonInterface $to,
    ): array {
        $query = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
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

        $entries = $query->get();
        $locationRows = $entries->whereNotNull('location_level')->whereNotNull('location_id')
            ->groupBy(fn (IndicatorDataEntry $entry): string => $entry->location_level.':'.$entry->location_id)
            ->map(function (Collection $group) use ($indicator): array {
                $entry = $group->first();
                $chain = AdminLocationLevel::ancestorChain($entry->location_level, (int) $entry->location_id);
                $label = collect(AdminLocationLevel::pathToLevel($entry->location_level))
                    ->map(fn (string $level): string => $chain[$level]['name'] ?? '')
                    ->filter()->implode(' / ');

                return ['label' => $label, 'value' => $this->aggregateEntries($group, $indicator->aggregation_method)];
            })->sortBy('label')->values()->all();

        $organizationNames = Organization::query()
            ->whereIn('id', $entries->pluck('organization_id')->filter()->unique())
            ->pluck('name', 'id');
        $organizationRows = $entries->whereNotNull('organization_id')->groupBy('organization_id')
            ->map(fn (Collection $group, $id): array => [
                'label' => $organizationNames[(int) $id] ?? 'Unknown organization',
                'value' => $this->aggregateEntries($group, $indicator->aggregation_method),
            ])->sortBy('label')->values()->all();
        $activityRows = $entries->filter(fn (IndicatorDataEntry $entry): bool => filled($entry->activity_name))
            ->groupBy('activity_name')->map(fn (Collection $group, $name): array => [
                'label' => (string) $name,
                'value' => $this->aggregateEntries($group, $indicator->aggregation_method),
            ])->sortBy('label')->values()->all();

        return ['locations' => $locationRows, 'organizations' => $organizationRows, 'activities' => $activityRows];
    }

    private function aggregateEntries(Collection $entries, ?string $method): float
    {
        return (float) match ($method) {
            'average' => $entries->avg('actual_value'),
            'latest' => $entries->sortByDesc('entry_date')->sortByDesc('id')->first()?->actual_value ?? 0,
            'count' => $entries->count(),
            'max' => $entries->max('actual_value'),
            'min' => $entries->min('actual_value'),
            default => $entries->sum('actual_value'),
        };
    }
}
