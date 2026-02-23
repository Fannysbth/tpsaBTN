<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AssessmentHistory;

class Assessment extends Model
{
    use HasFactory;
  

protected $fillable = [
    'company_name',
    'assessment_date',
    'evaluated_at',
    'total_score',
    'risk_level',
    'tier_criticality',
    'vendor_status',
    'category_scores',
    'notes',
    'indicators'
];

protected $casts = [
    'category_scores' => 'array',
    'indicators' => 'array',
    'assessment_date' => 'date',
    'evaluated_at' => 'date',
];



  public function categories()
{
    return $this->hasMany(Category::class);
}

public function histories()
{
    return $this->hasMany(AssessmentHistory::class);
}

public function calculateTierCriticality(): void
{
    $scores = collect($this->category_scores ?? []);

    $map = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
    ];

    $total = $scores->pluck('indicator')
                    ->filter()
                    ->map(fn($i) => $map[$i] ?? 0)
                    ->sum();

    if ($total <= 3) {
        $tier = 3;
    } elseif ($total < 7) {
        $tier = 2;
    } else {
        $tier = 1;
    }

    $this->tier_criticality = $tier;
}


    public function answers()
{
    return $this->hasMany(Answer::class);
}


    public function calculateRiskLevel()
{
    if ($this->total_score >= 80) {
        $this->risk_level = 'low';   // risiko rendah
        $this->notes = 'Kepatuhan terhadap regulasi dan standar keamanan sangat baik. Tidak terdapat isu ketidakpatuhan terhadap regulasi dan best practice.';
    } elseif ($this->total_score >= 50) {
        $this->risk_level = 'medium'; // risiko sedang
        $this->notes = 'Kepatuhan terhadap regulasi dan standar keamanan cukup baik. Terdapat isu ketidakpatuhan namun masih dalam batas toleransi Bank.';
    } else {
        $this->risk_level = 'high';  // risiko tinggi
        $this->notes = 'Kepatuhan terhadap regulasi dan standar keamanan buruk.';
    }

    $this->save();
}

public function getRiskLevelLabelAttribute()
{
    return match ($this->risk_level) {
        'low'    => 'Sangat Memadai',
        'medium' => 'Cukup Memadai',
        'high'   => 'Kurang Memadai',
        default  => '-',
    };


}

// Di dalam class Assessment, tambahkan method:

/**
 * Get compliance level for a score
 */
public static function getComplianceLevel($score): string
{
    if ($score >= 80) return 'Sangat Memadai';
    if ($score >= 50) return 'Cukup Memadai';
    return 'Kurang Memadai';
}



/**
 * Get heatmap data for category × vendor
 */
public function getCategoryVendorHeatmapData(): array
{
    $categories = Category::orderBy('id')->get();
    $categoryScores = $this->category_scores ?? [];
    
    $data = [];
    foreach ($categories as $category) {
        $score = $categoryScores[$category->id]['score'] ?? 0;
        $data[$category->name] = [
            'score' => $score,
            'level' => self::getComplianceLevel($score),
            'color' => self::getComplianceColor($score)
        ];
    }
    
    return $data;
}

public function getHeatmapCellAttribute(): array
{
    return [
        'x' => $this->inherent_risk,               // Low / Medium / High
        'y' => $this->risk_level_label,            // Sangat / Cukup / Kurang
        'company' => $this->company_name,
        'score' => $this->total_score
    ];
}


public function getInherentRiskAttribute(): string
{
    $scores = collect($this->category_scores ?? []);

    // ambil indikator dari kategori-kategori TPSA
    $indicators = $scores->pluck('indicator')->filter();

    // mapping low=1, medium=2, high=3
    $map = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
    ];

    $total = $indicators->map(fn($i) => $map[$i] ?? 0)->sum();

    if ($total >= 8) return 'high';
    if ($total >= 5) return 'medium';
    return 'low';
}

