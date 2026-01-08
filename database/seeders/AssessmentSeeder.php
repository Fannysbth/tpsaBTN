<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\Answer;
use App\Models\Question;
use Carbon\Carbon;

class AssessmentSeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama
        Assessment::truncate();
        Answer::truncate();

        // Buat assessment contoh untuk bulan ini
        $this->createCurrentMonthAssessments();

        // Buat assessment contoh untuk bulan-bulan sebelumnya
        $this->createPreviousMonthsAssessments();

        $this->command->info('Assessment data seeded successfully!');
    }

    private function createCurrentMonthAssessments()
    {
        $companies = [
            'PT. Maju Bersama',
            'CV. Sejahtera Abadi',
            'PT. Teknologi Indonesia',
            'UD. Jaya Makmur',
            'PT. Global Solution',
        ];

        $questions = Question::with('options')->get();

        foreach ($companies as $index => $company) {
            $assessment = Assessment::create([
                'company_name' => $company,
                'assessment_date' => Carbon::now()->subDays($index),
                'total_score' => 0,
                'notes' => 'Assessment dilakukan secara menyeluruh',
            ]);

            $totalScore = 0;

            // Buat jawaban untuk setiap pertanyaan
            foreach ($questions as $question) {
                $answerData = $this->generateAnswer($question);
                
                $answer = Answer::create([
                    'assessment_id' => $assessment->id,
                    'question_id' => $question->id,
                    'answer_text' => $answerData['answer'],
                    'score' => $answerData['score'],
                    'attachment_path' => $question->has_attachment ? 'dokumen/sample.pdf' : null,
                ]);

                $totalScore += $answerData['score'];
            }

            // Update total score dan risk level
            $assessment->update([
                'total_score' => $totalScore,
                'risk_level' => $this->calculateRiskLevel($totalScore),
            ]);

            // Hitung category scores
            $assessment->calculateCategoryScores();
        }
    }

    private function createPreviousMonthsAssessments()
    {
        $companies = [
            'PT. Sinar Jaya',
            'CV. Anugerah',
            'PT. Mandiri Sejahtera',
            'UD. Berkah',
            'PT. Prima Utama',
            'CV. Cahaya Baru',
            'PT. Mitra Kerja',
            'UD. Sentosa',
            'PT. Nusantara',
            'CV. Gemilang',
        ];

        $questions = Question::with('options')->get();

        for ($i = 1; $i <= 3; $i++) {
            foreach ($companies as $index => $company) {
                $assessment = Assessment::create([
                    'company_name' => $company . ' ' . $i,
                    'assessment_date' => Carbon::now()->subMonths($i)->addDays($index),
                    'total_score' => 0,
                    'notes' => 'Assessment bulan lalu',
                ]);

                $totalScore = 0;

                foreach ($questions as $question) {
                    $answerData = $this->generateAnswer($question);
                    
                    $answer = Answer::create([
                        'assessment_id' => $assessment->id,
                        'question_id' => $question->id,
                        'answer_text' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'attachment_path' => $question->has_attachment ? 'dokumen/sample_' . $i . '.pdf' : null,
                    ]);

                    $totalScore += $answerData['score'];
                }

                $assessment->update([
                    'total_score' => $totalScore,
                    'risk_level' => $this->calculateRiskLevel($totalScore),
                ]);

                $assessment->calculateCategoryScores();
            }
        }
    }

    private function generateAnswer($question)
    {
        $answer = '';
        $score = 0;

        switch ($question->question_type) {
            case 'pilihan':
                $options = $question->options;
                $selectedOption = $options->random();
                $answer = $selectedOption->option_text;
                $score = $selectedOption->score;
                break;

            case 'checkbox':
                $selectedOptions = $question->options->random(rand(1, 3));
                $answer = json_encode($selectedOptions->pluck('option_text')->toArray());
                $score = $selectedOptions->sum('score');
                break;

            case 'isian':
                $answers = [
                    'Sudah diimplementasikan dengan baik',
                    'Dalam proses implementasi',
                    '85%',
                    'Sistem manual dengan excel',
                    'Rutin setiap bulan',
                    '10% per tahun',
                    'Backup harian dan cloud storage',
                    'Menggunakan firewall dan antivirus',
                    'Kepatuhan 100%',
                    'Risk assessment quarterly',
                ];
                $answer = $answers[array_rand($answers)];
                $score = rand(5, 10); // Random score untuk isian
                break;
        }

        return [
            'answer' => $answer,
            'score' => $score,
        ];
    }

    private function calculateRiskLevel($score)
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }
}