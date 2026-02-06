<?php

namespace App\Http\Controllers;

use App\Imports\QuestionnaireImport;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    return view('questionnaire.import-preview', [
        'importData'     => $import->importData,
        'categories'     => Category::all(),
        'totalQuestions' => count($import->importData),
    ]);
}



    /**
     * STEP 2 — CONFIRM IMPORT (MASUK DB)
     */
    public function import(Request $request)
    {
        $questions = $request->input('questions', []);

        foreach ($questions as $q) {

            if (!($q['import'] ?? false)) {
                continue;
            }

            /** DELETE */
            if (($q['action'] ?? '') === 'delete') {
                Question::where('id', $q['id'])->delete();
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
                    'indicator'       => json_encode($q['indicator'] ?? []),
                    'sub'             => $q['sub'] ?? null,
                    'attachment_text' => $q['attachment_text'] ?? null,
                    'clue'            => $q['clue'] ?? null,
                ]);

                $question->options()->delete();

                if ($q['question_type'] === 'pilihan') {
                    foreach ($q['options'] ?? [] as $opt) {
                        $question->options()->create([
                            'text'  => $opt['text'],
                            'score' => $opt['score'],
                        ]);
                    }
                }

                continue;
            }

            /** CREATE NEW */
            $question = Question::create([
                'question_text'   => $q['question_text'],
                'question_type'   => $q['question_type'],
                'category_id'     => $q['category_id'],
                'indicator'       => json_encode($q['indicator'] ?? []),
                'sub'             => $q['sub'] ?? null,
                'attachment_text' => $q['attachment_text'] ?? null,
                'clue'            => $q['clue'] ?? null,
                'no'              => $q['no'] ?? null,
            ]);

            if ($q['question_type'] === 'pilihan') {
                foreach ($q['options'] ?? [] as $opt) {
                    $question->options()->create([
                        'text'  => $opt['text'],
                        'score' => $opt['score'],
                    ]);
                }
            }
        }

        return redirect()
            ->route('questionnaire.index')
            ->with('success', 'Import questionnaire berhasil');
    }
}
