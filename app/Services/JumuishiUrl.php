<?php

namespace App\Services;

class JumuishiUrl
{
    public static function central(string $path): string
    {
        return rtrim((string) config('jumuishi.url'), '/').'/'.ltrim($path, '/');
    }

    public static function login(): string
    {
        return self::central(config('jumuishi.sso_start_path')).'/'.rawurlencode(config('jumuishi.module_path'));
    }

    public static function safeReturnTo(mixed $path): string
    {
        if (! is_string($path)) {
            return '/dashboard';
        }

        $decoded = rawurldecode($path);
        if (! str_starts_with($decoded, '/') || str_starts_with($decoded, '//')
            || str_contains($decoded, '\\') || preg_match('/[\x00-\x20\x7f]/', $decoded)
            || preg_match('#^/(?:login|logout|jumuishi/sso/consume)(?:[/?\x23]|$)#i', $decoded)) {
            return '/dashboard';
        }

        return $path;
    }
}
