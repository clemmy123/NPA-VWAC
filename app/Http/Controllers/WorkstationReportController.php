<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\ReportingPeriod;
use App\Services\IndicatorPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkstationReportController extends Controller
{
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
            'organization_type_id' => ['nullable', 'integer', 'exists:organization_types,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'thematic_area_id' => ['nullable', 'integer', 'exists:thematic_areas,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $frequency = $data['frequency'] ?? 'monthly';
        $applied = $request->boolean('apply');
        $search = trim((string) ($data['search'] ?? ''));

        $organizationTypes = OrganizationType::query()->where('is_active', true)->orderBy('name')->get();
        $selectedOrganizationType = $organizationTypes->firstWhere('id', (int) ($data['organization_type_id'] ?? 0));

        $organizations = Organization::query()
            ->where('is_active', true)
            ->when($selectedOrganizationType, fn ($query) => $query->where('organization_type_id', $selectedOrganizationType->id))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();

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
        $organizationIds = $organizations->pluck('id')->all();

        if ($applied && $organizations->isNotEmpty() && $areasToAnalyse->isNotEmpty() && $selectedFinancialYear) {
            $indicators = Indicator::query()
                ->with('unitOfMeasure')
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

            $analysis = $this->performanceService->analyse($rows);
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
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'selectedFinancialYear' => $selectedFinancialYear,
            'selectedReportingPeriod' => $selectedReportingPeriod,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'analysis' => $analysis,
        ]);
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
