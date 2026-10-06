<?php

namespace App\Support;

/**
 * PNG gradients as data URIs, for renderers such as Dompdf that ignore CSS gradients.
 */
final class GradientImage
{
    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * @param  list<string>  $colors  Hex stops spread evenly from the first edge to the last.
     */
    public static function dataUri(array $colors, int $width, int $height, bool $horizontal = true): string
    {
        $key = implode(',', $colors).":{$width}x{$height}:".($horizontal ? 'h' : 'v');

        return self::$cache[$key] ??= self::render($colors, $width, $height, $horizontal);
    }

    /** @param  list<string>  $colors */
    private static function render(array $colors, int $width, int $height, bool $horizontal): string
    {
        $image = imagecreatetruecolor($width, $height);
        $stops = array_map(self::rgb(...), $colors);
        $length = $horizontal ? $width : $height;
        $segments = max(1, count($stops) - 1);

        for ($position = 0; $position < $length; $position++) {
            $progress = $length > 1 ? $position / ($length - 1) : 0;
            $segment = min($segments - 1, (int) floor($progress * $segments));
            $local = $progress * $segments - $segment;
            [$from, $to] = [$stops[$segment], $stops[min($segment + 1, count($stops) - 1)]];
            $color = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $local),
                (int) round($from[1] + ($to[1] - $from[1]) * $local),
                (int) round($from[2] + ($to[2] - $from[2]) * $local),
            );

            $horizontal
                ? imageline($image, $position, 0, $position, $height - 1, $color)
                : imageline($image, 0, $position, $width - 1, $position, $color);
        }

        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
