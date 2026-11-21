<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Lets a user who holds several roles choose which one they are acting as. */
class ActiveRoleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($user->heldRoleNames()->all())],
        ]);

        $request->session()->put(User::ACTIVE_ROLE_SESSION_KEY, $data['role']);

        // The new role may not be allowed on the current page, so always land on the dashboard.
        return redirect()->route('dashboard');
    }
}
