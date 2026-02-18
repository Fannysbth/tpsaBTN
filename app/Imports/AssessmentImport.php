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
use Illuminate\Validation\ValidationException;

class AssessmentImport implements ToCollection
{
    protected Assessment $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment;
    }

    public function collection(Collection $rows)
    {
        // =========================
        // VALIDASI FILE KOSONG
        // =========================
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => ['File Excel kosong atau tidak terbaca.']
            ]);
        }

        // Minimal harus punya header + 1 baris data
        if ($rows->count() < 2) {
            throw ValidationException::withMessages([
                'file' => ['Format Excel tidak sesuai. Data tidak ditemukan.']
            ]);
        }

        DB::beginTransaction();

        try {

            $newAnswers = [];
            $currentCategoryId = null;
            $foundValidRow = false;
            $errors = []; 

            // Ambil semua question id milik assessment ini (sekali saja di luar loop lebih bagus)
// =========================
// BUILD DAFTAR PERTANYAAN VALID BERDASARKAN CATEGORY & INDICATOR
// =========================

$allowedQuestions = collect();

$categoryScores = $this->assessment->category_scores ?? [];

foreach ($categoryScores as $categoryId => $data) {

    $indicator = $data['indicator'] ?? null;

    if (!$indicator) continue;

    $questions = Question::where('category_id', $categoryId)
        ->where('is_active', true)
        ->where(function($query) use ($indicator) {
            $query->whereJsonContains('indicator', $indicator)
                  ->orWhere('indicator', 'LIKE', "%{$indicator}%");
        })
        ->get();

    $allowedQuestions = $allowedQuestions->merge($questions);
}

// keyBy supaya bisa dicari cepat tanpa query ulang
$allowedQuestions = $allowedQuestions->keyBy(function($q) {
    return trim($q->question_text);
});




            foreach ($rows as $index => $row) {

                $row = $row->toArray();

                // =========================
                // VALIDASI STRUKTUR KOLOM
                // =========================
                if ($index === 0) {
                    if (count($row) < 5) {
                        throw ValidationException::withMessages([
                            'file' => ['Format kolom Excel tidak sesuai. Minimal harus 5 kolom.']
                        ]);
                    }
                    continue;
                }

                // =========================
                // VALIDASI ROW TIDAK VALID
                // =========================
                if (!is_array($row) || count($row) < 3) {
                    continue;
                }

                // =========================
                // DETEKSI BARIS KATEGORI
                // =========================
                if (!empty($row[0]) && str_starts_with(trim($row[0]), 'Kategori:')) {

    $fullText = trim($row[0]);

    // Ambil nama kategori
    preg_match('/Kategori:\s*(.*?)\s*\(/', $fullText, $catMatch);
    $categoryName = $catMatch[1] ?? null;

    // Ambil indicator
    preg_match('/Indikator:\s*(.*?)\)/', $fullText, $indMatch);
    $indicatorFromExcel = strtoupper(trim($indMatch[1] ?? ''));

    if (!$categoryName || !$indicatorFromExcel) {
        throw ValidationException::withMessages([
            'file' => ['Format Kategori / Indikator tidak sesuai.']
        ]);
    }

    $category = Category::where('name', trim($categoryName))->first();

    if (!$category) {
        throw ValidationException::withMessages([
            'file' => ["Kategori '{$categoryName}' tidak ditemukan di sistem."]
        ]);
    }

    // 🔥 VALIDASI DENGAN ASSESSMENT
    $assessmentIndicator = strtoupper(
        $this->assessment->category_scores[$category->id]['indicator'] ?? ''
    );

    if ($assessmentIndicator !== $indicatorFromExcel) {
        throw ValidationException::withMessages([
            'file' => [
                "Assessment yang kamu upload tidak sesuai"
            ]
        ]);
    }

    $currentCategoryId = $category->id;

    continue;
}

                // =========================
                // STOP AREA TTD
                // =========================
                $firstCell = strtoupper(trim((string)($row[2] ?? '')));
                if (
                    str_starts_with($firstCell, 'SAYA YANG') ||
                    str_contains($firstCell, 'TTD') ||
                    str_contains($firstCell, 'NAMA PERUSAHAAN')
                ) {
                    break;
                }

                if (!$currentCategoryId) {
                    continue;
                }

                $questionText = trim((string)($row[2] ?? ''));
                $answerText   = trim((string)($row[4] ?? ''));

                if ($questionText === '' || strtoupper($questionText) === 'PERTANYAAN') {
                    continue;
                }

                $foundValidRow = true;

                // =========================
                // VALIDASI QUESTION ADA
                // =========================
                // Ambil semua question milik assessment ini


$question = $allowedQuestions->get($questionText);

if (!$question) {
    $errors[] = "Pertanyaan '{$questionText}' tidak sesuai dengan kategori dan indikator assessment ini.";
    continue;
}




                // =========================
                // VALIDASI JAWABAN PILIHAN
                // =========================
                if ($question->question_type === 'pilihan') {

    $option = $question->options()
        ->whereRaw('LOWER(TRIM(option_text)) = ?', [strtolower(trim($answerText))])
        ->first();

    // Kalau option tidak ditemukan → score 0
    $score = $option?->score ?? 0;

} else {
    $score = 0;
}


                // Ambil score dari kolom terakhir jika ada
                if (count($row) > 6) {
                    $scoreFromExcel = trim((string)end($row));
                    if (is_numeric($scoreFromExcel)) {
                        $score = (float)$scoreFromExcel;
                    }
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

            // =========================
            // VALIDASI TIDAK ADA DATA VALID
            // =========================
            if (!$foundValidRow) {
                throw ValidationException::withMessages([
                    'file' => ['Tidak ditemukan data pertanyaan yang valid di dalam file.']
                ]);
            }

            // PRIORITAS tampilkan error sebenarnya dulu
if (!empty($errors)) {
    DB::rollBack();

    throw ValidationException::withMessages([
        'file' => $errors
    ]);
}

if (empty($newAnswers)) {
    throw ValidationException::withMessages([
        'file' => ['Tidak ada jawaban yang berhasil diimport.']
    ]);
}



            // =========================
            // SIMPAN DATA
            // =========================
            $this->assessment->answers()->delete();
            Answer::insert($newAnswers);

            $this->assessment->load('answers.question.options');
            $this->assessment->calculateCategoryScores();
            $this->assessment->refresh();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error('Import gagal: ' . $e->getMessage());

            throw $e;
        }
    }
}
