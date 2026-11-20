<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorPlan;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use App\Services\IndicatorPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PlanBuilderController extends Controller
{
    public function __construct(private readonly IndicatorPlanService $plans) {}

    /**
     * "My Thematic Areas" — a picker scoped to whichever thematic areas the
     * signed-in user may manage, so a Thematic Manager lands on just their
     * one (or few) thematic areas instead of the whole plan.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $visibleThematicAreas = $user->hasRole('Super Admin')
            ? ThematicArea::query()->with('project')->orderBy('name')->get()
            : ThematicArea::query()->with('project')->whereIn('id', $user->visibleThematicAreaIds())->orderBy('name')->get();

        // The project filter only offers projects that actually have a
        // thematic area this user can see — not every project they're
        // assigned to, and never an empty-looking "no thematic areas" project.
        $filterProjects = $visibleThematicAreas->pluck('project')->filter()->unique('id')->sortBy('name')->values();

        $selectedProjectId = $request->integer('project_id') ?: null;

        $thematicAreas = $selectedProjectId
            ? $visibleThematicAreas->where('project_id', $selectedProjectId)->values()
            : $visibleThematicAreas;

        $currentFinancialYear = $this->currentFinancialYear();

        $thematicAreas->each(function (ThematicArea $thematicArea) use ($currentFinancialYear) {
            $thematicArea->indicator_count = $thematicArea->indicators()->count();
            $thematicArea->targeted_count = $currentFinancialYear
                ? $thematicArea->indicators()
                    ->whereHas('targets', fn ($query) => $query
                        ->where('financial_year_id', $currentFinancialYear->id)
                        ->whereNull('reporting_period_id'))
                    ->count()
                : 0;
        });

        $canCreateThematicArea = $user->can('thematic-area.create');

        return view('plan-builder.index', [
            'thematicAreas' => $thematicAreas,
            'filterProjects' => $filterProjects,
            'selectedProjectId' => $selectedProjectId,
            'canCreateThematicArea' => $canCreateThematicArea,
            'projects' => $canCreateThematicArea ? $this->assignableProjects($request) : collect(),
            'statusOptions' => ThematicAreaController::STATUS_OPTIONS,
        ]);
    }

    /**
     * The focused single-thematic-area workspace: an Excel-style grid with
     * one row per indicator (interventions, baseline, target, budgets and
     * institutions inline) for a chosen plan financial year.
     */
    public function show(Request $request, ThematicArea $thematicArea): View
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin')) {
            abort_unless(in_array($thematicArea->id, $user->visibleThematicAreaIds(), true), 403);
        }

        $thematicArea->load('project');

        $financialYears = FinancialYear::query()->orderByDesc('start_date')->get();
        $planFinancialYear = $financialYears->firstWhere('id', $request->integer('financial_year_id'))
            ?? $this->currentFinancialYear();

        $indicators = $thematicArea->indicators()
            ->with(['unitOfMeasure', 'measurementType', 'interventions' => fn ($query) => $query->orderBy('interventions.id')])
            ->orderBy('name')
            ->get();

        $plans = $planFinancialYear
            ? IndicatorPlan::query()
                ->whereIn('indicator_id', $indicators->pluck('id'))
                ->where('financial_year_id', $planFinancialYear->id)
                ->get()
                ->keyBy('indicator_id')
            : collect();

        $missingByIndicator = $indicators->mapWithKeys(fn (Indicator $indicator) => [
            $indicator->id => $plans->has($indicator->id)
                ? $this->plans->missingFields($plans[$indicator->id])
                : [__('not planned yet')],
        ]);

        $canPlan = $user->hasRole('Super Admin') || $user->can('indicator.set-target') || $user->hasRole('Project Manager');

        return view('plan-builder.show', [
            'thematicArea' => $thematicArea,
            'indicators' => $indicators,
            'plans' => $plans,
            'missingByIndicator' => $missingByIndicator,
            'planFinancialYear' => $planFinancialYear,
            'financialYears' => $financialYears,
            'organizations' => Organization::query()->orderBy('name')->get(),
            'measurementTypes' => MeasurementType::query()->orderBy('name')->get(),
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->get(),
            'reportingFrequencies' => ['daily', 'weekly', 'monthly', 'quarterly', 'biannual', 'annual'],
            'canUpdateThematicArea' => $user->can('thematic-area.update'),
            'canCreateIndicator' => $user->can('indicator.create'),
            'canUpdateIndicator' => $user->can('indicator.update'),
            'canDeleteIndicator' => $user->can('indicator.delete'),
            'canPlan' => $canPlan,
            'canCreateIntervention' => $user->can('intervention.create'),
            'canUpdateIntervention' => $user->can('intervention.update'),
            'canDeleteIntervention' => $user->can('intervention.delete'),
        ]);
    }

    /**
     * Autosave one grid row's plan cells as a draft. Lenient on purpose —
     * a row is half-filled while the user is still typing across it; the
     * completeness check happens when the plan is submitted.
     */
    public function savePlan(Request $request, ThematicArea $thematicArea, Indicator $indicator): JsonResponse
    {
        abort_unless($indicator->thematic_area_id === $thematicArea->id, 404);

        $limits = $indicator->valueLimits();
        $valueRules = ['nullable', 'numeric', 'min:0'];
        if ($limits['max'] !== null) {
            $valueRules[] = 'max:'.$limits['max'];
        }
        if ($limits['decimals'] === 0) {
            $valueRules[] = 'integer';
        } elseif ($limits['decimals'] !== null) {
            $valueRules[] = 'decimal:0,'.$limits['decimals'];
        }

        $data = $request->validate([
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'baseline_financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'baseline_value' => $valueRules,
            'annual_target' => $valueRules,
            'domestic_budget' => ['nullable', 'numeric', 'min:0'],
            'external_budget' => ['nullable', 'numeric', 'min:0'],
            'lead_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'participating_organization_ids' => ['nullable', 'array'],
            'participating_organization_ids.*' => ['integer', 'distinct', 'exists:organizations,id'],
        ]);

        $plan = $this->plans->save($data + ['indicator_id' => $indicator->id], $request->user());

        return response()->json(['data' => [
            'id' => $plan->id,
            'status' => $plan->status,
            'missing' => $this->plans->missingFields($plan),
        ]]);
    }

    /**
     * Submit every draft/returned row of the grid for institutional
     * confirmation in one go. All-or-nothing: if any row is incomplete,
     * nothing is submitted and the incomplete rows are listed.
     */
    public function submitPlans(Request $request, ThematicArea $thematicArea): JsonResponse
    {
        $this->plans->authorizeThematic($request->user(), $thematicArea->id);

        $data = $request->validate(['financial_year_id' => ['required', 'integer', 'exists:financial_years,id']]);

        $indicators = $thematicArea->indicators()->orderBy('name')->get();
        $existing = IndicatorPlan::query()
            ->whereIn('indicator_id', $indicators->pluck('id'))
            ->where('financial_year_id', $data['financial_year_id'])
            ->get()
            ->keyBy('indicator_id');

        $problems = [];
        $submittable = [];

        foreach ($indicators as $indicator) {
            $plan = $existing->get($indicator->id);

            if ($plan === null) {
                $problems[] = $indicator->name.': '.__('not planned yet');

                continue;
            }
            if (! in_array($plan->status, ['draft', 'returned'], true)) {
                continue;
            }
            if ($missing = $this->plans->missingFields($plan)) {
                $problems[] = $indicator->name.': '.implode(', ', $missing);

                continue;
            }
            $submittable[] = $plan;
        }

        if ($problems !== []) {
            return response()->json([
                'message' => __('Plan not submitted — incomplete rows:').' '.implode(' • ', $problems),
            ], 422);
        }

        foreach ($submittable as $plan) {
            $this->plans->transition($plan, $request->user(), 'submit', null);
        }

        return response()->json(['data' => ['submitted' => count($submittable)]]);
    }

    private function currentFinancialYear(): ?FinancialYear
    {
        return FinancialYear::query()->where('is_current', true)->first()
            ?? FinancialYear::query()->orderByDesc('start_date')->first();
    }

    /** @return Collection<int, Project> */
    private function assignableProjects(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            return Project::query()->orderBy('name')->get();
        }

        return Project::query()->whereIn('id', $user->assignedProjectIds())->orderBy('name')->get();
    }
}
