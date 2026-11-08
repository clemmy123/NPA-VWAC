<?php

namespace App\Http\Controllers;

use App\Exports\SimpleReportExport;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneralReportController extends Controller
{
    use PaginatesReportRows;
    use ResolvesReportPeriod;

    public const array FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $data = $this->reportData($request);

        return view('reports.general', $data + [
            'rowPaginator' => $this->paginateRows($request, $data['rows']),
        ]);
    }

    public function exportPreview(Request $request, string $format): View|RedirectResponse|Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $data = $this->reportData($request);

        if (! $data['applied']) {
            return redirect()->route('reports.general', ['frequency' => $data['frequency']])
                ->with('warning', __('Apply filters before exporting.'));
        }

        $filename = 'general-report-'.now()->format('Ymd-His');

        if ($request->boolean('download')) {
            if ($format === 'excel') {
                return Excel::download(new SimpleReportExport($this->excelRows($data)), $filename.'.xlsx');
            }

            return Pdf::loadView('reports.exports.simple-pdf', $data + [
                'title' => ucfirst($data['frequency']).' General Report',
                'context' => $this->contextLines($data),
                'executiveSummary' => $this->executiveSummary($data, false),
                'sectionIntro' => $this->sectionIntro($data, false),
                'tableRows' => $this->tableRows($data),
            ])->setPaper('a4', 'landscape')->stream($filename.'.pdf');
        }

        return view('reports.export-preview', $data + [
            'title' => ucfirst($data['frequency']).' General Report',
            'format' => $format,
            'downloadRoute' => route('reports.general.export', [$format] + $request->query() + ['download' => 1]),
            'context' => $this->contextLines($data),
            'executiveSummary' => $this->executiveSummary($data, false),
            'sectionIntro' => $this->sectionIntro($data, false),
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
            'Plan: '.($data['selectedProject']?->name ?: 'All visible plans'),
            'Thematic area: '.($data['selectedThematicArea']?->name ?: 'All thematic areas'),
            'Indicator: '.($data['selectedIndicator']?->name ?: 'All indicators'),
        ]));
    }

    private function executiveSummary(array $data, bool $workstation): array
    {
        $analysis = $data['analysis'] ?? ['total' => 0, 'average_achievement' => null, 'on_track' => 0, 'at_risk' => 0, 'off_track' => 0];
        $average = $analysis['average_achievement'] === null ? 'not yet scored' : round((float) $analysis['average_achievement'], 2).'%';

        return [
            'This report reviews approved implementation performance for '.$data['periodLabel'].'.',
            'It covers '.$analysis['total'].' indicator(s) within the selected plan and thematic scope.',
            'Average achievement is '.$average.' based on indicators with measurable targets.',
            $analysis['on_track'].' indicator(s) are on track, '.$analysis['at_risk'].' are at risk, and '.$analysis['off_track'].' are off track.',
            'The findings should guide management review, follow-up with responsible teams, and evidence-based planning.',
        ];
    }

    private function sectionIntro(array $data, bool $workstation): array
    {
        return [
            'The table below presents each indicator with its approved actual value against the target for the selected reporting period.',
            'Achievement percentages are calculated from approved data only, so indicators without approved entries may show low or blank performance until reporting is completed.',
        ];
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
