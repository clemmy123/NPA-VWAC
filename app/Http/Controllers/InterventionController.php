<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterventionRequest;
use App\Http\Requests\UpdateInterventionRequest;
use App\Http\Resources\InterventionResource;
use App\Models\Indicator;
use App\Models\Intervention;
use App\Models\ThematicArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'completed', 'closed'];

    public function index(Request $request): JsonResponse|View
    {
        $interventions = Intervention::query()
            ->with(['indicators', 'thematicArea'])
            ->when($request->integer('thematic_area_id'), fn ($query, $thematicAreaId) => $query->where('thematic_area_id', $thematicAreaId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(InterventionResource::collection($interventions)->response()->getData(true));
        }

        return view('interventions.index', ['interventions' => $interventions]);
    }

    public function create(): View
    {
        return view('interventions.create', [
            'statusOptions' => self::STATUS_OPTIONS,
            'thematicAreas' => ThematicArea::query()->orderBy('name')->get(),
            'indicators' => Indicator::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInterventionRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $indicatorIds = $data['indicator_ids'] ?? [];
        unset($data['indicator_ids']);

        $intervention = Intervention::create($data + ['created_by' => $request->user()->id]);
        $intervention->indicators()->sync($indicatorIds);

        if ($request->wantsJson()) {
            return (new InterventionResource($intervention->load('indicators')))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'interventions.index')->with('success', "Intervention \"{$intervention->name}\" created.");
    }

    public function show(Intervention $intervention): InterventionResource
    {
        return new InterventionResource($intervention->load('indicators'));
    }

    public function edit(Intervention $intervention): View
    {
        return view('interventions.edit', [
            'intervention' => $intervention->load('indicators'),
            'statusOptions' => self::STATUS_OPTIONS,
            'thematicAreas' => ThematicArea::query()->orderBy('name')->get(),
            'indicators' => Indicator::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateInterventionRequest $request, Intervention $intervention): JsonResponse|RedirectResponse|InterventionResource
    {
        $data = $request->validated();

        if (array_key_exists('indicator_ids', $data)) {
            $intervention->indicators()->sync($data['indicator_ids'] ?? []);
            unset($data['indicator_ids']);
        }

        $intervention->update($data);

        if ($request->wantsJson()) {
            return new InterventionResource($intervention->load('indicators'));
        }

        return $this->redirectBackOrTo($request, 'interventions.index')->with('success', "Intervention \"{$intervention->name}\" updated.");
    }

    public function destroy(Request $request, Intervention $intervention): JsonResponse|RedirectResponse
    {
        $intervention->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return $this->redirectBackOrTo($request, 'interventions.index')->with('success', "Intervention \"{$intervention->name}\" deleted.");
    }
}
