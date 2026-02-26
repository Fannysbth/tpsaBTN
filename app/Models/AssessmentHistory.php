<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AssessmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'change_type',
        'old_value',
        'new_value',
    ];

    /**
     * CAST JSON & DATE
     */
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * RELATION
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * ===============================
     * SNAPSHOT ACCESSOR
     * ===============================
     * Mengambil isi new_value sebagai snapshot
     */
    public function getSnapshotAttribute(): array
    {
        return $this->new_value ?? [];
    }

    /**
     * ===============================
     * BASIC SNAPSHOT FIELDS
     * ===============================
     */

    public function getEvaluatedAtAttribute(): ?Carbon
    {
        return isset($this->snapshot['evaluated_at'])
            ? Carbon::parse($this->snapshot['evaluated_at'])
            : null;
    }

    public function getAssessorAttribute(): ?string
    {
        return $this->snapshot['assessor'] ?? null;
    }

    public function getVendorStatusAttribute(): ?string
    {
        return $this->snapshot['vendor_status'] ?? null;
    }

    public function getTierCriticalityAttribute(): ?string
    {
        return $this->snapshot['tier_criticality'] ?? null;
    }

    public function getTotalScoreAttribute(): ?float
    {
        return $this->snapshot['total_score'] ?? null;
    }

    public function getRiskLevelAttribute(): ?string
    {
        return $this->snapshot['risk_level'] ?? null;
    }

    public function getCategoryScoresAttribute(): array
    {
        return $this->snapshot['category_scores'] ?? [];
    }

    public function getAnswersAttribute(): array
    {
        return $this->snapshot['answers'] ?? [];
    }

    /**
     * ===============================
     * LABEL CHANGE TYPE
     * ===============================
     */
    public function getChangeTypeLabelAttribute(): string
    {
        return match ($this->change_type) {
            'status' => 'Perubahan Status Vendor',
            'tier'   => 'Perubahan Tier Criticality',
            'result' => 'Snapshot Hasil Assessment',
            default  => '-',
        };
    }

    /**
     * ===============================
     * FORMATTED VALUE (OLD / NEW)
     * ===============================
     */

    public function getOldValueFormattedAttribute(): string
    {
        return $this->formatValue($this->old_value);
    }

    public function getNewValueFormattedAttribute(): string
    {
        return $this->formatValue($this->new_value);
    }

    private function formatValue(?array $value): string
    {
        if (!$value) {
            return '-';
        }

        return match ($this->change_type) {

            'status' => $value['vendor_status'] ?? '-',

            'result' => isset($value['total_score'], $value['risk_level'])
                ? $value['total_score'] . '% (' . strtoupper($value['risk_level']) . ')'
                : '-',

            'tier' => $value['tier_criticality'] ?? '-',

            default => '-',
        };
    }
}