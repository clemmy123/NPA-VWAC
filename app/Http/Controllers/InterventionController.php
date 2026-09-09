<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterventionRequest;
use App\Http\Requests\UpdateInterventionRequest;
use App\Http\Resources\InterventionResource;
use App\Models\Intervention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $interventions = Intervention::query()
            ->with('indicators')
            ->when($request->integer('thematic_area_id'), fn ($query, $thematicAreaId) => $query->where('thematic_area_id', $thematicAreaId))
            ->paginate($request->integer('per_page', 15));

        return response()->json(InterventionResource::collection($interventions)->response()->getData(true));
    }

    public function store(StoreInterventionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $indicatorIds = $data['indicator_ids'] ?? [];
        unset($data['indicator_ids']);

        $intervention = Intervention::create($data + ['created_by' => $request->user()->id]);
        $intervention->indicators()->sync($indicatorIds);

        return (new InterventionResource($intervention->load('indicators')))->response()->setStatusCode(201);
    }

    public function show(Intervention $intervention): InterventionResource
    {
        return new InterventionResource($intervention->load('indicators'));
    }

    public function update(UpdateInterventionRequest $request, Intervention $intervention): InterventionResource
    {
        $data = $request->validated();

        if (array_key_exists('indicator_ids', $data)) {
            $intervention->indicators()->sync($data['indicator_ids'] ?? []);
            unset($data['indicator_ids']);
        }

        $intervention->update($data);

        return new InterventionResource($intervention->load('indicators'));
    }

    public function destroy(Intervention $intervention): JsonResponse
    {
        $intervention->delete();

        return response()->json(null, 204);
    }
}
