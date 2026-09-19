<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectUserController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeManagerChange($request, $project);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $project->users()->syncWithoutDetaching([
            $data['user_id'] => ['is_active' => true],
        ]);

        return $this->redirectBackOrTo($request, 'projects.show', ['project' => $project])
            ->with('success', __('Project Manager assigned.'));
    }

    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorizeManagerChange($request, $project);

        $project->users()->detach($user->id);

        return $this->redirectBackOrTo($request, 'projects.show', ['project' => $project])
            ->with('success', __('Project Manager removed.'));
    }

    /**
     * Only Super Admin, or a manager already assigned to this project, may change
     * who else manages it. Otherwise `project.assign-manager` (which every Project
     * Manager holds) would let a PM assign themselves to a project they're not on.
     */
    private function authorizeManagerChange(Request $request, Project $project): void
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($project->id, $user->assignedProjectIds(), true), 403);
    }
}
