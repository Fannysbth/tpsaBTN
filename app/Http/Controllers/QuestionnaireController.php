<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuestionnaireExport;
use App\Imports\QuestionnaireImport;

class QuestionnaireController extends Controller
{
    /* =======================
     * EXPORT
     * ======================= */
    public function export()
    {
        return Excel::download(new QuestionnaireExport, 'questionnaire.xlsx');
    }

    /* =======================
     * INDEX
     * ======================= */
    public function index()
{
    $categories = Category::with('questions')->get();

    $categories->each(function ($category) {

        $category->questions = $category->questions
            ->sortBy(function ($q) {

                preg_match('/^(\d+)([a-zA-Z]*)$/', $q->question_no, $matches);

                $number = isset($matches[1]) ? (int) $matches[1] : 0;
                $suffix = $matches[2] ?? '';

                return [$number, $suffix];
            })
            ->values();
    });

    return view('questionnaire.index', [
        'categories'     => $categories,
        'mainCategories' => $categories->take(3),
        'moreCategories' => $categories->skip(3),
    ]);
}


    /* =======================
     * EDIT ALL
     * ======================= */
    public function editAll()
{
    $categories = Category::with('questions.options')->get();

    $categories->each(function ($category) {

        $category->questions = $category->questions
            ->sortBy(function ($q) {

                preg_match('/^(\d+)([a-zA-Z]*)$/', $q->question_no, $matches);

                $number = isset($matches[1]) ? (int) $matches[1] : 0;
                $suffix = $matches[2] ?? '';

                return [$number, $suffix];
            })
            ->values();
    });

    return view('questionnaire.editAll', compact('categories'));
}


    /* =======================
     * UPDATE ALL (CREATE + UPDATE + DELETE)
     * ======================= */
    public function updateAll(Request $request)
{
    

    DB::beginTransaction();

    try {

        /* =======================
           DELETE QUESTIONS
        ======================= */
        $deletedIds = collect(
            explode(',', $request->deleted_questions ?? '')
        )->filter()->map(fn ($id) => (int) $id);

        if ($deletedIds->isNotEmpty()) {
            QuestionOption::whereIn('question_id', $deletedIds)->delete();
            Question::whereIn('id', $deletedIds)->delete();
        }

        /* =======================
           LOOP QUESTIONS
        ======================= */
        foreach ($request->questions ?? [] as $key => $data) {

            if (trim($data['question_text'] ?? '') === '') {
                continue;
            }

            /* =======================
               UPDATE EXISTING
            ======================= */
            if (is_numeric($key)) {
    $question = Question::find($key);
    if (!$question) continue;

    $oldCategoryId = $question->category_id;
    $newCategoryId = $data['category_id'];

    $updateData = [
        'category_id'     => $newCategoryId,
        'question_text'   => trim($data['question_text']),
        'question_type'   => $data['question_type'] ?? 'pilihan',
        'indicator'       => $data['indicator'] ?? null,
        'clue'            => $data['clue'] ?? null,
        'attachment_text' => $data['attachment_text'] ?? null,
        'has_attachment'  => !empty($data['attachment_text']),
        'sub'             => $data['sub'] ?? null,
    ];

    // Jika pindah kategori, assign question_no baru
    if ($oldCategoryId != $newCategoryId) {
        $lastNumber = Question::where('category_id', $newCategoryId)
            ->whereNotNull('question_no')
            ->pluck('question_no')
            ->map(fn ($no) => (int) $no)
            ->max();
        $newQuestionNo = $lastNumber ? $lastNumber + 1 : 1;
        $updateData['question_no'] = $newQuestionNo;
    }

    $question->update($updateData);
}else {

                /* =======================
                   CREATE NEW QUESTION
                ======================= */

                $lastNumber = Question::where('category_id', $data['category_id'])
                    ->whereNotNull('question_no')
                    ->pluck('question_no')
                    ->map(fn ($no) => (int) $no)
                    ->max();

                $nextNumber = $lastNumber ? $lastNumber + 1 : 1;

                $question = Question::create([
                    'category_id'     => $data['category_id'],
                    'question_no'     => $nextNumber,
                    'question_text'   => trim($data['question_text']),
                    'question_type'   => $data['question_type'] ?? 'pilihan',
                    'indicator'       => $data['indicator'] ?? null,
                    'clue'            => $data['clue'] ?? null,
                    'attachment_text' => $data['attachment_text'] ?? null,
                    'has_attachment'  => !empty($data['attachment_text']),
                    'sub'             => $data['sub'] ?? null,
                ]);
            }

            /* =======================
               HANDLE OPTIONS
            ======================= */
            if (($data['question_type'] ?? '') === 'pilihan') {

                // Hapus lama dulu
                $question->options()->delete();

                foreach ($data['options'] ?? [] as $opt) {

                    $text = trim($opt['text'] ?? '');

                    if ($text !== '') {
                        $question->options()->create([
                            'option_text' => $text,
                            'score'       => $opt['score'] ?? 0,
                        ]);
                    }
                }

            } else {
                // Kalau tipe bukan pilihan → pastikan options kosong
                $question->options()->delete();
            }
        }

        DB::commit();

        return redirect()
            ->route('questionnaire.index')
            ->with('success', 'All changes saved successfully');

    } catch (\Throwable $e) {
    DB::rollBack();
    Log::error('UpdateAll failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
}
}


    /* =======================
     * STORE QUESTION (MODAL / AJAX)
     * ======================= */
    public function storeQuestion(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'category_id'     => 'required|exists:categories,id',
                'question_text'   => 'required|string',
                'question_type'   => 'required|in:pilihan,isian',
                'indicator'       => 'nullable',
                'attachment_text' => 'nullable|string',
                'clue'            => 'nullable|string',
            ]);

            $lastNumber = Question::where('category_id', $validated['category_id'])
                ->whereNotNull('question_no')
                ->pluck('question_no')
                ->map(fn ($no) => (int) $no)
                ->max();

            $nextNumber = $lastNumber ? $lastNumber + 1 : 1;

            $question = Question::create([
                ...$validated,
                'question_no'    => $nextNumber,
                'has_attachment' => !empty($validated['attachment_text']),
            ]);


            if ($question->question_type === 'pilihan' && $request->options) {
                foreach ($request->options as $opt) {
                    if (!empty($opt['text'])) {
                        $question->options()->create([
                            'option_text' => $opt['text'],
                            'score'       => $opt['score'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'question' => $question->load('options')
            ]);

        } catch (\Throwable $e) {
    DB::rollBack();
    Log::error('UpdateAll failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
}
    }

    /* =======================
     * DELETE QUESTION
     * ======================= */
    public function destroyQuestion(Question $question)
    {
        $question->options()->delete();
        $question->delete();

        return back()->with('success', 'Question deleted');
    }
}
