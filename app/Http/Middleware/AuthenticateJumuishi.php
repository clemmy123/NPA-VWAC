<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJumuishi
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('jumuishi.platform_secret');
        $received = (string) $request->header('X-Jumuishi-Platform-Secret');

        if (! config('jumuishi.enabled') || $expected === '' || ! hash_equals($expected, $received)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Jumuishi credentials.'], 401);
        }

        return $next($request);
    }
}
