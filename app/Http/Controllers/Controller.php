<?php

namespace App\Http\Controllers;

use App\Support\AdminLocationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Redirect to the caller-supplied `redirect_to` field when present and
     * pointing back into this app, falling back to the given named route
     * otherwise. Lets a "quick add/edit" form embedded on a parent record's
     * show page (e.g. adding a Thematic Area from its Project's page) return
     * there instead of always landing on the resource's own index.
     */
    protected function redirectBackOrTo(Request $request, string $fallbackRoute, array $fallbackParams = []): RedirectResponse
    {
        $redirectTo = $request->string('redirect_to')->toString();

        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect($redirectTo);
        }

        return redirect()->route($fallbackRoute, $fallbackParams);
    }

    /**
     * Resolves the chain of ancestor locations for a stored `location_level`/
     * `location_id` pair, used to prefill the cascading location picker on an edit
     * form. Empty when either half is unset.
     *
     * @return array<string, array{id: int, name: string}>
     */
    protected function locationAncestorChain(?string $level, ?int $id): array
    {
        if ($level === null || $id === null) {
            return [];
        }

        return AdminLocationLevel::ancestorChain($level, $id);
    }
}
