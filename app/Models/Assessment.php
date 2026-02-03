<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Assessment extends Model
{
    use HasFactory;
  

    protected $fillable = [
    'company_name', 'assessment_date', 'total_score',
    'risk_level', 'category_scores', 'notes', 'indicators'
];

protected $casts = [
    'category_scores' => 'array',
    'indicators' => 'array',
    'assessment_date' => 'date',
];



  public function categories()
{
    return $this->hasMany(Category::class);
}



    public function answers()
{
    return $this->hasMany(Answer::class);
}

    public function calculateTotalScore()
    {
        $totalScore = $this->answers()->sum('score');
        $this->update(['total_score' => $totalScore]);
        $this->determineRiskLevel();
        return $totalScore;
    }

    public function determineRiskLevel()
    {
        $totalScore = $this->total_score;
        $riskLevel = 'low';
        
        if ($totalScore >= 70) {
            $riskLevel = 'high';
        } elseif ($totalScore >= 40) {
            $riskLevel = 'medium';
        }
        
        $this->update(['risk_level' => $riskLevel]);
        return $riskLevel;
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
 * Get compliance level color
 */
public static function getComplianceColor($score): string
{
    if ($score >= 80) return '#4AD991';
    if ($score >= 50) return '#FEC53D';
    return '#FF6B6B';
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


public function calculateCategoryScores()
{
    $categories = Category::with(['questions.options'])->get();
    $categoryScores = [];
    
    foreach ($categories as $category) {
        $indicator = $this->category_scores[$category->id]['indicator'] ?? null;
        
        if (!$indicator) {
            $categoryScores[$category->id] = [
                'score' => 0,
                'indicator' => null,
            ];
            continue;
        }
        
        // Filter pertanyaan berdasarkan indikator
        $filteredQuestions = $category->questions->filter(function($question) use ($indicator) {
            $indicators = $question->indicator;

// kalau string, bersihin dulu
if (is_string($indicators)) {
    $indicators = trim($indicators, "\""); // hapus """
    $decoded = json_decode($indicators, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $indicators = $decoded;
    } else {
        $indicators = array_map('trim', explode(',', $indicators));
    }
}

            
            return in_array($indicator, $indicators ?? []);
        });
        
        $totalMaxScore = 0;
        $totalActualScore = 0;
        
        foreach ($filteredQuestions as $question) {
    $answer = $this->answers->firstWhere('question_id', $question->id);

    // Max score: dari option, kalau tidak ada pakai nilai dari pertanyaan
    if ($question->options->isNotEmpty()) {
        $maxScore = $question->options->max('score');
    } else {
        $maxScore = $question->max_score ?? 0; // atau field lain yg kamu punya
    }

    $totalMaxScore += $maxScore;

    // Actual score: walau 0 tetap dihitung
    if ($answer) {
        $totalActualScore += $answer->score ?? 0;
    }
}

        
        // Hitung persentase (jika ada max score)
        $percentage = $totalMaxScore > 0 
            ? round(($totalActualScore / $totalMaxScore) * 100, 2)
            : 0;
        
        $categoryScores[$category->id] = [
            'score' => $percentage,
            'indicator' => $indicator,
            'actual_score' => $totalActualScore,
            'max_score' => $totalMaxScore
        ];
    }
    
    $this->category_scores = $categoryScores;
    
    // Hitung total score sebagai rata-rata semua kategori
    $totalCategories = count($categoryScores);
    if ($totalCategories > 0) {
        $sumScores = array_sum(array_column($categoryScores, 'score'));
        $this->total_score = round($sumScores / $totalCategories, 2);
    } else {
        $this->total_score = 0;
    }
    
    $this->calculateRiskLevel();
    $this->save();
}
// App/Models/Assessment.php
}