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
    protected int $indicatorStartCol = 6; // setelah F
    protected int $indicatorEndCol;
    protected int $attachmentCol;

    protected function parseScore($value): ?float
{
    if ($value === null || $value === '') return null;

    // ganti koma ke titik (Excel ID locale)
    $normalized = str_replace(',', '.', $value);

    return is_numeric($normalized) ? (float)$normalized : null;
}



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
        $headerRow = $rows[1]; // header baris ke-2 (levels)

        $lastColIndex = count($headerRow) - 1;

        $this->attachmentCol = $lastColIndex;
        $this->indicatorEndCol = $lastColIndex - 1;


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

            // ==============================
// PARSE INDICATOR (V)
// ==============================
$indicator = [];

for ($col = $this->indicatorStartCol; $col <= $this->indicatorEndCol; $col++) {

    if (strtoupper(trim((string)($row[$col] ?? ''))) === 'V') {

        // nama indicator diambil dari header baris ke-2
        $indicatorName = strtolower(trim((string)($headerRow[$col] ?? '')));

        if ($indicatorName !== '') {
            $indicator[] = $indicatorName;
        }
    }
}


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
        'question_type' => 'isian', // default
        'indicator'     => $indicator,
        'clue'          => trim((string)$E) ?: null,
        'attachment'    => ($attachment === '-' ? null : $attachment),

        // 🔥 option pertama disimpan dulu tapi belum dipakai
        '_first_option' => [
    'text'  => trim((string)$E) ?: null,
    'score' => $this->parseScore($F),
],
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
    // 🔥 pertama kali ketemu baris lanjutan
    if ($this->excelQuestions[$currentIndex]['question_type'] === 'isian') {

        // ubah jadi pilihan
        $this->excelQuestions[$currentIndex]['question_type'] = 'pilihan';

        // pindahkan option pertama dari baris utama
        $first = $this->excelQuestions[$currentIndex]['_first_option'];

if (!empty($first['text'])) {
    $this->excelQuestions[$currentIndex]['options'][] = [
        'text'  => $first['text'],
        'score' => $first['score'], // 🔥 score ikut
    ];
}


        // clue dibuang
        $this->excelQuestions[$currentIndex]['clue'] = null;
    }

    // option berikutnya
    $this->excelQuestions[$currentIndex]['options'][] = [
        'text'  => trim((string)$E),
        'score' => $this->parseScore($F),
    ];
}


        }

        foreach ($this->excelQuestions as &$q) {
    unset($q['_first_option']);
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
                    $o['score'] !== null ? (float)$o['score'] : null,
                ])
                ->values()
                ->toArray(),
        ], JSON_UNESCAPED_UNICODE));
    }
}
