<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Category;
use Carbon\Carbon;

class AssessmentSeeder extends Seeder
{
    public function run()
    {
        Assessment::truncate();
        Answer::truncate();

        $this->seedCurrentMonth();
        $this->seedPreviousMonths();

        $this->command->info('Assessment data seeded successfully!');
    }

    private function seedCurrentMonth()
    {
        $companies = [
            'PT. Maju Bersama',
            'CV. Sejahtera Abadi',
            'PT. Teknologi Indonesia',
            'UD. Jaya Makmur',
            'PT. Global Solution',
        ];

        foreach ($companies as $i => $company) {
            $this->createAssessment(
                $company,
                Carbon::now()->subDays($i)
            );
        }
    }

    private function seedPreviousMonths()
    {
        $companies = [
            'PT. Sinar Jaya',
            'CV. Anugerah',
            'PT. Mandiri Sejahtera',
            'UD. Berkah',
            'PT. Prima Utama',
        ];

        for ($month = 1; $month <= 3; $month++) {
            foreach ($companies as $i => $company) {
                $this->createAssessment(
                    $company . " {$month}",
                    Carbon::now()->subMonths($month)->addDays($i)
                );
            }
        }
    }

    private function createAssessment(string $company, Carbon $date)
    {
        $categories = Category::all();

        // === SET INDIKATOR PER CATEGORY ===
        $categoryScores = [];
        foreach ($categories as $category) {
            $categoryScores[$category->id] = [
                'indicator' => collect(['low', 'medium', 'high'])->random()
            ];
        }

        $assessment = Assessment::create([
            'company_name'    => $company,
            'assessment_date' => $date,
            'category_scores' => $categoryScores,
            'total_score'     => 0,
            'risk_level'      => null,
            'notes'           => 'Seeder dummy data',
        ]);

        $questions = Question::with('options')->get();

        foreach ($questions as $question) {
            $answer = $this->generateAnswer($question);

            Answer::create([
                'assessment_id' => $assessment->id,
                'question_id'   => $question->id,
                'answer_text'   => $answer['answer'],
                'score'         => $answer['score'],
            ]);
        }

        // 🔥 BIARKAN MODEL YANG HITUNG
        $assessment->calculateCategoryScores();
    }

    private function generateAnswer(Question $question): array
    {
        if ($question->question_type === 'pilihan' && $question->options->count()) {
            $option = $question->options->random();

            return [
                'answer' => $option->option_text,
                'score'  => $option->score,
            ];
        }

        // isian → tidak dihitung
        return [
            'answer' => 'Data tersedia',
            'score'  => 0,
        ];
    }
}
