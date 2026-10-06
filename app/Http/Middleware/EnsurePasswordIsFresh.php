<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsFresh
{
    /**
     * Routes a user with an expired or admin-flagged local password must
     * still be able to reach — the local password-change screen and
     * logout — so the redirect below doesn't loop them back to itself.
     */
    private const EXEMPT_ROUTES = [
        'local-password.edit',
        'local-password.update',
        'local-logout',
        'logout',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustChangePassword() && ! $request->routeIs(...self::EXEMPT_ROUTES)) {
            return redirect()->route('local-password.edit')
                ->with('warning', __('Your password has expired and must be changed before you can continue.'));
        }

        return $next($request);
    }
}
