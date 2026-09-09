<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorBaselineRequest;
use App\Http\Requests\UpdateIndicatorBaselineRequest;
use App\Http\Resources\IndicatorBaselineResource;
use App\Models\IndicatorBaseline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicatorBaselineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $baselines = IndicatorBaseline::query()
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->paginate($request->integer('per_page', 15));

        return response()->json(IndicatorBaselineResource::collection($baselines)->response()->getData(true));
    }

    public function store(StoreIndicatorBaselineRequest $request): JsonResponse
    {
        $baseline = IndicatorBaseline::create($request->validated() + ['created_by' => $request->user()->id]);

        return (new IndicatorBaselineResource($baseline))->response()->setStatusCode(201);
    }

    public function show(IndicatorBaseline $indicatorBaseline): IndicatorBaselineResource
    {
        return new IndicatorBaselineResource($indicatorBaseline);
    }

    public function update(UpdateIndicatorBaselineRequest $request, IndicatorBaseline $indicatorBaseline): IndicatorBaselineResource
    {
        $indicatorBaseline->update($request->validated());

        return new IndicatorBaselineResource($indicatorBaseline);
    }

    public function destroy(IndicatorBaseline $indicatorBaseline): JsonResponse
    {
        $indicatorBaseline->delete();

        return response()->json(null, 204);
    }
}
