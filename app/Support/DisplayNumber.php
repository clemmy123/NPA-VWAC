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
}
