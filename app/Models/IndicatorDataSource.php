<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataSource extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'data_source_id',

        'is_primary',

        'collection_status',

    ];

    protected function casts(): array
    {
        return [

            'is_primary' => 'boolean',

        ];
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }
}
