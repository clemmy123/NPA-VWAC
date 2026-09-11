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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThematicAreaController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'completed', 'closed'];

    public function index(Request $request): JsonResponse|View
    {
        $thematicAreas = ThematicArea::query()
            ->with('project')
            ->when($request->integer('project_id'), fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(ThematicAreaResource::collection($thematicAreas)->response()->getData(true));
        }

        return view('thematic-areas.index', ['thematicAreas' => $thematicAreas]);
    }

    public function create(): View
    {
        return view('thematic-areas.create', [
            'statusOptions' => self::STATUS_OPTIONS,
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreThematicAreaRequest $request): JsonResponse|RedirectResponse
    {
        $thematicArea = ThematicArea::create($request->validated() + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new ThematicAreaResource($thematicArea))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', "Thematic area \"{$thematicArea->name}\" created.");
    }

    public function show(Request $request, ThematicArea $thematicArea): JsonResponse|View|ThematicAreaResource
    {
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
        ]);
    }

    public function edit(ThematicArea $thematicArea): View
    {
        return view('thematic-areas.edit', [
            'thematicArea' => $thematicArea,
            'statusOptions' => self::STATUS_OPTIONS,
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateThematicAreaRequest $request, ThematicArea $thematicArea): JsonResponse|RedirectResponse|ThematicAreaResource
    {
        $thematicArea->update($request->validated());

        if ($request->wantsJson()) {
            return new ThematicAreaResource($thematicArea);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', "Thematic area \"{$thematicArea->name}\" updated.");
    }

    public function destroy(Request $request, ThematicArea $thematicArea): JsonResponse|RedirectResponse
    {
        $thematicArea->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return $this->redirectBackOrTo($request, 'thematic-areas.index')->with('success', "Thematic area \"{$thematicArea->name}\" deleted.");
    }
}
