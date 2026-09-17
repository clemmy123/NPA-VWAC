<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorApprovalAssignment extends Model
{
    protected $fillable = ['user_id', 'location_level', 'location_id', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function user() { return $this->belongsTo(User::class); }
}