// Pastikan method ini ada di model Assessment
public static function getComplianceColor($score): string
{
    if ($score >= 80) return '#4AD991'; // Hijau
    if ($score >= 50) return '#FEC53D'; // Kuning
    if ($score > 0) return '#FF6B6B';   // Merah
    return '#f8f9fa';                   // Abu-abu (tidak ada data)
}


// App\Models\Assessment.php
public static function scoreToRiskLabel(float $score): string
{
    if ($score >= 80) {
        return 'Sangat Memadai';
    } elseif ($score >= 50) {
        return 'Cukup Memadai';
    }
    return 'Kurang Memadai';
}

protected static function booted()
{
    static::updating(function ($assessment) {

        // STATUS CHANGE
        if ($assessment->isDirty('vendor_status')) {
            $assessment->histories()->create([
                'change_type' => 'status',
                'old_value'   => $assessment->getOriginal('vendor_status'),
                'new_value'   => $assessment->vendor_status,
            ]);
        }

        // TIER CHANGE
        if ($assessment->isDirty('tier_criticality')) {
            $assessment->histories()->create([
                'change_type' => 'tier',
                'old_value'   => $assessment->getOriginal('tier_criticality'),
                'new_value'   => $assessment->tier_criticality,
            ]);
        }

        // RESULT CHANGE (score / risk level)
        if ($assessment->isDirty('total_score') || $assessment->isDirty('risk_level')) {

            $old = $assessment->getOriginal('total_score') . 
                   ' (' . $assessment->getOriginal('risk_level') . ')';

            $new = $assessment->total_score . 
                   ' (' . $assessment->risk_level . ')';

            $assessment->histories()->create([
                'change_type' => 'result',
                'old_value'   => $old,
                'new_value'   => $new,
            ]);
        }
    });
}


public function calculateCategoryScores(): void
{
    $this->loadMissing('answers');
    
    $categories = Category::with(['questions.options'])->get();
    $categoryScores = [];

    foreach ($categories as $category) {

        // indikator yg dipilih perusahaan
        $indicator = $this->category_scores[$category->id]['indicator'] ?? null;

        if (!$indicator) {
            continue;
        }

        $actualScore = 0;
        $maxScore    = 0;

        foreach ($category->questions as $question) {

            // === FILTER PERTANYAAN SESUAI INDIKATOR ===
            $indicators = $question->indicator;

            
            if (is_string($indicators)) {
                $decoded = json_decode($indicators, true);
                $indicators = json_last_error() === JSON_ERROR_NONE
                    ? $decoded
                    : array_map('trim', explode(',', $indicators));
            }

            if (!in_array($indicator, $indicators ?? [])) {
                continue;
            }

            // === HANYA HITUNG PERTANYAAN PILIHAN ===
            if ($question->question_type !== 'pilihan') {
                continue;
            }

            // ambil jawaban user
            $answer = $this->answers->firstWhere('question_id', $question->id);

            // score maksimum dari option
            $questionMaxScore = $question->options->max('score') ?? 0;

            $maxScore += $questionMaxScore;
            $actualScore += $answer->score ?? 0;
        }

        $percentage = $maxScore > 0
            ? round(($actualScore / $maxScore) * 100, 2)
            : 0;

        $categoryScores[$category->id] = [
            'indicator'     => $indicator,
            'actual_score'  => $actualScore,
            'max_score'     => $maxScore,
            'score'         => $percentage,
        ];
    }

    // === SIMPAN CATEGORY SCORE ===
    $this->category_scores = $categoryScores;
    // hitung tier
    $this->calculateTierCriticality();


    // === TOTAL SCORE = RATA-RATA CATEGORY ===
    $totalCategories = count($categoryScores);
    $this->total_score = $totalCategories > 0
        ? round(array_sum(array_column($categoryScores, 'score')) / ($totalCategories-1), 2)
        : 0;

    // === RISK LEVEL ===
    $this->calculateRiskLevel();

    $this->save();
}


// App/Models/Assessment.php
}