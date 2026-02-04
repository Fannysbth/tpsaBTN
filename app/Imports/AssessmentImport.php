<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentImport implements ToCollection
{
    protected Assessment $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $newAnswers = [];
            $currentCategoryId = null;

            foreach ($rows as $index => $row) {

                // Skip header
                if ($index === 0) {
                    continue;
                }

                // Pastikan row array
                $row = $row->toArray();

                // =========================
                // DETEKSI BARIS KATEGORI
                // =========================
                if (!empty($row[0]) && str_starts_with(trim($row[0]), 'Kategori:')) {

                    $raw = trim(str_replace('Kategori:', '', $row[0]));
                    $categoryName = preg_split('/[\(\-]/', $raw)[0];
                    $categoryName = trim($categoryName);

                    $category = Category::where('name', $categoryName)->first();

                    if (!$category) {
                        Log::warning("Kategori tidak ditemukan: {$categoryName}");
                        $currentCategoryId = null;
                        continue;
                    }

                    $currentCategoryId = $category->id;
                    continue;
                }

                // =========================
                // STOP JIKA MASUK AREA TTD
                // =========================
                $firstCell = strtoupper(trim((string)($row[2] ?? '')));
                if (
                    str_starts_with($firstCell, 'SAYA YANG') ||
                    str_contains($firstCell, 'TTD') ||
                    str_contains($firstCell, 'NAMA PERUSAHAAN')
                ) {
                    break;
                }

                // =========================
                // VALIDASI BARIS PERTANYAAN
                // =========================
                if (!$currentCategoryId) {
                    continue;
                }

                $questionText = trim((string)($row[2] ?? '')); // kolom C
                $answerText   = trim((string)($row[4] ?? '')); // kolom E

                if ($questionText === '' || $questionText === 'PERTANYAAN') {
                    continue;
                }

                // Ambil score dari kolom terakhir (kalau ada)
                $scoreFromExcel = null;
                if (count($row) > 6) {
                    $scoreFromExcel = trim((string)end($row));
                    $scoreFromExcel = is_numeric($scoreFromExcel)
                        ? (float)$scoreFromExcel
                        : null;
                }

                // =========================
                // CARI QUESTION
                // =========================
                $question = Question::where('question_text', $questionText)
                    ->where('category_id', $currentCategoryId)
                    ->first();

                if (!$question) {
                    Log::warning("Question tidak ditemukan: {$questionText}");
                    continue;
                }

                // =========================
                // HITUNG SCORE
                // =========================
                $score = 0;

                // 1️⃣ Prioritas score dari Excel
                if ($scoreFromExcel !== null) {
                    $score = $scoreFromExcel;
                }
                // 2️⃣ Hitung dari option jika pilihan
                elseif ($question->question_type === 'pilihan' && $answerText !== '') {
                    $option = $question->options()
                        ->where('option_text', $answerText)
                        ->first();

                    $score = $option?->score ?? 0;
                }

                $newAnswers[] = [
                    'assessment_id' => $this->assessment->id,
                    'question_id'   => $question->id,
                    'answer_text'   => $answerText,
                    'score'         => $score,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            if (empty($newAnswers)) {
                throw new \Exception('Tidak ada jawaban yang berhasil diimport');
            }

            // =========================
            // SIMPAN DATA
            // =========================
            $this->assessment->answers()->delete();
            Answer::insert($newAnswers);

            // Hitung ulang total & risk level
            $this->assessment->calculateCategoryScores();
            $this->assessment->refresh();

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
