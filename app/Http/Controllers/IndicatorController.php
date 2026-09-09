<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorRequest;
use App\Http\Requests\UpdateIndicatorRequest;
use App\Http\Resources\IndicatorResource;
use App\Models\Indicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $indicators = Indicator::query()
            ->with('interventions')
            ->when($request->integer('thematic_area_id'), fn ($query, $thematicAreaId) => $query->where('thematic_area_id', $thematicAreaId))
            ->paginate($request->integer('per_page', 15));

        return response()->json(IndicatorResource::collection($indicators)->response()->getData(true));
    }

    public function store(StoreIndicatorRequest $request): JsonResponse
    {
        $indicator = Indicator::create($request->validated() + ['created_by' => $request->user()->id]);

        return (new IndicatorResource($indicator))->response()->setStatusCode(201);
    }

    public function show(Indicator $indicator): IndicatorResource
    {
        return new IndicatorResource($indicator->load('interventions'));
    }

    public function update(UpdateIndicatorRequest $request, Indicator $indicator): IndicatorResource
    {
        $indicator->update($request->validated());

        return new IndicatorResource($indicator);
    }

    public function destroy(Indicator $indicator): JsonResponse
    {
        $indicator->delete();

        return response()->json(null, 204);
    }
}
