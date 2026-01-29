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

    public function collection(Collection $rows)
    {
        if ($rows->count() < 3) {
            $this->errors[] = 'Format Excel tidak valid.';
            return;
        }

        $currentCategory = null;
        $lastQuestionIndex = null;
        $lastQuestionText = null;

        /**
         * ==============================
         * DETEKSI KOLOM INDIKATOR
         * ==============================
         */
        $indicatorColumnIndexes = [];
        $headerRow = $rows[1]; // baris ke-2

        foreach ($headerRow as $colIndex => $val) {
            $v = strtolower(trim((string) $val));
            if (in_array($v, ['high', 'medium', 'low', 'umum'])) {
                $indicatorColumnIndexes[$colIndex] = $v;
            }
        }

        /**
         * ==============================
         * LOOP DATA
         * ==============================
         */
        foreach ($rows as $rowIndex => $row) {

            // skip header
            if ($rowIndex < 2) continue;

            $excelRow = $rowIndex + 1;
            $lastColumnIndex = count($row) - 1;

            $sub        = trim((string) ($row[0] ?? '')); // A
            $no         = trim((string) ($row[1] ?? '')); // B
            $deskripsi  = trim((string) ($row[2] ?? '')); // C
            $pilihan    = trim((string) ($row[4] ?? '')); // E
            $scoreRaw   = trim((string) ($row[5] ?? '')); // F
            $attachment = trim((string) ($row[$lastColumnIndex] ?? ''));

            $attachment = ($attachment === '' || $attachment === '-') ? null : $attachment;
            $score = ($scoreRaw === '' || !is_numeric($scoreRaw)) ? null : (int) $scoreRaw;

            // skip baris kosong
            if ($sub === '' && $no === '' && $deskripsi === '' && $pilihan === '') {
                continue;
            }

            /**
             * ==============================
             * CATEGORY HEADER
             * ==============================
             */
            if ($sub !== '' && $no === '' && $deskripsi === '') {
                $category = Category::where('name', $sub)->first();

                if (!$category) {
                    $this->errors[] = "Baris {$excelRow}: Category '{$sub}' tidak ditemukan.";
                    $currentCategory = null;
                    continue;
                }

                $currentCategory = $category;
                continue;
            }

            if (!$currentCategory) {
                $this->errors[] = "Baris {$excelRow}: Pertanyaan tanpa category.";
                continue;
            }

            /**
             * ==============================
             * PERTANYAAN BARU
             * ==============================
             */
            if ($no !== '' && is_numeric($no)) {

                if ($deskripsi === '') {
                    $this->errors[] = "Baris {$excelRow}: Deskripsi pertanyaan kosong.";
                    continue;
                }

                $lastQuestionText = $deskripsi;

                /**
                 * ==============================
                 * INDIKATOR
                 * ==============================
                 */
                $indicator = [];

                foreach ($indicatorColumnIndexes as $colIndex => $level) {
                    if (
                        isset($row[$colIndex]) &&
                        strtoupper(trim((string) $row[$colIndex])) === 'V'
                    ) {
                        $indicator[] = $level;
                    }
                }

                /**
                 * ==============================
                 * TIPE PERTANYAAN
                 * ==============================
                 */
                $isPilihan = $pilihan !== '';

                /**
                 * ==============================
                 * CEK DUPLIKAT
                 * ==============================
                 */
                $exists = Question::whereRaw(
                    'LOWER(TRIM(question_text)) = ?',
                    [strtolower(trim($lastQuestionText))]
                )->exists();

                if ($exists) {
                    $lastQuestionIndex = null;
                    continue;
                }

                /**
                 * ==============================
                 * SIMPAN DATA
                 * ==============================
                 */
                $this->importData[] = [
                    'row_number'      => $excelRow,
                    'category_id'     => $currentCategory->id,
                    'category_name'   => $currentCategory->name,
                    'sub'             => $sub ?: null,
                    'no'              => (int) $no,
                    'question_text'   => $lastQuestionText,
                    'clue'            => $isPilihan ? null : ($pilihan ?: null),
                    'indicator'       => $indicator,
                    'attachment_text' => $attachment,
                    'question_type'   => $isPilihan ? 'pilihan' : 'isian',
                    'options'         => $isPilihan
                        ? [[
                            'text'  => $pilihan,
                            'score' => $score,
                        ]]
                        : [],
                    'is_new' => true,
                ];

                $lastQuestionIndex = array_key_last($this->importData);
                continue;
            }

            /**
             * ==============================
             * OPTION LANJUTAN
             * ==============================
             */
            if (
                $lastQuestionIndex !== null &&
                $deskripsi === '' &&
                $pilihan !== '' &&
                $this->importData[$lastQuestionIndex]['question_type'] === 'pilihan'
            ) {
                $this->importData[$lastQuestionIndex]['options'][] = [
                    'text'  => $pilihan,
                    'score' => $score,
                ];
            }
        }

        if (empty($this->importData)) {
            $this->errors[] = 'Tidak ada data yang bisa diimport.';
        }
    }
}
