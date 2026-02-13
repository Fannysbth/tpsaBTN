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

    protected int $indicatorStartCol = 6;
    protected int $indicatorEndCol;
    protected int $attachmentCol;

    protected array $excelQuestions = [];

    protected function parseScore($value): ?float
    {
        if ($value === null || $value === '') return null;

        $normalized = str_replace(',', '.', $value);
        return is_numeric($normalized) ? (float)$normalized : null;
    }

    public function collection(Collection $rows)
    {
        /**
         * ===================================================
         * VALIDASI STRUKTUR FILE (TAMBAHAN)
         * ===================================================
         */

        // 1️⃣ Minimal baris
        if ($rows->count() < 3) {
            $this->errors[] = 'Format Excel tidak valid. Minimal harus ada header dan 1 data.';
            return;
        }

        $headerRow = $rows[1];

        // 2️⃣ Minimal jumlah kolom
        if (count($headerRow) < 7) {
            $this->errors[] = 'Jumlah kolom tidak sesuai template. Silakan gunakan file export resmi.';
            return;
        }

        // 3️⃣ Validasi header tetap A–F
        $expectedHeaders = [
            'Sub',
            'No',
            'Question',
            '', // D bebas
            'Answer/Option',
            'Score'
        ];

        foreach ($expectedHeaders as $index => $expected) {

            if ($expected === '') continue;

            $actual = trim((string)($headerRow[$index] ?? ''));

            if (strcasecmp($actual, $expected) !== 0) {
                $this->errors[] =
                    "Format kolom salah pada kolom " .
                    chr(65 + $index) .
                    ". Ditemukan '{$actual}', seharusnya '{$expected}'.";
            }
        }

        $lastColIndex = count($headerRow) - 1;

        $this->attachmentCol = $lastColIndex;
        $this->indicatorEndCol = $lastColIndex - 1;

        // 4️⃣ Validasi minimal ada indicator header
        $indicatorCount = 0;

        for ($col = $this->indicatorStartCol; $col <= $this->indicatorEndCol; $col++) {
            $header = trim((string)($headerRow[$col] ?? ''));
            if ($header !== '') {
                $indicatorCount++;
            }
        }

        if ($indicatorCount === 0) {
            $this->errors[] = 'Indicator tidak ditemukan pada header Excel.';
        }

        // ❗ STOP kalau ada error struktur
        if (!empty($this->errors)) {
            return;
        }

        /**
         * ===================================================
         * LOGIC ASLI KAMU (TIDAK DIUBAH)
         * ===================================================
         */

        $categoryMap = Category::pluck('id', 'name')->toArray();

        $currentCategoryId = null;
        $currentIndex = null;

        foreach ($rows as $i => $row) {

            if ($i < 2) continue;

            $excelRow = $i + 1;

            $A = trim((string)($row[0] ?? ''));
            $B = trim((string)($row[1] ?? ''));
            $C = trim((string)($row[2] ?? ''));
            $E = trim((string)($row[4] ?? ''));
            $F = $row[5] ?? null;

            $lastCol = count($row) - 1;
            $attachment = $row[$lastCol] ?? null;

            /**
             * CATEGORY HEADER
             */
            if ($A !== '' && $B === '' && $C === '') {

                if (!isset($categoryMap[$A])) {
                    $this->errors[] = "Baris {$excelRow}: Category '{$A}' tidak ditemukan.";
                    $currentCategoryId = null;
                } else {
                    $currentCategoryId = $categoryMap[$A];
                }

                continue;
            }

            if (!$currentCategoryId) continue;

            /**
             * PARSE INDICATOR (V)
             */
            $indicator = [];

            for ($col = $this->indicatorStartCol; $col <= $this->indicatorEndCol; $col++) {
                if (strtoupper(trim((string)($row[$col] ?? ''))) === 'V') {
                    $indicatorName = strtolower(trim((string)($headerRow[$col] ?? '')));
                    if ($indicatorName !== '') {
                        $indicator[] = $indicatorName;
                    }
                }
            }

            /**
             * QUESTION UTAMA
             */
            if ($B !== '' && $C !== '') {

                $question = [
                    'category_id'   => $currentCategoryId,
                    'sub'           => $A ?: null,
                    'question_no'   => $B,
                    'question_text' => $C,
                    'question_type' => 'isian',
                    'indicator'     => $indicator,
                    'clue'          => $E ?: null,
                    'attachment_text' => ($attachment === '-' ? null : $attachment),
                    'order_index'   => 0,

                    '_first_option' => [
                        'text'  => $E ?: null,
                        'score' => $this->parseScore($F),
                    ],
                    'options' => [],
                ];

                $this->excelQuestions[] = $question;
                $currentIndex = array_key_last($this->excelQuestions);
                continue;
            }

            /**
             * OPTION LANJUTAN
             */
            if (
                $currentIndex !== null &&
                $B === '' &&
                $C === '' &&
                $E !== ''
            ) {

                if ($this->excelQuestions[$currentIndex]['question_type'] === 'isian') {

                    $this->excelQuestions[$currentIndex]['question_type'] = 'pilihan';

                    $first = $this->excelQuestions[$currentIndex]['_first_option'];

                    if (!empty($first['text'])) {
                        $this->excelQuestions[$currentIndex]['options'][] = [
                            'text'  => $first['text'],
                            'score' => $first['score'],
                        ];
                    }

                    $this->excelQuestions[$currentIndex]['clue'] = null;
                }

                $this->excelQuestions[$currentIndex]['options'][] = [
                    'text'  => $E,
                    'score' => $this->parseScore($F),
                ];
            }
        }

        foreach ($this->excelQuestions as &$q) {
            unset($q['_first_option']);
        }

        if (empty($this->excelQuestions)) {
            $this->errors[] = '0 question terbaca dari Excel. Pastikan format sesuai template.';
            return;
        }

        $this->importData = $this->excelQuestions;
    }

    protected function signature(array $q): string
    {
        return md5(json_encode([
            'category_id'   => $q['category_id'],
            'question_no'   => $q['question_no'],
        ]));
    }
}
