<?php

namespace App\Http\Controllers;

use App\Exports\SimpleReportExport;
use App\Http\Controllers\Concerns\PaginatesReportRows;
use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\ReportingPeriod;
use App\Services\IndicatorPerformanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkstationReportController extends Controller
{
    use PaginatesReportRows;
    use ResolvesReportPeriod;

    public const array FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $data = $this->reportData($request);

        return view('reports.workstation', $data + [
            'rowPaginator' => $this->paginateRows($request, $data['rows']),
        ]);
    }

    public function exportPreview(Request $request, string $format): View|RedirectResponse|Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $data = $this->reportData($request);

        if (! $data['applied']) {
            return redirect()->route('reports.workstation', ['frequency' => $data['frequency']])
                ->with('warning', __('Apply filters before exporting.'));
        }

        $filename = 'workstation-report-'.now()->format('Ymd-His');

        if ($request->boolean('download')) {
            if ($format === 'excel') {
                return Excel::download(new SimpleReportExport($this->excelRows($data)), $filename.'.xlsx');
            }

            return Pdf::loadView('reports.exports.simple-pdf', $data + [
                'title' => ucfirst($data['frequency']).' Workstation Report',
                'context' => $this->contextLines($data),
                'executiveSummary' => $this->executiveSummary($data),
                'sectionIntro' => $this->sectionIntro($data),
                'tableRows' => $this->tableRows($data),
            ])->setPaper('a4', 'landscape')->stream($filename.'.pdf');
        }

        return view('reports.export-preview', $data + [
            'title' => ucfirst($data['frequency']).' Workstation Report',
            'format' => $format,
            'downloadRoute' => route('reports.workstation.export', [$format] + $request->query() + ['download' => 1]),
            'context' => $this->contextLines($data),
            'executiveSummary' => $this->executiveSummary($data),
            'sectionIntro' => $this->sectionIntro($data),
            'tableRows' => $this->tableRows($data),
        ]);
    }

    private function reportData(Request $request): array
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
        }

        $periodLabel = match ($frequency) {
            'monthly' => $month->format('F Y'),
            'quarterly' => trim(($selectedReportingPeriod?->financialYear?->name ?? '').' · '.($selectedReportingPeriod?->name ?? ''), ' ·'),
            default => $selectedFinancialYear?->name,
        };

        return [
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
        ];
    }

    private function excelRows(array $data): array
    {
        return array_merge([['Indicator', 'Code', 'Thematic Area', 'Unit', 'Target', 'Actual Approved', 'Achievement Percent']], $this->tableRows($data));
    }

    private function tableRows(array $data): array
    {
        return collect($data['rows'])->map(function (array $row): array {
            $perf = $row['performance'];

            return [
                $row['indicator']->name,
                $row['indicator']->code,
                $row['indicator']->thematicArea?->name,
                $row['indicator']->unitOfMeasure?->name,
                $perf['target_value'],
                $perf['actual_value'],
                $perf['achievement_percent'] === null ? null : round((float) $perf['achievement_percent'], 2),
            ];
        })->all();
    }

    private function contextLines(array $data): array
    {
        return array_values(array_filter([
            'Period: '.($data['periodLabel'] ?: 'Not specified'),
            'Workstation scope: '.($data['workstationScopeLabel'] ?: 'All active workstations'),
            'Plan: '.($data['selectedProject']?->name ?: 'All visible plans'),
            'Thematic area: '.($data['selectedThematicArea']?->name ?: 'All thematic areas'),
        ]));
    }

    private function executiveSummary(array $data): array
    {
        $analysis = $data['analysis'] ?? ['total' => 0, 'average_achievement' => null, 'on_track' => 0, 'at_risk' => 0, 'off_track' => 0];
        $average = $analysis['average_achievement'] === null ? 'not yet scored' : round((float) $analysis['average_achievement'], 2).'%';
        $scope = $data['workstationScopeLabel'] ?: 'all active workstations matching the filters';

        return [
            'This workstation report reviews approved indicator performance for '.$data['periodLabel'].'.',
            'The analysis is based on '.$scope.' and the selected plan scope.',
            'It covers '.$analysis['total'].' indicator(s) with an average achievement of '.$average.'.',
            $analysis['on_track'].' indicator(s) are on track, '.$analysis['at_risk'].' are at risk, and '.$analysis['off_track'].' are off track.',
            'The findings support research, management follow-up, and comparison of workstation contribution against planned targets.',
        ];
    }

    private function sectionIntro(array $data): array
    {
        $scope = $data['workstationScopeLabel'] ?: 'the selected workstation filters';

        return [
            'The table below summarizes approved results for '.$scope.' during the selected reporting period.',
            'It highlights target, actual approved value, and achievement percentage so workstation performance can be reviewed in a compact evidence table.',
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
