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
            if (!isset($q['import']) || $q['import'] !== 'on') continue;
            
            // Cek apakah pertanyaan sudah ada
            $existingQuestion = \App\Models\Question::where('question_text', $q['question_text'])
                ->where('category_id', $q['category_id'])
                ->first();
            
            if ($existingQuestion) {
                continue; // Skip jika sudah ada
            }
            
            // Buat pertanyaan baru
            $question = \App\Models\Question::create([
                'category_id' => $q['category_id'],
                'sub' => $q['sub'] ?? null,
                'question_text' => $q['question_text'],
                'question_type' => $q['question_type'],
                'clue' => $q['clue'] ?? null,
                'indicator' => isset($q['indicator']) ? json_encode($q['indicator']) : json_encode([]),
            ]);
            
            // Jika tipe pilihan, buat opsi
            if ($q['question_type'] === 'pilihan' && isset($q['options'])) {
                foreach ($q['options'] as $option) {
                    if (!empty($option['text'])) {
                        \App\Models\QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $option['text'],
                            'score' => $option['score'] ?? 0,
                        ]);
                    }
                }
            }
        }
        
        return redirect()->route('questionnaire.index')
            ->with('success', 'Data berhasil diimport!');
    }
}