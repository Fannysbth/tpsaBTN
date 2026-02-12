<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_no',
        'question_text',
        'question_type',
        'clue',
        'sub',
        'indicator',
        'has_attachment',
        'attachment_text',
        'is_active',
        'order_index',
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
    })->orderBy('order_index', 'asc'); // ganti "order" -> "order_index"
}




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