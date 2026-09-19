<?php

namespace App\Http\Controllers;

use App\Models\ThematicArea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThematicAreaUserController extends Controller
{
    public function store(Request $request, ThematicArea $thematicArea): RedirectResponse
    {
        $this->authorizeManagerChange($request, $thematicArea);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $thematicArea->users()->syncWithoutDetaching([
            $data['user_id'] => ['is_active' => true],
        ]);

        return $this->redirectBackOrTo($request, 'thematic-areas.show', ['thematic_area' => $thematicArea])
            ->with('success', __('Thematic Manager assigned.'));
    }

    public function destroy(Request $request, ThematicArea $thematicArea, User $user): RedirectResponse
    {
        $this->authorizeManagerChange($request, $thematicArea);

        $thematicArea->users()->detach($user->id);

        return $this->redirectBackOrTo($request, 'thematic-areas.show', ['thematic_area' => $thematicArea])
            ->with('success', __('Thematic Manager removed.'));
    }

    /**
     * Only Super Admin, a Project Manager of the parent project, or a thematic
     * manager already assigned here may change who manages this thematic area.
     * Otherwise `thematic-area.assign-manager` (held by every Thematic Manager)
     * would let one assign themselves to an unrelated thematic area.
     */
    private function authorizeManagerChange(Request $request, ThematicArea $thematicArea): void
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            return;
        }

        $inScope = in_array($thematicArea->project_id, $user->assignedProjectIds(), true)
            || in_array($thematicArea->id, $user->assignedThematicAreaIds(), true);

        abort_unless($inScope, 403);
    }
}
