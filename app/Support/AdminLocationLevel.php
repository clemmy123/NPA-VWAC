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
 *
 * `parent_level`/`parent_key` describe the real cascading hierarchy, which is not a
 * single strict chain: `ward` is filtered by `division_id` (its always-present real
 * parent in the source TAMISEMI boundary data), not by `township` — `township` is an
 * optional secondary classification under `division` that only ~14% of wards carry.
 * See the comment on the `ward` migration for the full explanation.
 */
class AdminLocationLevel
{
    /** @var array<string, array{model: class-string, key: string, parent_level: string|null, parent_key: string|null}> */
    private const LEVELS = [
        'region' => ['model' => Region::class, 'key' => 'region_id', 'parent_level' => null, 'parent_key' => null],
        'district' => ['model' => District::class, 'key' => 'district_id', 'parent_level' => 'region', 'parent_key' => 'region_id'],
        'council' => ['model' => Council::class, 'key' => 'council_id', 'parent_level' => 'district', 'parent_key' => 'district_id'],
        'division' => ['model' => Division::class, 'key' => 'division_id', 'parent_level' => 'council', 'parent_key' => 'council_id'],
        'township' => ['model' => Township::class, 'key' => 'township_id', 'parent_level' => 'division', 'parent_key' => 'division_id'],
        'ward' => ['model' => Ward::class, 'key' => 'ward_id', 'parent_level' => 'division', 'parent_key' => 'division_id'],
        'village_mtaa' => ['model' => VillageMtaa::class, 'key' => 'village_mtaa_id', 'parent_level' => 'ward', 'parent_key' => 'ward_id'],
        'kitongoji' => ['model' => Kitongoji::class, 'key' => 'kitongoji_id', 'parent_level' => 'village_mtaa', 'parent_key' => 'village_mtaa_id'],
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

    public static function parentLevel(string $level): ?string
    {
        return self::LEVELS[$level]['parent_level'] ?? null;
    }

    /**
     * The chain of levels from the top of the hierarchy down to (and including) the
     * given level, e.g. `ward` => `[region, district, council, division, ward]`.
     *
     * @return list<string>
     */
    public static function pathToLevel(string $level): array
    {
        $path = [];
        $current = $level;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = self::parentLevel($current);
        }

        return $path;
    }

    /**
     * The selectable options at a level, optionally filtered by the id of its real
     * parent. Returns everything at that level when the level has no parent (region)
     * or when no `$parentId` is given for a level whose parent hasn't been chosen yet.
     *
     * @return list<array{id: int, name: string}>
     */
    public static function options(string $level, ?int $parentId = null): array
    {
        abort_unless(self::isValidLevel($level), 404);

        $definition = self::LEVELS[$level];
        $query = $definition['model']::query();

        if ($definition['parent_key'] !== null) {
            if ($parentId === null) {
                return [];
            }

            $query->where($definition['parent_key'], $parentId);
        }

        return $query->orderBy('name')->get()
            ->map(fn ($record) => ['id' => (int) $record->{$definition['key']}, 'name' => $record->name])
            ->all();
    }

    /**
     * Walks up from a given level+id to the top of the hierarchy, resolving each
     * ancestor's id and name — used to prefill the cascading picker when editing a
     * record that already has a location set.
     *
     * @return array<string, array{id: int, name: string}>
     */
    public static function ancestorChain(string $level, int $id): array
    {
        $chain = [];
        $currentLevel = $level;
        $currentId = $id;

        while ($currentLevel !== null) {
            $definition = self::LEVELS[$currentLevel];
            $record = $definition['model']::query()->find($currentId);

            if (! $record) {
                break;
            }

            $chain[$currentLevel] = ['id' => (int) $currentId, 'name' => $record->name];

            $parentLevel = $definition['parent_level'];

            if ($parentLevel === null) {
                break;
            }

            $parentId = $record->{$definition['parent_key']};

            if ($parentId === null) {
                break;
            }

            $currentLevel = $parentLevel;
            $currentId = $parentId;
        }

        return $chain;
    }

    public static function name(string $level, int $id): ?string
    {
        if (! self::isValidLevel($level)) {
            return null;
        }

        $definition = self::LEVELS[$level];

        return $definition['model']::query()->find($id)?->name;
    }
}
