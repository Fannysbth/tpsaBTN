<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionnaireImport implements ToCollection
{
    public array $importData = [];
    public array $errors = [];

    protected array $excelQuestions = [];

    public function collection(Collection $rows)
    {
        if ($rows->count() < 3) {
            $this->errors[] = 'Format Excel tidak valid.';
            return;
        }

        /**
         * ==============================
         * 1. PARSE EXCEL
         * ==============================
         */
        $currentCategory = null;
        $currentIndex = null;

        foreach ($rows as $i => $row) {

            if ($i < 2) continue; // skip 2 header row
            $excelRow = $i + 1;

            $A = $row[0] ?? null; // Sub / Category
            $B = $row[1] ?? null; // No
            $C = $row[2] ?? null; // Question text
            $D = $row[3] ?? null; // :
            $E = $row[4] ?? null; // Option / Clue
            $F = $row[5] ?? null; // Score
            $lastCol = count($row) - 1;
            $attachment = $row[$lastCol] ?? null;

            /**
             * CATEGORY HEADER
             * A != '' && B,C,D kosong
             */
            if (
                is_string($A) && trim($A) !== '' &&
                $B === null && $C === null && $D === null
            ) {
                $currentCategory = Category::where('name', trim($A))->first();

                if (!$currentCategory) {
                    $this->errors[] = "Baris {$excelRow}: Category '{$A}' tidak ditemukan.";
                }
                continue;
            }

            if (!$currentCategory) continue;

            /**
             * QUESTION UTAMA
             * B numeric && D === ':'
             */
            if (is_numeric($B) && trim((string)$D) === ':') {

                $question = [
                    'category_id'   => $currentCategory->id,
                    'category_name' => $currentCategory->name,
                    'sub'           => trim((string)$A) ?: null,
                    'no'            => (int)$B,
                    'question_text' => trim((string)$C),
                    'question_type' => 'isian', // default isian dulu
                    'indicator'     => [], 
                    'attachment'    => ($attachment === '-' ? null : $attachment),
                    'options'       => [],
                ];

                $this->excelQuestions[] = $question;
                $currentIndex = array_key_last($this->excelQuestions);
                continue;
            }

            /**
             * OPTION LANJUTAN
             * B dan C kosong, E ada → baris tambahan opsi
             */
            if (
                $currentIndex !== null &&
                $B === null && $C === null &&
                trim((string)$E) !== ''
            ) {
                $this->excelQuestions[$currentIndex]['options'][] = [
                    'text'  => trim((string)$E),
                    'score' => is_numeric($F) ? (int)$F : null,
                ];

                // kalau ada opsi tambahan → ubah question_type jadi pilihan
                $this->excelQuestions[$currentIndex]['question_type'] = 'pilihan';
            }
        }

        if (empty($this->excelQuestions)) {
            $this->errors[] = '0 question terbaca dari Excel.';
            return;
        }

        /**
         * ==============================
         * 2. LOAD DATABASE QUESTIONS
         * ==============================
         */
        $dbQuestions = Question::with('options')
            ->get()
            ->map(function ($q) {
                return [
                    'id'            => $q->id,
                    'category_id'   => $q->category_id,
                    'sub'           => $q->sub,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'indicator'     => json_decode($q->indicator, true) ?? [],
                    'attachment'    => $q->attachment_text,
                    'options'       => $q->options->map(fn ($o) => [
                        'text'  => $o->option_text,
                        'score' => $o->score,
                    ])->toArray(),
                ];
            })
            ->keyBy(fn ($q) => $this->signature($q))
            ->toArray();

        /**
         * ==============================
         * 3. DIFF ENGINE (FILTERED)
         * ==============================
         */
        $preview = [];

        // ADD & UPDATE
        foreach ($this->excelQuestions as $q) {
            $sig = $this->signature($q);

            // sama persis → SKIP
            if (isset($dbQuestions[$sig])) {
                continue;
            }

            // cari kemungkinan UPDATE (question_text sama)
            $matchedDb = collect($dbQuestions)->first(fn ($dbQ) =>
                trim($dbQ['question_text']) === trim($q['question_text']) &&
                $dbQ['category_id'] === $q['category_id']
            );

            if ($matchedDb) {
                $preview[] = $q + [
                    'id'     => $matchedDb['id'],
                    'action' => 'update',
                ];
            } else {
                $preview[] = $q + [
                    'action' => 'add',
                ];
            }
        }

        // DELETE (yang benar-benar hilang)
        $excelSignatures = collect($this->excelQuestions)
            ->map(fn ($q) => $this->signature($q))
            ->toArray();

        foreach ($dbQuestions as $sig => $dbQ) {
            if (!in_array($sig, $excelSignatures, true)) {
                $preview[] = [
                    'id'            => $dbQ['id'],
                    'category_id'   => $dbQ['category_id'],
                    'question_text' => $dbQ['question_text'],
                    'action'        => 'delete',
                ];
            }
        }

        /**
         * ==============================
         * 4. FINAL PREVIEW
         * ==============================
         */
        $this->importData = $preview;

        if (empty($this->importData)) {
            $this->errors[] = 'Tidak ada perubahan data.';
        }
    }

    /**
     * ==============================
     * QUESTION SIGNATURE
     * ==============================
     */
    protected function signature(array $q): string
    {
        return md5(json_encode([
            'category_id'   => $q['category_id'],
            'sub'           => trim((string)($q['sub'] ?? '')),
            'question_text' => trim((string)$q['question_text']),
            'question_type' => $q['question_type'] ?? null,
            'indicator'     => $q['indicator'] ?? [],
            'attachment'    => $q['attachment'] ?? null,
            'options'       => collect($q['options'] ?? [])
                ->map(fn ($o) => [
                    trim((string)$o['text']),
                    $o['score'],
                ])
                ->values()
                ->toArray(),
        ], JSON_UNESCAPED_UNICODE));
    }
}
