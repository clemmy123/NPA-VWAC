<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundSource extends Model
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

    public function indicators()
    {
        return $this->belongsToMany(Indicator::class, 'indicator_fund_sources')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
