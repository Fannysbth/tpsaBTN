<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\QuestionOption;

class QuestionOptionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel + reset auto increment
        QuestionOption::truncate();

        $options = [

            // Contoh: Question No 1
            '1' => [
                ['text' => 'Ya', 'score' => 1],
                ['text' => 'Tidak', 'score' => 3],
            ],

            // Contoh: Question No 2 (Jenis Bisnis)
            '2' => [
                ['text' => 'IT / Teknologi Informasi (namun tidak terbatas pada)', 'score' => 0],
                ['text' => 'Business Process Outsourcing (BPO) / Operational Support', 'score' => 0],
                ['text' => 'Financial & Payment Services', 'score' => 0],
                ['text' => 'Information & Data Service', 'score' => 0],
                ['text' => 'HR, Training & Professional Services', 'score' => 0],
                ['text' => 'Non-IT / Non-Core Support Services', 'score' => 0],
                ['text' => 'Marketing & Communication', 'score' => 0],
                ['text' => 'Hardware & Equipment Provider', 'score' => 0],
                ['text' => 'Legal & Compliance Related Services', 'score' => 0],
                ['text' => 'Others', 'score' => 0],
            ],

            // Tambahkan sesuai question_no kamu
        ];

        foreach ($options as $questionNo => $questionOptions) {

            $question = Question::where('question_no', $questionNo)->first();

            if (!$question) {
                $this->command->warn("Question No {$questionNo} tidak ditemukan. Skip...");
                continue;
            }

            foreach ($questionOptions as $opt) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'score'       => $opt['score'],
                ]);
            }
        }
    }
}