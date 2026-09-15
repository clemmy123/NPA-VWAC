<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\ReportingPeriod;
use App\Services\IndicatorPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralReportController extends Controller
{
    use ResolvesReportPeriod;

    public const array FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'frequency' => ['nullable', 'in:'.implode(',', self::FREQUENCIES)],
            'month' => ['nullable', 'date'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'thematic_area_id' => ['nullable', 'integer', 'exists:thematic_areas,id'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $frequency = $data['frequency'] ?? 'monthly';
        $applied = $request->boolean('apply');
        $projects = $this->visibleProjects($request)->with('thematicAreas')->orderBy('name')->get();
        $thematicAreas = $projects->flatMap->thematicAreas->sortBy('name')->values();

        $selectedProject = $projects->firstWhere('id', (int) ($data['project_id'] ?? 0))
            ?? $projects->first();

        $areasForPlan = $selectedProject
            ? $thematicAreas->where('project_id', $selectedProject->id)->values()
            : $thematicAreas;

        $selectedThematicArea = $areasForPlan->firstWhere('id', (int) ($data['thematic_area_id'] ?? 0))
            ?? $areasForPlan->first();

        $month = isset($data['month']) ? Carbon::parse($data['month'])->startOfMonth() : now()->startOfMonth();
        $financialYears = FinancialYear::query()->orderByDesc('start_date')->get();
        $reportingPeriods = ReportingPeriod::query()->with('financialYear')->orderBy('start_date')->get();

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

        if ($applied && $selectedThematicArea && $selectedFinancialYear) {
            $indicators = $selectedThematicArea->indicators()->with(['unitOfMeasure'])->orderBy('code')->orderBy('name')->get();

            foreach ($indicators as $indicator) {
                $rows[] = [
                    'indicator' => $indicator,
                    'performance' => $this->performanceService->summarize(
                        $indicator,
                        $selectedFinancialYear,
                        $selectedReportingPeriod,
                        $from,
                        $to,
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

        return view('reports.general', [
            'frequency' => $frequency,
            'month' => $month,
            'applied' => $applied,
            'projects' => $projects,
            'thematicAreas' => $thematicAreas,
            'selectedProject' => $selectedProject,
            'selectedThematicArea' => $selectedThematicArea,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'selectedFinancialYear' => $selectedFinancialYear,
            'selectedReportingPeriod' => $selectedReportingPeriod,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'analysis' => $analysis,
        ]);
    }
}
