<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            // Hapus jawaban lama
            $this->assessment->answers()->delete();

            $currentQuestion = null;
            $currentCategory = null;

            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                // Deteksi kategori
                if (strpos($row[0], 'Kategori: ') === 0) {
                    $currentCategory = str_replace('Kategori: ', '', $row[0]);
                    continue;
                }

                // Skip jika ini total score atau risk level
                if ($row[0] === 'TOTAL SCORE' || $row[0] === 'RISK LEVEL') {
                    continue;
                }

                // Cari pertanyaan berdasarkan teks
                $question = Question::where('question_text', $row[0])->first();
                if ($question) {
                    $answerText = $row[1] ?? '';
                    $score = $row[2] ?? 0;

                    Answer::create([
                        'assessment_id' => $this->assessment->id,
                        'question_id' => $question->id,
                        'answer_text' => $answerText,
                        'score' => $score
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}