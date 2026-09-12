<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\IndicatorBaseline;
use App\Models\IndicatorTarget;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PlanBuilderController extends Controller
{
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
     * The focused single-thematic-area workspace: rename/describe the
     * thematic area and manage its indicators (with a current-financial-year
     * baseline and annual target) inline, without navigating away.
     */
    public function show(Request $request, ThematicArea $thematicArea): View
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin')) {
            abort_unless(in_array($thematicArea->id, $user->visibleThematicAreaIds(), true), 403);
        }

        $thematicArea->load('project');

        $currentFinancialYear = $this->currentFinancialYear();

        $indicators = $thematicArea->indicators()
            ->with(['unitOfMeasure', 'measurementType'])
            ->orderBy('name')
            ->get();

        $indicatorIds = $indicators->pluck('id');

        $baselines = $currentFinancialYear
            ? IndicatorBaseline::query()
                ->whereIn('indicator_id', $indicatorIds)
                ->where('financial_year_id', $currentFinancialYear->id)
                ->get()
                ->keyBy('indicator_id')
            : collect();

        $targets = $currentFinancialYear
            ? IndicatorTarget::query()
                ->whereIn('indicator_id', $indicatorIds)
                ->where('financial_year_id', $currentFinancialYear->id)
                ->whereNull('reporting_period_id')
                ->whereNull('dimension_option_id')
                ->get()
                ->keyBy('indicator_id')
            : collect();

        return view('plan-builder.show', [
            'thematicArea' => $thematicArea,
            'indicators' => $indicators,
            'baselines' => $baselines,
            'targets' => $targets,
            'currentFinancialYear' => $currentFinancialYear,
            'financialYears' => FinancialYear::query()->orderByDesc('start_date')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'measurementTypes' => MeasurementType::query()->orderBy('name')->get(),
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->get(),
            'reportingFrequencies' => ['monthly', 'quarterly', 'biannual', 'annual'],
            'canUpdateThematicArea' => $user->can('thematic-area.update'),
            'canCreateIndicator' => $user->can('indicator.create'),
            'canUpdateIndicator' => $user->can('indicator.update'),
            'canDeleteIndicator' => $user->can('indicator.delete'),
            'canSetBaseline' => $user->can('indicator.set-baseline'),
            'canSetTarget' => $user->can('indicator.set-target'),
        ]);
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
