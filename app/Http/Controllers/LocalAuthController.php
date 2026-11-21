<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocalAuthController extends Controller
{
    public function editPassword(): View
    {
        return view('auth.local-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => $data['password'],
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        return redirect()->route('dashboard')->with('success', __('Your password has been updated.'));
    }
}
