<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AssessmentHistory;
use Illuminate\Support\Facades\Log;

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
    Log::info('===== TIER CALCULATION DEBUG START =====');

    $scores = collect($this->category_scores ?? []);

    Log::info('Raw Category Scores', [
        'category_scores' => $scores->toArray()
    ]);

    $map = [
        'low' => 1,
        'medium' => 2,
        'high' => 3
    ];

    /*
    --------------------------------------------------
    CONVERT INDICATOR → NUMERIC RISK VALUE
    --------------------------------------------------
    */

    $values = $scores->pluck('indicator')
        ->filter()
        ->map(function ($i) use ($map) {
            return $map[$i] ?? 0;
        })
        ->values();

    Log::info('Mapped Risk Values', [
        'values' => $values->toArray(),
        'count' => $values->count()
    ]);

    if ($values->count() === 0) {
        $this->tier_criticality = 'Tier 1';

        Log::info('No indicator found → Default Tier 1');
        Log::info('===== TIER CALCULATION DEBUG END =====');

        return;
    }


    /*
    --------------------------------------------------
    TIER DECISION
    --------------------------------------------------
    */

    if ($values->sum() <= 3) {
        $tier = 'Tier 3';
    } elseif ($values->sum() < 7) {
        $tier = 'Tier 2';
    } else {
        $tier = 'Tier 1';
    }

    Log::info('Computed Tier Criticality', [
        'tier' => $tier
    ]);

    $this->tier_criticality = $tier;

    Log::info('===== TIER CALCULATION DEBUG END =====');
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

        $existing = $this->category_scores[$category->id] ?? [];

$categoryScores[$category->id] = [
    'indicator'     => $indicator,
    'assessor'      => $existing['assessor'] ?? null,
    'justification' => $existing['justification'] ?? null,
    'actual_score'  => $actualScore,
    'max_score'     => $maxScore,
    'score'         => $percentage,
];
    }

    // === SIMPAN CATEGORY SCORE ===
    $this->category_scores = $categoryScores;
    // hitung tier
    $this->calculateTierCriticality();
    $this->save();


    // === TOTAL SCORE = RATA-RATA CATEGORY ===
    $totalCategories = count($categoryScores);
    $this->total_score = $totalCategories > 0
        ? round(array_sum(array_column($categoryScores, 'score')) / ($totalCategories), 2)
        : 0;

    // === RISK LEVEL ===
    $this->calculateRiskLevel();

    $this->save();
}


// App/Models/Assessment.php
}