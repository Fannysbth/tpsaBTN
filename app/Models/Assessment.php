<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name', 'assessment_date', 'total_score',
        'risk_level', 'category_scores', 'notes'
    ];

    protected $casts = [
        'category_scores' => 'array',
        'assessment_date' => 'date',
    ];

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

    public function calculateCategoryScores()
    {
        $categoryScores = [];
        $categories = Category::with(['questions' => function($query) {
            $query->where('is_active', true);
        }])->get();

        foreach ($categories as $category) {
            $categoryScore = $this->answers()
                ->whereHas('question', function($query) use ($category) {
                    $query->where('category_id', $category->id);
                })
                ->sum('score');

            $categoryScores[$category->id] = [
                'name' => $category->name,
                'score' => $categoryScore,
                'weight' => $category->weight,
                'weighted_score' => $categoryScore * $category->weight
            ];
        }

        $this->update(['category_scores' => $categoryScores]);
        return $categoryScores;
    }
}