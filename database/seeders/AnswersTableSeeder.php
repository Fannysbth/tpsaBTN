<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;

class AnswersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel
        Answer::truncate();

        // Ambil semua assessment yang sudah ada
        $assessments = Assessment::all();

        foreach ($assessments as $assessment) {

            // Ambil semua question aktif
            $questions = Question::where('is_active', true)->get();

            foreach ($questions as $question) {
                $assessment->answers()->create([
                    'question_id'     => $question->id,
                    'answer_text'     => "null",
                    'score'           => 0,
                    'attachment_path' => null,
                ]);
            }
        }
    }
}