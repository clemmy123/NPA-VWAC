<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorDataEntryApproval extends Model
{
    protected $fillable = ['location_level', 'location_id', 'sequence', 'status', 'approved_by', 'decided_at', 'comment'];
    protected function casts(): array { return ['decided_at' => 'datetime']; }
    public function entry() { return $this->belongsTo(IndicatorDataEntry::class, 'indicator_data_entry_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
