<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThematicAreaRequest;
use App\Http\Requests\UpdateThematicAreaRequest;
use App\Http\Resources\ThematicAreaResource;
use App\Models\Indicator;
use App\Models\MeasurementType;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThematicAreaController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'inactive', 'completed', 'closed'];

    public function index(Request $request): JsonResponse|View
    {
        $projectId = $request->integer('project_id');
        $thematicAreas = $this->scopedThematicAreas($request)
            ->with('project')
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when(! $request->wantsJson() && ! $projectId, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(ThematicAreaResource::collection($thematicAreas)->response()->getData(true));
        }

        return view('thematic-areas.index', [
            'thematicAreas' => $thematicAreas,
            'projects' => $this->assignableProjects($request),
            'selectedProjectId' => $projectId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('thematic-areas.create', [
            'statusOptions' => self::STATUS_OPTIONS,
            'projects' => $this->assignableProjects($request),
        ]);
    }

    public function store(StoreThematicAreaRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorizeProjectScope($request, (int) $request->validated('project_id'));

        $thematicArea = ThematicArea::create($request->validated() + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new ThematicAreaResource($thematicArea))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', __('Thematic area ":name" created.', ['name' => $thematicArea->name]));
    }

    public function show(Request $request, ThematicArea $thematicArea): JsonResponse|View|ThematicAreaResource
    {
        $this->authorizeThematicAreaAccess($request, $thematicArea);

        if ($request->wantsJson()) {
            return new ThematicAreaResource($thematicArea);
        }

        $thematicArea->load('project');

        return view('thematic-areas.show', [
            'thematicArea' => $thematicArea,
            'indicators' => $thematicArea->indicators()->latest('id')->get(),
            'interventions' => $thematicArea->interventions()->with('indicators')->latest('id')->get(),
            'indicatorStatusOptions' => IndicatorController::STATUS_OPTIONS,
            'interventionStatusOptions' => self::STATUS_OPTIONS,
            'measurementTypes' => MeasurementType::query()->orderBy('name')->get(),
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->get(),
            'allIndicators' => Indicator::query()->orderBy('name')->get(),
            'managers' => $thematicArea->users()->orderBy('name')->get(),
            'assignableManagers' => User::role('Thematic Manager')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, ThematicArea $thematicArea): View
    {
        $this->authorizeThematicAreaAccess($request, $thematicArea);

        return view('thematic-areas.edit', [
            'thematicArea' => $thematicArea,
            'statusOptions' => self::STATUS_OPTIONS,
            'projects' => $this->assignableProjects($request),
        ]);
    }

    public function update(UpdateThematicAreaRequest $request, ThematicArea $thematicArea): JsonResponse|RedirectResponse|ThematicAreaResource
    {
        $this->authorizeThematicAreaAccess($request, $thematicArea);

        if ($request->validated('project_id')) {
            $this->authorizeProjectScope($request, (int) $request->validated('project_id'));
        }

        $thematicArea->update($request->validated());

        if ($request->wantsJson()) {
            return new ThematicAreaResource($thematicArea);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', __('Thematic area ":name" updated.', ['name' => $thematicArea->name]));
    }

    public function destroy(Request $request, ThematicArea $thematicArea): JsonResponse|RedirectResponse
    {
        $this->authorizeThematicAreaAccess($request, $thematicArea);

        $thematicArea->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', __('Thematic area ":name" deleted.', ['name' => $thematicArea->name]));
    }

    public function disable(Request $request, ThematicArea $thematicArea): RedirectResponse
    {
        $this->authorizeThematicAreaAccess($request, $thematicArea);
        $thematicArea->update(['status' => 'inactive']);

        return back()->with('success', __('Thematic area ":name" disabled.', ['name' => $thematicArea->name]));
    }

    private function scopedThematicAreas(Request $request): Builder
    {
        $query = ThematicArea::query();

        if (! $request->user()->hasRole('Super Admin')) {
            $query->whereIn('id', $request->user()->visibleThematicAreaIds());
        }

        return $query;
    }

    private function authorizeThematicAreaAccess(Request $request, ThematicArea $thematicArea): void
    {
        if ($request->user()->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($thematicArea->id, $request->user()->visibleThematicAreaIds(), true), 403);
    }

    private function authorizeProjectScope(Request $request, int $projectId): void
    {
        if ($request->user()->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($projectId, $request->user()->assignedProjectIds(), true), 403);
    }

    /** @return Collection<int, Project> */
    private function assignableProjects(Request $request)
    {
        if ($request->user()->hasRole('Super Admin')) {
            return Project::query()->orderBy('name')->get();
        }

        return Project::query()->whereIn('id', $request->user()->assignedProjectIds())->orderBy('name')->get();
    }
}
