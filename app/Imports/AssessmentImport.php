<?php
namespace App\Imports;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class AssessmentImport implements ToCollection
{
    protected $assessment;

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

            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                $cell0 = strtoupper(trim($row[0]));

                // Deteksi baris kategori
                if (str_starts_with($row[0], 'Kategori: ')) {
    $rawName = trim(str_replace('Kategori: ', '', $row[0]));
    // Ambil hanya nama kategori sebelum "(" atau "-"
    $categoryName = preg_split('/[\(-]/', $rawName)[0];
    $categoryName = trim($categoryName);

    $category = Category::where('name', $categoryName)->first();
    $currentCategoryId = $category?->id;

    if (!$category) {
        Log::info("Kategori tidak ditemukan: " . $categoryName);
    }
    continue;
}

                $cell0 = strtoupper(trim($row[0]));
if (empty($cell0) || $cell0 === 'PERTANYAAN' || str_starts_with($cell0, 'SAYA YANG')) {
    continue;
}



                // Proses pertanyaan
                if ($currentCategoryId) {
                    $questionText   = trim($row[0]);
                    $answerText     = trim($row[1] ?? '');
                    $attachmentInfo= trim($row[2] ?? '');

                    $question = Question::where('question_text', $questionText)
                        ->where('category_id', $currentCategoryId)
                        ->first();

                    if (!$question) {
    Log::info("Question tidak ditemukan: " . $questionText . " | Kategori ID: " . $currentCategoryId);
}

                    if ($question) {
                        // hitung score dari sistem
                        $score = 0;
                        if ($question->question_type === 'pilihan' && $answerText) {
                            $option = $question->options()
                                ->where('option_text', $answerText)
                                ->first();
                            $score = $option?->score ?? 0;
                        }

                        $newAnswers[] = [
                            'assessment_id'   => $this->assessment->id,
                            'question_id'     => $question->id,
                            'answer_text'     => $answerText,
                            'score'           => $score,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }

            if (count($newAnswers) === 0) {
                throw new \Exception("Tidak ada data yang berhasil diimport");
            }

            // baru hapus & simpan
            $this->assessment->answers()->delete();
            Answer::insert($newAnswers);

            // hitung ulang score & risk level di sistem
            $this->assessment->calculateCategoryScores();
            $this->assessment->refresh();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
