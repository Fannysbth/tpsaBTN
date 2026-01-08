<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'weight'];

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function activeQuestions()
    {
        return $this->hasMany(Question::class)->where('is_active', true)->orderBy('order');
    }
}