<?php

namespace App\Support;

final class DisplayNumber
{
    public static function format(mixed $value, int $maximumDecimals = 4, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format((float) $value, $maximumDecimals, '.', ','), '0'), '.');
    }

    public static function input(mixed $value, int $maximumDecimals = 4): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format((float) $value, $maximumDecimals, '.', ''), '0'), '.');
    }

    public static function normalizeInput(mixed $value): int|float|string|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return is_numeric($value) ? $value : null;
        }

        $normalized = str_replace([',', ' ', "\u{00A0}"], '', trim($value));

        return $normalized === '' || $normalized === '-' || $normalized === '.' ? null : $normalized;
    }
}
