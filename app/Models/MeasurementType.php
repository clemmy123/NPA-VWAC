<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeasurementType extends Model
{
    use HasFactory;

    protected $fillable = [

        'code',

        'name',

        'description',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }

    /**
     * How many decimals a value of this measurement type may carry, and its ceiling.
     *
     * @return array{decimals: int|null, max: int|null}
     */
    public function valueLimits(): array
    {
        return match ($this->code) {
            'count' => ['decimals' => 0, 'max' => null],
            'percentage' => ['decimals' => 2, 'max' => 100],
            'yes_no' => ['decimals' => 0, 'max' => 1],
            'currency' => ['decimals' => 2, 'max' => null],
            default => ['decimals' => null, 'max' => null],
        };
    }
}
