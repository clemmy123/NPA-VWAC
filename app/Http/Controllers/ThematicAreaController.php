<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThematicAreaRequest;
use App\Http\Requests\UpdateThematicAreaRequest;
use App\Http\Resources\ThematicAreaResource;
use App\Models\ThematicArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThematicAreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $thematicAreas = ThematicArea::query()
            ->when($request->integer('project_id'), fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->paginate($request->integer('per_page', 15));

        return response()->json(ThematicAreaResource::collection($thematicAreas)->response()->getData(true));
    }

    public function store(StoreThematicAreaRequest $request): JsonResponse
    {
        $thematicArea = ThematicArea::create($request->validated() + ['created_by' => $request->user()->id]);

        return (new ThematicAreaResource($thematicArea))->response()->setStatusCode(201);
    }

    public function show(ThematicArea $thematicArea): ThematicAreaResource
    {
        return new ThematicAreaResource($thematicArea);
    }

    public function update(UpdateThematicAreaRequest $request, ThematicArea $thematicArea): ThematicAreaResource
    {
        $thematicArea->update($request->validated());

        return new ThematicAreaResource($thematicArea);
    }

    public function destroy(ThematicArea $thematicArea): JsonResponse
    {
        $thematicArea->delete();

        return response()->json(null, 204);
    }
}
