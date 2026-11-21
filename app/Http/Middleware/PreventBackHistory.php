<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stops browsers caching page responses so the Back button after sign-out (or
 * after sign-in) re-asks the server instead of showing a stale page.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_contains((string) $response->headers->get('Cache-Control'), 'no-store')) {
            return $response;
        }

        if (! $response instanceof BinaryFileResponse && ! $response instanceof StreamedResponse) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
