<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'units_of_measure';

    protected $fillable = [

        'code',

        'name',

        'symbol',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }

    /** Units that count whole things — a fraction of a person or a case is meaningless. */
    public const array WHOLE_NUMBER_CODES = [
        'people', 'women', 'children', 'households', 'groups',
        'organizations', 'accounts', 'cases', 'activities', 'responses',
    ];

    /**
     * How many decimals a value in this unit may carry, and its ceiling.
     *
     * @return array{decimals: int|null, max: int|null}
     */
    public function valueLimits(): array
    {
        return match (true) {
            in_array($this->code, self::WHOLE_NUMBER_CODES, true) => ['decimals' => 0, 'max' => null],
            $this->code === 'percent' => ['decimals' => 2, 'max' => 100],
            in_array($this->code, ['tzs', 'usd'], true) => ['decimals' => 2, 'max' => null],
            default => ['decimals' => null, 'max' => null],
        };
    }
}
