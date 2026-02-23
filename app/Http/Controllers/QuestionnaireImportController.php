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
    private function normalizeValue($value)
{
    if (is_null($value)) {
        return '';
    }

    $value = trim((string) $value);

    if ($value === '' || $value === '-') {
        return '';
    }

    return $value;
}


    /**
     * ==========================================
     * STEP 1 — PREVIEW
     * Identity = category_id + question_no
     * ==========================================
     */
    
public function preview(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $import = new QuestionnaireImport();
    Excel::import($import, $request->file('file'));

    /**
     * ==========================================
     * 🚨 TAMBAHAN: STOP JIKA ADA ERROR IMPORT
     * ==========================================
     */
    if (!empty($import->errors)) {
        return redirect()
            ->back()
            ->with('error', implode('<br>', $import->errors));
    }

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
        $item['attachment_text'] = $item['attachment_text'] ?? $item['attachment'] ?? "-";
        $item['clue']            = $item['clue'] ?? null;
        $item['order_index']     = $item['order_index'] ?? 0;
    }

    /**
     * ------------------------------------------
     * REMOVE DUPLICATE EXCEL ROW
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

    $importData = array_filter($importData, function ($item) {
        return $item['action'] !== 'unchanged';
    });

    $importData = array_values($importData);

    if (empty($importData)) {
        return view('questionnaire.import-preview', [
            'importData'     => [],
            'categories'     => $categories,
            'totalQuestions' => 0,
            'info'           => 'Tidak ada perubahan terdeteksi.'
        ]);
    }

    usort($importData, function ($a, $b) {

        if ((int)$a['category_id'] !== (int)$b['category_id']) {
            return (int)$a['category_id'] <=> (int)$b['category_id'];
        }

        $parse = function ($value) {

            $value = strtolower(trim($value));

            preg_match('/^(\d+)([a-z]*)$/', $value, $matches);

            return [
                'number' => isset($matches[1]) ? (int)$matches[1] : 0,
                'suffix' => $matches[2] ?? ''
            ];
        };

        $aParsed = $parse($a['question_no'] ?? '');
        $bParsed = $parse($b['question_no'] ?? '');

        if ($aParsed['number'] !== $bParsed['number']) {
            return $aParsed['number'] <=> $bParsed['number'];
        }

        return strcmp($aParsed['suffix'], $bParsed['suffix']);
    });

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
            'order_index',
            'options' 
        ];

        $differences = [
            'total_columns' => count($columnsToCompare),
            'total_differences' => 0,
            'details' => []
        ];

        foreach ($columnsToCompare as $column) {

    $existingValue = $existing->$column ?? '';
    $importValue   = $importItem[$column] ?? '';

    // Khusus indicator (array)
    if ($column === 'indicator') {

        $existingValue = $existingValue ?? [];
$importValue   = $importValue ?? [];

$existingValue = is_string($existingValue)
    ? json_decode($existingValue, true) ?? [$existingValue]
    : $existingValue;

$importValue = is_string($importValue)
    ? json_decode($importValue, true) ?? [$importValue]
    : $importValue;

sort($existingValue);
sort($importValue);


        if ($existingValue !== $importValue) {
            $differences['total_differences']++;
            $differences['details'][$column] = [
                'existing' => $existingValue,
                'import'   => $importValue
            ];
        }

        continue;
    }

    if ($column === 'options') {

    $existingOptions = $existing->options()
        ->orderBy('id')
        ->get()
        ->map(fn($o) => [
            'text' => trim($o->option_text),
            'score' => (float) $o->score
        ])
        ->toArray();

    $importOptions = collect($importItem['options'] ?? [])
        ->map(fn($o) => [
            'text' => trim($o['text']),
            'score' => (float) ($o['score'] ?? 0)
        ])
        ->values()
        ->toArray();

    if ($existingOptions !== $importOptions) {
        $differences['total_differences']++;
        $differences['details'][$column] = [
            'existing' => $existingOptions,
            'import'   => $importOptions
        ];
    }

    continue;
}


    // 🔥 Normalisasi "-" dan ""
    $existingValue = $this->normalizeValue($existingValue);
    $importValue   = $this->normalizeValue($importValue);

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
    private function generateNextQuestionNo($categoryId)
{
    $lastQuestion = \App\Models\Question::where('category_id', $categoryId)
        ->orderByRaw("
            CAST(REGEXP_REPLACE(question_no, '[^0-9]', '', 'g') AS INTEGER) DESC
        ")
        ->first();

    if (!$lastQuestion) {
        return 1;
    }

    $lastNo = $lastQuestion->question_no;

    preg_match('/\d+/', $lastNo, $matches);
    $number = isset($matches[0]) ? (int)$matches[0] : 0;

    return $number + 1;
}




    /**
     * ==========================================
     * STEP 2 — APPLY SYNC
     * ==========================================
     */
    public function import(Request $request)
    {

    //dd($request->all());
        DB::beginTransaction();

try {

    foreach ($request->questions as $q) {

        

        $question = null;

        // DELETE
        if ($q['action'] === 'delete') {
            $question = Question::find($q['id']);
            $question?->options()->delete(); // hapus opsi dulu
            $question?->delete();
            continue;
        }

        // CREATE / UPDATE
        if ($q['action'] === 'update') {
            $question = Question::find($q['id']);
            $question->update([
                'question_text'   => $q['question_text'],
                'question_type'   => $q['question_type'],
                'category_id'     => $q['category_id'],
                'sub'             => $q['sub'],
                'indicator'       => $q['indicator'] ?? [],
                'attachment_text' => $q['attachment_text'] ?? null,
                'has_attachment'  => !empty($q['attachment_text'] ?? null),
                'clue'            => $q['clue'] ?? null,
                'question_no'     => $q['question_no'],
                'order_index'     => $q['order_index'] ?? 0,
            ]);
        }

        if ($q['action'] === 'create') {
            $question = Question::create([
                'question_text'   => $q['question_text'],
                'question_type'   => $q['question_type'],
                'category_id'     => $q['category_id'],
                'sub'             => $q['sub'],
                'indicator'       => $q['indicator'] ?? [],
                'attachment_text' => $q['attachment_text'] ?? null,
                'has_attachment'  => !empty($q['attachment_text'] ?? null),
                'clue'            => $q['clue'] ?? null,
                'question_no'     => $q['question_no'] ?? $this->generateNextQuestionNo($q['category_id']),
                'order_index'     => $q['order_index'] ?? 0,
            ]);
        }

        if ($q['action'] === 'unchanged') {
        continue;
    }

        // SIMPAN OPTIONS jika tipe 'pilihan'
        if ($question->question_type === 'pilihan' && !empty($q['options'])) {
            // hapus opsi lama jika update
            if ($q['action'] === 'update') {
                $question->options()->delete();
            }

            foreach ($q['options'] as $opt) {
                if (!empty($opt['text'])) {
                    $question->options()->create([
                        'option_text' => $opt['text'],
                        'score'       => $opt['score'] ?? 0,
                    ]);
                }
            }
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
