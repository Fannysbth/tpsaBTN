<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'criteria'];

     protected $casts = [
        'criteria' => 'array', // ini otomatis bikin $category->criteria jadi array
    ];

   // Tambahkan relasi
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    // Relasi khusus untuk activeQuestions
    public function activeQuestions()
    {
        return $this->hasMany(Question::class)->where('is_active', 1);
    }
}
