<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesReportRows;
use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\ReportingPeriod;
use App\Services\IndicatorPerformanceService;
use App\Services\WorkstationReportService;
use App\Support\AdminLocationLevel;
use App\Support\DisplayNumber;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkstationReportController extends Controller
{
    use PaginatesReportRows;
    use ResolvesReportPeriod;

    public const array FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    private const int WORKSTATIONS_PER_PAGE = 10;

    public function __construct(
        private readonly IndicatorPerformanceService $performanceService,
        private readonly WorkstationReportService $workstationReportService,
    ) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'frequency' => ['nullable', 'in:'.implode(',', self::FREQUENCIES)],
            'month' => ['nullable', 'date', 'before_or_equal:today'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'organization_type_id' => ['nullable', 'integer', 'exists:organization_types,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'thematic_area_id' => ['nullable', 'integer', 'exists:thematic_areas,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'location_level' => ['nullable', 'string', Rule::in(WorkstationReportService::LOCATION_LEVELS)],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $frequency = $data['frequency'] ?? 'monthly';
        $applied = $request->boolean('apply');
        $search = trim((string) ($data['search'] ?? ''));

        $locationLevel = $data['location_level'] ?? null;
        $locationId = isset($data['location_id']) ? (int) $data['location_id'] : null;

        if ($locationLevel === null || $locationId === null || ! AdminLocationLevel::exists($locationLevel, $locationId)) {
            $locationLevel = null;
            $locationId = null;
        }

        $locationChain = $locationLevel !== null ? AdminLocationLevel::ancestorChain($locationLevel, $locationId) : [];

        $organizationTypes = OrganizationType::query()->where('is_active', true)->orderBy('name')->get();
        $selectedOrganizationType = $organizationTypes->firstWhere('id', (int) ($data['organization_type_id'] ?? 0));

        $organizations = $this->workstationReportService->withinLocation(
            Organization::query()
                ->with('organizationType')
                ->where('is_active', true)
                ->when($selectedOrganizationType, fn ($query) => $query->where('organization_type_id', $selectedOrganizationType->id))
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')
                ->get(),
            $locationLevel,
            $locationId,
        );

        $projects = $this->visibleProjects($request)->with('thematicAreas')->orderBy('name')->get();
        $visibleAreaIds = $request->user()->hasRole('Super Admin') ? null : $this->visibleThematicAreaIds($request);
        $thematicAreas = $projects->flatMap->thematicAreas
            ->when($visibleAreaIds !== null, fn ($areas) => $areas->whereIn('id', $visibleAreaIds))
            ->sortBy('name')->values();

        $selectedOrganization = $organizations->count() === 1 ? $organizations->first() : null;
        $selectedProject = $projects->firstWhere('id', (int) ($data['project_id'] ?? 0));

        $areasForPlan = $selectedProject
            ? $thematicAreas->where('project_id', $selectedProject->id)->values()
            : $thematicAreas;

        $selectedThematicArea = $areasForPlan->firstWhere('id', (int) ($data['thematic_area_id'] ?? 0));
        $areasToAnalyse = $selectedThematicArea
            ? collect([$selectedThematicArea])
            : $areasForPlan;

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
        $insights = null;
        $organizationIds = $organizations->pluck('id')->all();

        if ($applied && $organizations->isNotEmpty() && $areasToAnalyse->isNotEmpty() && $selectedFinancialYear) {
            $indicators = Indicator::query()
                ->with(['unitOfMeasure', 'thematicArea'])
                ->whereIn('thematic_area_id', $areasToAnalyse->pluck('id'))
                ->orderBy('code')
                ->orderBy('name')
                ->get();

            foreach ($indicators as $indicator) {
                $rows[] = [
                    'indicator' => $indicator,
                    'performance' => $this->performanceService->summarize(
                        $indicator,
                        $selectedFinancialYear,
                        $selectedReportingPeriod,
                        $from,
                        $to,
                        $organizationIds,
                    ),
                ];
            }

            $rows = $this->performanceService->prioritizeOffTrackRows($rows);
            $analysis = $this->performanceService->analyse($rows);

            $indicatorIds = $indicators->pluck('id')->all();
            $comparison = $this->workstationReportService->comparison(
                $organizations,
                $indicatorIds,
                $selectedFinancialYear,
                $selectedReportingPeriod,
                $from,
                $to,
            );
            $insights = [
                'kpis' => $this->insightKpis($comparison['totals']),
                'comparison' => $comparison,
                'comparisonPaginator' => $this->paginateWorkstations($request, $comparison['rows']),
                'comparisonChart' => $this->comparisonChart($comparison['rows'], $comparison['totals']),
                'trend' => $this->workstationReportService->monthlyTrend($organizations, $indicatorIds, $selectedFinancialYear),
            ];
        }

        $periodLabel = match ($frequency) {
            'monthly' => $month->format('F Y'),
            'quarterly' => trim(($selectedReportingPeriod?->financialYear?->name ?? '').' · '.($selectedReportingPeriod?->name ?? ''), ' ·'),
            default => $selectedFinancialYear?->name,
        };

        return view('reports.workstation', [
            'frequency' => $frequency,
            'month' => $month,
            'applied' => $applied,
            'search' => $search,
            'organizationTypes' => $organizationTypes,
            'organizations' => $organizations,
            'projects' => $projects,
            'thematicAreas' => $thematicAreas,
            'selectedOrganizationType' => $selectedOrganizationType,
            'selectedOrganization' => $selectedOrganization,
            'selectedProject' => $selectedProject,
            'selectedThematicArea' => $selectedThematicArea,
            'workstationScopeLabel' => $this->workstationScopeLabel($selectedOrganizationType, $organizations, $search),
            'locationLevel' => $locationLevel,
            'locationId' => $locationId,
            'locationChain' => $locationChain,
            'locationScopeLabel' => $this->workstationReportService->locationLabel($locationChain),
            'insights' => $insights,
            'extraKpis' => $insights['kpis'] ?? [],
            'indicatorsReported' => $insights['comparison']['totals']['indicators_reported'] ?? null,
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

    /**
     * @param  array{approved: int, pending: int, rejected: int, workstations: int, reporting_workstations: int, indicators: int, indicators_reported: int}  $totals
     * @return list<array{label: string, value: string, meta: string}>
     */
    private function insightKpis(array $totals): array
    {
        return [
            [
                'label' => __('Workstations reporting'),
                'value' => __(':reporting of :total', ['reporting' => $totals['reporting_workstations'], 'total' => $totals['workstations']]),
                'meta' => __('with approved collections'),
            ],
            [
                'label' => __('Approved collections'),
                'value' => DisplayNumber::format($totals['approved']),
                'meta' => __(':pending pending · :rejected rejected', ['pending' => $totals['pending'], 'rejected' => $totals['rejected']]),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function paginateWorkstations(Request $request, array $rows): LengthAwarePaginator
    {
        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / self::WORKSTATIONS_PER_PAGE));
        $page = min(max(1, $request->integer('ws_page', 1)), $lastPage);

        return (new LengthAwarePaginator(
            collect($rows)->forPage($page, self::WORKSTATIONS_PER_PAGE)->values(),
            $total,
            self::WORKSTATIONS_PER_PAGE,
            $page,
            ['path' => $request->url(), 'pageName' => 'ws_page'],
        ))->withQueryString()->fragment('workstations');
    }

    /**
     * Ten busiest workstations by approved collections.
     *
     * @param  list<array{organization: Organization, approved: int, pending: int, rejected: int, indicators_reported: int, last_approved_at: CarbonInterface|null}>  $rows
     * @param  array{approved: int, indicators: int}  $totals
     * @return array{labels: list<string>, names: list<string>, types: list<string|null>, approved: list<int>, pending: list<int>, rejected: list<int>, indicators_reported: list<int>, last_approved: list<string|null>, total_approved: int, total_indicators: int}
     */
    private function comparisonChart(array $rows, array $totals): array
    {
        $top = array_slice(array_values(array_filter($rows, fn (array $row): bool => $row['approved'] > 0 || $row['pending'] > 0)), 0, 10);

        return [
            'labels' => array_map(fn (array $row): string => mb_strimwidth($row['organization']->name, 0, 32, '…'), $top),
            'names' => array_map(fn (array $row): string => $row['organization']->name, $top),
            'types' => array_map(fn (array $row): ?string => $row['organization']->organizationType?->name, $top),
            'approved' => array_column($top, 'approved'),
            'pending' => array_column($top, 'pending'),
            'rejected' => array_column($top, 'rejected'),
            'indicators_reported' => array_column($top, 'indicators_reported'),
            'last_approved' => array_map(fn (array $row): ?string => $row['last_approved_at']?->translatedFormat('d M Y'), $top),
            'total_approved' => $totals['approved'],
            'total_indicators' => $totals['indicators'],
        ];
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     */
    private function workstationScopeLabel(?OrganizationType $type, Collection $organizations, string $search): ?string
    {
        if ($organizations->count() === 1) {
            return $organizations->first()?->name;
        }

        if ($type) {
            return $type->name;
        }

        return $search !== '' ? $search : null;
    }
}
