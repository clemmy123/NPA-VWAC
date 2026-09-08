<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDimension extends Model
{
    use HasFactory;

    protected $fillable = ['indicator_id', 'dimension_id', 'is_required', 'must_reconcile'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'must_reconcile' => 'boolean'];
    }


}
