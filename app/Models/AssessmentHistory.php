<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'change_type',
        'old_value',
        'new_value',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
    
    public function getChangeTypeLabelAttribute(): string
    {
        return match ($this->change_type) {
            'status' => 'Perubahan Status Vendor',
            'tier'   => 'Perubahan Tier Criticality',
            'result' => 'Perubahan Hasil Assessment',
            default  => '-',
        };
    }
}