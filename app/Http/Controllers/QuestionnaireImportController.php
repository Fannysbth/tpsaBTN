<?php

namespace App\Http\Controllers;

use App\Imports\QuestionnaireImport;
use App\Models\Category;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class QuestionnaireImportController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $import = new QuestionnaireImport();
        Excel::import($import, $request->file('file'));

        $totalQuestions = count($import->importData);
        $categories = Category::all();

       

        return view('questionnaire.import-preview', [
            'importData' => $import->importData,
            'totalQuestions' => $totalQuestions,
            'importErrors' => $import->errors,
            'categories' => $categories
        ]);
    }

    public function import(Request $request)
{
    $questions = $request->input('questions', []);

    foreach ($questions as $q) {

        if (!isset($q['import'])) continue;

        $questionText = trim($q['question_text'] ?? '');
        if ($questionText === '') continue;

        // Cek duplikat HANYA untuk import file
        if (empty($q['is_new'])) {
            $exists = \App\Models\Question::whereRaw(
                'LOWER(TRIM(question_text)) = ?',
                [strtolower($questionText)]
            )->where('category_id', $q['category_id'])->exists();

            if ($exists) continue;
        }

        $question = \App\Models\Question::create([
            'category_id' => $q['category_id'],
            'sub' => $q['sub'] ?? null,
            'question_text' => $questionText,
            'question_type' => $q['question_type'],
            'clue' => $q['clue'] ?? null,
            'indicator' => json_encode($q['indicator'] ?? []),
        ]);

        if ($q['question_type'] === 'pilihan') {
            foreach ($q['options'] ?? [] as $option) {
                if (trim($option['text'] ?? '') === '') continue;

                \App\Models\QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['text'],
                    'score' => $option['score'] ?? 0,
                ]);
            }
        }
    }

    return redirect()->route('questionnaire.index')
        ->with('success', 'Data berhasil diimport!');
}

}