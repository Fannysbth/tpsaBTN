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
            $this->errors[] = 'Format Excel tidak valid: baris data tidak ditemukan.';
            return;
        }

        $currentCategory = null;
        $excelRow = 0;
        $questionCount = 0;

        $lastQuestionText = null;
        $lastQuestionIndex = null;

        /**
         * ==============================
         * DETEKSI KOLOM INDIKATOR (DINAMIS)
         * ==============================
         * Baris header ke-2 berisi:
         * High | Medium | Low | High | Medium | Low | ...
         */
        $indicatorColumnIndexes = [];
        $headerIndicatorRow = $rows[1]; // baris ke-2

        foreach ($headerIndicatorRow as $colIndex => $val) {
            $v = strtolower(trim((string) $val));
            if (in_array($v, ['high', 'medium', 'low'])) {
                $indicatorColumnIndexes[$colIndex] = $v;
            }
        }

        if (empty($indicatorColumnIndexes)) {
            $this->errors[] = 'Kolom indikator (High/Medium/Low) tidak terdeteksi.';
        }

        /**
         * ==============================
         * LOOP DATA
         * ==============================
         */
        foreach ($rows as $row) {
            $excelRow++;

            // skip 2 header
            if ($excelRow <= 2) {
                continue;
            }

            // mapping kolom tetap
            $sub        = trim((string) ($row[0] ?? '')); // A
            $no         = trim((string) ($row[1] ?? '')); // B
            $deskripsi  = trim((string) ($row[2] ?? '')); // C
            $pilihan    = trim((string) ($row[4] ?? '')); // E
            $score      = trim((string) ($row[5] ?? '')); // F

            // skip baris kosong total
            if ($sub === '' && $no === '' && $deskripsi === '' && $pilihan === '') {
                continue;
            }

            /**
             * ==============================
             * DETEKSI CATEGORY HEADER
             * ==============================
             */
            if ($sub !== '' && $no === '' && $deskripsi === '') {
                $category = Category::where('name', $sub)->first();

                if (!$category) {
                    $this->errors[] = "Baris {$excelRow}: Kategori '{$sub}' tidak ditemukan di database.";
                    $currentCategory = null;
                    continue;
                }

                $currentCategory = $category;
                continue;
            }

            if (!$currentCategory) {
                $this->errors[] = "Baris {$excelRow}: Pertanyaan ditemukan tanpa kategori.";
                continue;
            }

            /**
             * ==============================
             * DETEKSI PERTANYAAN BARU
             * ==============================
             * Patokan UTAMA: kolom No angka
             */
            if ($no !== '' && is_numeric(trim($no))) {

                // simpan deskripsi pertama (karena merge)
                if ($deskripsi !== '') {
                    $lastQuestionText = $deskripsi;
                }

                if (!$lastQuestionText) {
                    $this->errors[] = "Baris {$excelRow}: Deskripsi pertanyaan tidak ditemukan.";
                    continue;
                }

                /**
                 * ==============================
                 * DETEKSI INDIKATOR
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
                 * DETEKSI TIPE
                 * ==============================
                 */
                $isPilihan = false;
                $options = [];

                if ($pilihan !== '' && $score !== '' && $score !== '-') {
                    if (!is_numeric(str_replace(',', '.', $score))) {
                        $this->errors[] = "Baris {$excelRow}: Score harus angka.";
                        continue;
                    }

                    $isPilihan = true;
                    $options[] = [
                        'text' => $pilihan,
                        'score' => (float) str_replace(',', '.', $score),
                    ];
                }

                /**
                 * ==============================
                 * CEK DUPLICATE (AMAN)
                 * ==============================
                 */
                $exists = Question::whereRaw(
    'LOWER(TRIM(question_text)) = ?',
    [strtolower(trim($lastQuestionText))]
)->exists();


                if ($exists) {
                    // sudah ada → jangan masuk preview
                    continue;
                }

                /**
                 * ==============================
                 * SIMPAN KE PREVIEW
                 * ==============================
                 */
                $this->importData[] = [
                    'row_number'   => $excelRow,
                    'category_id'  => $currentCategory->id,
                    'category_name'=> $currentCategory->name,
                    'sub'          => $sub ?: null,
                    'no'           => (int) $no,
                    'question_text'=> $lastQuestionText,
                    'clue'         => !$isPilihan ? ($pilihan ?: null) : null,
                    'indicator'    => $indicator,
                    'question_type'=> $isPilihan ? 'pilihan' : 'isian',
                    'options'      => $options,
                    'is_new'       => true,
                ];

                $lastQuestionIndex = array_key_last($this->importData);
                $questionCount++;
                continue;
            }

            /**
             * ==============================
             * OPSI LANJUTAN (PILIHAN)
             * ==============================
             */
            if (
                $lastQuestionIndex !== null &&
                $pilihan !== '' &&
                $score !== '' &&
                $score !== '-'
            ) {
                if (!is_numeric(str_replace(',', '.', $score))) {
                    $this->errors[] = "Baris {$excelRow}: Score opsi harus angka.";
                    continue;
                }

                if ($this->importData[$lastQuestionIndex]['question_type'] === 'pilihan') {
                    $this->importData[$lastQuestionIndex]['options'][] = [
                        'text' => $pilihan,
                        'score' => (float) str_replace(',', '.', $score),
                    ];
                }
            }
        }

        /**
         * ==============================
         * VALIDASI AKHIR
         * ==============================
         */
        if ($questionCount === 0) {
            $this->errors[] = 'Tidak ada pertanyaan baru yang dapat diimport.';
        }
    }
}
