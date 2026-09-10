<?php

namespace App\Support;

use App\Models\Council;
use App\Models\District;
use App\Models\Division;
use App\Models\Kitongoji;
use App\Models\Region;
use App\Models\Township;
use App\Models\VillageMtaa;
use App\Models\Ward;

/**
 * Maps a `location_level` string (as stored on indicator_data_entries /
 * indicator_data_assignments) to the model/primary key it points into. No DB-level
 * FK is possible for these columns since the target table varies by level, so
 * existence has to be checked here at the application layer instead.
 */
class AdminLocationLevel
{
    /** @var array<string, array{model: class-string, key: string}> */
    private const LEVELS = [
        'region' => ['model' => Region::class, 'key' => 'region_id'],
        'district' => ['model' => District::class, 'key' => 'district_id'],
        'council' => ['model' => Council::class, 'key' => 'council_id'],
        'division' => ['model' => Division::class, 'key' => 'division_id'],
        'township' => ['model' => Township::class, 'key' => 'township_id'],
        'ward' => ['model' => Ward::class, 'key' => 'ward_id'],
        'village_mtaa' => ['model' => VillageMtaa::class, 'key' => 'village_mtaa_id'],
        'kitongoji' => ['model' => Kitongoji::class, 'key' => 'kitongoji_id'],
    ];

    /**
     * @return list<string>
     */
    public static function levels(): array
    {
        return array_keys(self::LEVELS);
    }

    public static function isValidLevel(string $level): bool
    {
        return array_key_exists($level, self::LEVELS);
    }

    public static function exists(string $level, int $id): bool
    {
        if (! self::isValidLevel($level)) {
            return false;
        }

        $definition = self::LEVELS[$level];

        return $definition['model']::query()->where($definition['key'], $id)->exists();
    }
}
