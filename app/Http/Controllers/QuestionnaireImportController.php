<?php

namespace App\Http\Controllers;

use App\Imports\QuestionnaireImport;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class QuestionnaireImportController extends Controller
{
    /**
     * STEP 1 — PREVIEW (BELUM INSERT DB)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        try {
            $import = new QuestionnaireImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            dd('IMPORT ERROR', $e->getMessage(), $e->getTraceAsString());
        }

        // Get all categories first
        $categories = Category::all();

        // Tambahkan default values untuk setiap item
        $importData = $import->importData;
        foreach ($importData as &$item) {
            $item['question_no'] = $item['question_no'] ?? $item['no'] ?? null;
            $item['question_type'] = $item['question_type'] ?? 'isian';
            $item['category_id'] = $item['category_id'] ?? ($categories->first()->id ?? null);
            $item['category_name'] = $item['category_name'] ?? '';
            $item['sub'] = $item['sub'] ?? '';
            $item['indicator'] = $item['indicator'] ?? [];
            $item['attachment_text'] = $item['attachment_text'] ?? $item['attachment'] ?? '-';
        }

        // Hanya ambil data yang memiliki question_no (tidak null)
        $existingQuestionNos = [];
        foreach ($importData as $item) {
            if (!empty($item['question_no'])) {
                $existingQuestionNos[] = $item['question_no'];
            }
        }

        // Cari existing questions berdasarkan question_no yang ada di import
        $existingQuestions = [];
        if (!empty($existingQuestionNos)) {
            $existingQuestions = Question::whereIn('question_no', $existingQuestionNos)
                ->get()
                ->keyBy('question_no');
        }

        // Tentukan action untuk setiap item
        foreach ($importData as &$item) {
            $questionNo = $item['question_no'];
            
            // Jika question_no kosong/null, pasti create baru
            if (empty($questionNo)) {
                $item['action'] = 'create';
                $item['id'] = null;
                $item['differences'] = [];
                $item['total_differences'] = 0;
                continue;
            }
            
            // Jika ditemukan di database
            if (isset($existingQuestions[$questionNo])) {
                $existing = $existingQuestions[$questionNo];
                
                // Hitung perbedaan kolom
                $differences = $this->countDifferences($existing, $item);
                
                // LOGIKA FIXED:
                // 1. Jika SEMUA kolom berbeda → delete
                // 2. Jika ADA perbedaan (tidak harus semua) → update
                // 3. Jika TIDAK ADA perbedaan → unchanged
                
                if ($differences['total_differences'] === $differences['total_columns']) {
                    $item['action'] = 'delete';
                } elseif ($differences['total_differences'] > 0) {
                    $item['action'] = 'update';
                } else {
                    $item['action'] = 'unchanged';
                }
                
                $item['id'] = $existing->id;
                $item['differences'] = $differences['details'];
                $item['total_differences'] = $differences['total_differences'];
            } else {
                // Jika TIDAK ditemukan di database → CREATE BARU
                $item['action'] = 'create';
                $item['id'] = null;
                $item['differences'] = [];
                $item['total_differences'] = 0;
            }
        }

        return view('questionnaire.import-preview', [
            'importData'     => $importData,
            'categories'     => $categories,
            'totalQuestions' => count($importData),
        ]);
    }

    /**
     * Hitung perbedaan antara data existing dan data import
     * HANYA untuk data yang sudah ada di database
     */
    private function countDifferences(Question $existing, array $importItem): array
    {
        $differences = [
            'total_columns' => 0,
            'total_differences' => 0,
            'details' => []
        ];

        // Daftar kolom yang akan dibandingkan
        $columnsToCompare = [
            'question_text' => [
                'existing' => $existing->question_text,
                'import' => $importItem['question_text'] ?? ''
            ],
            'question_type' => [
                'existing' => $existing->question_type,
                'import' => $importItem['question_type'] ?? 'isian'
            ],
            'category_id' => [
                'existing' => $existing->category_id,
                'import' => $importItem['category_id'] ?? null
            ],
            'sub' => [
                'existing' => $existing->sub,
                'import' => $importItem['sub'] ?? ''
            ],
            'indicator' => [
                'existing' => $existing->indicator ?? [],
                'import' => $importItem['indicator'] ?? []
            ],
            'attachment_text' => [
                'existing' => $existing->attachment_text,
                'import' => $importItem['attachment_text'] ?? null
            ],
            'clue' => [
                'existing' => $existing->clue,
                'import' => $importItem['clue'] ?? null
            ],
            'question_no' => [
                'existing' => $existing->question_no,
                'import' => $importItem['question_no'] ?? null
            ]
        ];

        $differences['total_columns'] = count($columnsToCompare);

        foreach ($columnsToCompare as $column => $values) {
            $existingValue = $values['existing'];
            $importValue = $values['import'];
            
            // Normalisasi tipe data untuk perbandingan
            if ($column === 'indicator') {
    if (is_array($existingValue)) {
        sort($existingValue); // sort array by values
        $existingValue = json_encode($existingValue);
    }
    if (is_array($importValue)) {
        sort($importValue);
        $importValue = json_encode($importValue);
    }
} else {
    $existingValue = (string) $existingValue;
    $importValue = (string) $importValue;
}

            
            // Trim untuk menghindari spasi yang tidak perlu
            $existingValue = trim($existingValue);
            $importValue = trim($importValue);
            
            if ($existingValue !== $importValue) {
                $differences['total_differences']++;
                $differences['details'][$column] = [
                    'existing' => $existingValue,
                    'import' => $importValue
                ];
            }
        }

        return $differences;
    }

    /**
     * STEP 2 — CONFIRM IMPORT (MASUK DB)
     */
    public function import(Request $request)
    {
        DB::beginTransaction();
        $questions = $request->input('questions', []);

        try {
            foreach ($questions as $q) {
                if (!($q['import'] ?? false)) continue;

                /** DELETE */
                if (($q['action'] ?? '') === 'delete') {
                    $question = Question::find($q['id']);
                    if ($question) {
                        $question->options()->delete();
                        $question->delete();
                    }
                    continue;
                }

                /** UPDATE */
                if (($q['action'] ?? '') === 'update') {
                    $question = Question::find($q['id']);
                    if (!$question) continue;

                    $question->update([
                        'question_text'   => $q['question_text'],
                        'question_type'   => $q['question_type'],
                        'category_id'     => $q['category_id'],
                        'indicator'       => $q['indicator'] ?? [],
                        'sub'             => $q['sub'] ?? null,
                        'attachment_text' => $q['attachment_text'] ?? null,
                        'has_attachment'  => !empty($q['attachment_text']),
                        'clue'            => $q['clue'] ?? null,
                        'question_no'     => trim((string)($q['question_no'] ?? '')),
                    ]);

                    $question->options()->delete();

                    if ($q['question_type'] === 'pilihan') {
                        foreach ($q['options'] ?? [] as $opt) {
                            if (empty(trim($opt['text'] ?? ''))) continue;
                            $question->options()->create([
                                'option_text' => $opt['text'],
                                'score'       => floatval($opt['score'] ?? 0),
                            ]);
                        }
                    }
                    continue;
                }

                /** CREATE NEW */
                if (($q['action'] ?? '') === 'create') {
                    $question = Question::create([
                        'question_text'   => $q['question_text'],
                        'question_type'   => $q['question_type'],
                        'category_id'     => $q['category_id'],
                        'indicator'       => $q['indicator'] ?? [],
                        'sub'             => $q['sub'] ?? null,
                        'attachment_text' => $q['attachment_text'] ?? null,
                        'has_attachment'  => !empty($q['attachment_text']),
                        'clue'            => $q['clue'] ?? null,
                        'question_no'     => trim((string)($q['question_no'] ?? '')),
                        'order_index'     => $q['order_index'] ?? 0,
                    ]);

                    if ($q['question_type'] === 'pilihan') {
                        foreach ($q['options'] ?? [] as $opt) {
                            if (empty(trim($opt['text'] ?? ''))) continue;
                            $question->options()->create([
                                'option_text' => $opt['text'],
                                'score'       => floatval($opt['score'] ?? 0),
                            ]);
                        }
                    }
                    continue;
                }

                /** UNCHANGED - Skip jika tidak ada perubahan */
                // Tidak perlu melakukan apa-apa
            }

            DB::commit();

            return redirect()
                ->route('questionnaire.index')
                ->with('success', 'Import questionnaire berhasil');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}