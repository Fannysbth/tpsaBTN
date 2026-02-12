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
     * ==========================================
     * STEP 1 — PREVIEW
     * Identity = category_id + question_no
     * ==========================================
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $import = new QuestionnaireImport();
        Excel::import($import, $request->file('file'));

        $categories = Category::all();
        $importData = $import->importData;

        /**
         * ------------------------------------------
         * NORMALIZE IMPORT DATA
         * ------------------------------------------
         */
        foreach ($importData as &$item) {

            $item['question_no']     = trim((string)($item['question_no'] ?? ''));
            $item['question_text']   = trim((string)($item['question_text'] ?? ''));
            $item['question_type']   = $item['question_type'] ?? 'isian';
            $item['category_id']     = $item['category_id'] ?? null;
            $item['sub']             = trim((string)($item['sub'] ?? ''));
            $item['indicator']       = $item['indicator'] ?? [];
            $item['attachment_text'] = $item['attachment_text'] ?? $item['attachment'] ?? null;
            $item['clue']            = $item['clue'] ?? null;
            $item['order_index']     = $item['order_index'] ?? 0;
        }

        /**
         * ------------------------------------------
         * REMOVE DUPLICATE EXCEL ROW (IMPORTANT)
         * ------------------------------------------
         */
        $importData = collect($importData)
            ->unique(function ($q) {
                return $this->identityKey($q);
            })
            ->values()
            ->toArray();

        /**
         * ------------------------------------------
         * LOAD DB QUESTIONS
         * ------------------------------------------
         */
        $dbQuestions = Question::all();

        $dbMap = [];
        foreach ($dbQuestions as $q) {
            $identity = $this->identityKey([
                'category_id' => $q->category_id,
                'question_no' => $q->question_no,
            ]);
            $dbMap[$identity] = $q;
        }

        $excelIdentities = [];

        /**
         * ------------------------------------------
         * DETERMINE CREATE / UPDATE / UNCHANGED
         * ------------------------------------------
         */
        foreach ($importData as &$item) {

            $identity = $this->identityKey($item);
            $excelIdentities[] = $identity;

            if (isset($dbMap[$identity])) {

                $existing = $dbMap[$identity];
                $differences = $this->countDifferences($existing, $item);

                $item['id'] = $existing->id;

                if ($differences['total_differences'] > 0) {
                    $item['action'] = 'update';
                } else {
                    $item['action'] = 'unchanged';
                }

                $item['differences'] = $differences['details'];
                $item['total_differences'] = $differences['total_differences'];

            } else {

                $item['action'] = 'create';
                $item['id'] = null;
                $item['differences'] = [];
                $item['total_differences'] = 0;
            }
        }

        /**
         * ------------------------------------------
         * DETECT DELETE
         * ------------------------------------------
         */
        foreach ($dbMap as $identity => $q) {

            if (!in_array($identity, $excelIdentities, true)) {

                $importData[] = [
                    'id' => $q->id,
                    'question_no' => $q->question_no,
                    'question_text' => $q->question_text,
                    'category_id' => $q->category_id,
                    'sub' => $q->sub,
                    'action' => 'delete',
                    'differences' => [],
                    'total_differences' => 0,
                ];
            }
        }

        return view('questionnaire.import-preview', [
            'importData'     => $importData,
            'categories'     => $categories,
            'totalQuestions' => count($importData),
        ]);
    }

    /**
     * ==========================================
     * IDENTITY KEY
     * category_id + question_no
     * ==========================================
     */
    private function identityKey(array $data): string
    {
        return ($data['category_id'] ?? '') . '|' .
               strtolower(trim((string)($data['question_no'] ?? '')));
    }

    /**
     * ==========================================
     * COLUMN COMPARISON
     * ==========================================
     */
    private function countDifferences(Question $existing, array $importItem): array
    {
        $columnsToCompare = [
            'question_text',
            'question_type',
            'category_id',
            'sub',
            'indicator',
            'attachment_text',
            'clue',
            'question_no',
            'order_index'
        ];

        $differences = [
            'total_columns' => count($columnsToCompare),
            'total_differences' => 0,
            'details' => []
        ];

        foreach ($columnsToCompare as $column) {

            $existingValue = $existing->$column ?? '';
            $importValue   = $importItem[$column] ?? '';

            if ($column === 'indicator') {
                $existingValue = json_encode($existingValue ?? []);
                $importValue   = json_encode($importValue ?? []);
            }

            $existingValue = trim((string)$existingValue);
            $importValue   = trim((string)$importValue);

            if ($existingValue !== $importValue) {

                $differences['total_differences']++;

                $differences['details'][$column] = [
                    'existing' => $existingValue,
                    'import'   => $importValue
                ];
            }
        }

        return $differences;
    }

    /**
     * ==========================================
     * STEP 2 — APPLY SYNC
     * ==========================================
     */
    public function import(Request $request)
    {
        DB::beginTransaction();

        try {

            foreach ($request->questions as $q) {

                if (!($q['import'] ?? false)) continue;

                if ($q['action'] === 'delete') {
                    Question::find($q['id'])?->delete();
                    continue;
                }

                if ($q['action'] === 'update') {
                    Question::find($q['id'])?->update([
                        'question_text'   => $q['question_text'],
                        'question_type'   => $q['question_type'],
                        'category_id'     => $q['category_id'],
                        'indicator'       => $q['indicator'] ?? [],
                        'sub'             => $q['sub'],
                        'attachment_text' => $q['attachment_text'],
                        'has_attachment'  => !empty($q['attachment_text']),
                        'clue' => $q['clue'] ?? null,
                        'question_no'     => $q['question_no'],
                        'order_index'     => $q['order_index'] ?? 0,
                    ]);
                    continue;
                }

                if ($q['action'] === 'create') {
                    Question::create([
                        'question_text'   => $q['question_text'],
                        'question_type'   => $q['question_type'],
                        'category_id'     => $q['category_id'],
                        'indicator'       => $q['indicator'] ?? [],
                        'sub'             => $q['sub'],
                        'attachment_text' => $q['attachment_text'],
                        'has_attachment'  => !empty($q['attachment_text']),
                        'clue' => $q['clue'] ?? null,
                        'question_no'     => $q['question_no'],
                        'order_index'     => $q['order_index'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('questionnaire.index')
                ->with('success', 'Sinkronisasi Excel berhasil');

        } catch (\Throwable $e) {

            DB::rollBack();
            throw $e;
        }
    }
}
