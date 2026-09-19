<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'completed', 'closed'];

    public function index(Request $request): JsonResponse|View
    {
        $projects = $this->scopedProjects($request)->latest('id')->paginate($request->integer('per_page', 15));

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

        return redirect()->route('projects.index')->with('success', __('Project ":name" created.', ['name' => $project->name]));
    }

    public function show(Request $request, Project $project): JsonResponse|View|ProjectResource
    {
        $this->authorizeProjectAccess($request, $project);

        if ($request->wantsJson()) {
            return new ProjectResource($project);
        }

        return view('projects.show', [
            'project' => $project,
            'thematicAreas' => $project->thematicAreas()->latest('id')->get(),
            'thematicAreaStatusOptions' => ThematicAreaController::STATUS_OPTIONS,
            'thematicAreaProjects' => collect([$project]),
            'managers' => $project->users()->orderBy('name')->get(),
            'assignableManagers' => User::role('Project Manager')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        return view('projects.edit', ['project' => $project, 'statusOptions' => self::STATUS_OPTIONS]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse|ProjectResource
    {
        $this->authorizeProjectAccess($request, $project);

        $project->update($request->validated());

        if ($request->wantsJson()) {
            return new ProjectResource($project);
        }

        return redirect()->route('projects.index')->with('success', __('Project ":name" updated.', ['name' => $project->name]));
    }

    public function destroy(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $this->authorizeProjectAccess($request, $project);

        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('projects.index')->with('success', __('Project ":name" deleted.', ['name' => $project->name]));
    }

    private function scopedProjects(Request $request): Builder
    {
        $query = Project::query();

        if (! $request->user()->hasRole('Super Admin')) {
            $query->whereIn('id', $request->user()->visibleProjectIds());
        }

        return $query;
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        if ($request->user()->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($project->id, $request->user()->visibleProjectIds(), true), 403);
    }
}
