<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorTargetRequest;
use App\Http\Requests\UpdateIndicatorTargetRequest;
use App\Http\Resources\IndicatorTargetResource;
use App\Models\IndicatorTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicatorTargetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $targets = IndicatorTarget::query()
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->paginate($request->integer('per_page', 15));

        return response()->json(IndicatorTargetResource::collection($targets)->response()->getData(true));
    }

    public function store(StoreIndicatorTargetRequest $request): JsonResponse
    {
        $target = IndicatorTarget::create($request->validated() + ['created_by' => $request->user()->id]);

        return (new IndicatorTargetResource($target))->response()->setStatusCode(201);
    }

    public function show(IndicatorTarget $indicatorTarget): IndicatorTargetResource
    {
        return new IndicatorTargetResource($indicatorTarget);
    }

    public function update(UpdateIndicatorTargetRequest $request, IndicatorTarget $indicatorTarget): IndicatorTargetResource
    {
        $indicatorTarget->update($request->validated());

        return new IndicatorTargetResource($indicatorTarget);
    }

    public function destroy(IndicatorTarget $indicatorTarget): JsonResponse
    {
        $indicatorTarget->delete();

        return response()->json(null, 204);
    }
}
