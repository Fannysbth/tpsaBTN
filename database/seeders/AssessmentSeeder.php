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
        Assessment::truncate();
        Answer::truncate();

        $this->createCurrentMonthAssessments();
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

        $questions = Question::with('options', 'category')->get();

        foreach ($companies as $index => $company) {

            $assessment = Assessment::create([
                'company_name' => $company,
                'assessment_date' => Carbon::now()->subDays($index),
                'total_score' => 0,
                'notes' => 'Assessment bulan berjalan',
            ]);

            $this->generateAnswers($assessment, $questions);
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
        ];

        $questions = Question::with('options', 'category')->get();

        for ($month = 1; $month <= 3; $month++) {
            foreach ($companies as $index => $company) {

                $assessment = Assessment::create([
                    'company_name' => $company . ' ' . $month,
                    'assessment_date' => Carbon::now()->subMonths($month)->addDays($index),
                    'total_score' => 0,
                    'notes' => 'Assessment bulan sebelumnya',
                ]);

                $this->generateAnswers($assessment, $questions);
            }
        }
    }

    private function generateAnswers($assessment, $questions)
    {
        $totalScore = 0;
        $categoryScores = [];

        foreach ($questions as $question) {

            $answerData = $this->generateAnswerByQuestion($question);

            Answer::create([
                'assessment_id' => $assessment->id,
                'question_id' => $question->id,
                'answer_text' => $answerData['answer'],
                'score' => $answerData['score'],
                'attachment_path' => $question->attachment_text ? 'dokumen/sample.pdf' : null,
            ]);

            $totalScore += $answerData['score'];

            // hitung score per kategori
            $categoryName = $question->category->name;
            $categoryScores[$categoryName] = ($categoryScores[$categoryName] ?? 0) + $answerData['score'];
        }

        $assessment->update([
            'total_score' => $totalScore,
            'risk_level' => $this->calculateRiskLevel($totalScore),
            'category_scores' => json_encode($categoryScores),
        ]);
    }

    private function generateAnswerByQuestion($question)
    {
        // PERTANYAAN PILIHAN
        if ($question->question_type === 'pilihan') {

            $option = $question->options->random();

            return [
                'answer' => $option->option_text,
                'score' => $option->score,
            ];
        }

        // PERTANYAAN ISIAN
        $dummyAnswers = [
            'Nama legal perusahaan' => 'PT Contoh Sejahtera',
            'Website perusahaan' => 'https://www.contoh.co.id',
            'Tanggal berdiri perusahaan' => '2018-06-12',
            'Lokasi perusahaan (kantor utama)' => 'Jakarta',
            'Struktur organisasi perusahaan' => 'Direktur Utama, IT Manager, Finance',
            'Penanggung jawab perusahaan (CEO)' => 'Budi Santoso',
            'Jumlah karyawan IT' => '15',
        ];

        return [
            'answer' => $dummyAnswers[$question->question_text] ?? 'Data tersedia',
            'score' => 0,
        ];
    }

    private function calculateRiskLevel($score)
    {
        if ($score >= 7) return 'high';
        if ($score >= 4) return 'medium';
        return 'low';
    }
}
