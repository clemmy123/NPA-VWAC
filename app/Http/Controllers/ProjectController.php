<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'completed', 'closed'];

    public function index(Request $request): JsonResponse|View
    {
        $projects = Project::query()->latest('id')->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(ProjectResource::collection($projects)->response()->getData(true));
        }

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create', ['statusOptions' => self::STATUS_OPTIONS]);
    }

    public function store(StoreProjectRequest $request): JsonResponse|RedirectResponse
    {
        $project = Project::create($request->validated() + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new ProjectResource($project))->response()->setStatusCode(201);
        }

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" created.");
    }

    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', ['project' => $project, 'statusOptions' => self::STATUS_OPTIONS]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse|ProjectResource
    {
        $project->update($request->validated());

        if ($request->wantsJson()) {
            return new ProjectResource($project);
        }

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" updated.");
    }

    public function destroy(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" deleted.");
    }
}
