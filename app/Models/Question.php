<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_text',
        'question_type',
        'clue',
        'indicator',
        'has_attachment',
        'attachment_text',
        'is_active',
        'order',
    ];

    protected $casts = [
        'indicator' => 'array',
        'has_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];
// App\Models\Question.php
public function scopeByIndicator($query, $indicator)
{
    return $query->where(function($q) use ($indicator) {
        $q->whereJsonContains('indicator', $indicator)
          ->orWhere('indicator', 'LIKE', "%{$indicator}%");
    });
}
//     // app/Models/Question.php
// public function scopeByIndicator($query, $level)
// {
//     if (!$level) return $query;
    
//     return $query->whereJsonContains('indicator', $level);
// }



    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}