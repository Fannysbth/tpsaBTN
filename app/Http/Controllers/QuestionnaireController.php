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
        $categories = Category::with([
            'questions' => fn ($q) => $q->orderBy('order_index')
        ])->get();

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
        $categories = Category::with([
            'questions.options' => fn ($q) => $q->orderBy('order_index')
        ])->get();

        return view('questionnaire.editAll', compact('categories'));
    }

    /* =======================
     * UPDATE ALL
     * ======================= */
    public function updateAll(Request $request)
    {
        DB::beginTransaction();

        try {
            // DELETE
            $deletedIds = collect(
                explode(',', $request->deleted_questions ?? '')
            )->filter()->map(fn ($id) => (int) $id);

            if ($deletedIds->isNotEmpty()) {
                QuestionOption::whereIn('question_id', $deletedIds)->delete();
                Question::whereIn('id', $deletedIds)->delete();
            }

            foreach ($request->questions ?? [] as $data) {

    if (trim($data['question_text'] ?? '') === '') continue;

    $question = Question::firstOrNew([
        'category_id' => $data['category_id'],
        'question_no' => trim($data['question_no']),
    ]);

    $question->fill([
        'order_index'     => $data['order_index'] ?? 0,
        'question_text'   => trim($data['question_text']),
        'question_type'   => $data['question_type'] ?? 'pilihan',
        'indicator'       => $data['indicator'] ?? null,
        'clue'            => $data['clue'] ?? null,
        'attachment_text' => $data['attachment_text'] ?? null,
        'has_attachment'  => !empty($data['attachment_text']),
        'sub'             => $data['sub'] ?? null,
    ]);

    $question->save();
}

            DB::commit();

            return redirect()
                ->route('questionnaire.index')
                ->with('success', 'All changes saved successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->with('error', 'Failed to save data');
        }
    }

    /* =======================
     * STORE QUESTION
     * ======================= */
    public function storeQuestion(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'category_id'   => 'required|exists:categories,id',
                'question_no'   => 'required|string',
                'question_text' => 'required|string',
                'question_type' => 'required|in:pilihan,isian',
                'indicator'     => 'nullable',
                'attachment_text' => 'nullable|string',
                'clue'          => 'nullable|string',
            ]);

            // 🔧 AUTO ORDER KE PALING BAWAH
            $validated['order_index'] =
                Question::where('category_id', $validated['category_id'])
                    ->max('order_index') + 1;

            $question = Question::create([
                ...$validated,
                'has_attachment' => !empty($validated['attachment_text']),
            ]);

            if (
                $question->question_type === 'pilihan'
                && $request->options
            ) {
                foreach ($request->options as $opt) {
                    if (!empty($opt['text'])) {
                        $question->options()->create([
                            'option_text' => $opt['text'],
                            'score' => $opt['score'] ?? 0,
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
            throw $e;
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

    /* =======================
     * UPDATE ORDER (DRAG)
     * ======================= */
    public function updateOrder(Request $request)
    {
        if (!$request->questions) {
            return response()->json(['success' => false]);
        }

        foreach ($request->questions as $q) {
            Question::where('id', $q['id'])
                ->update(['order_index' => $q['order']]);
        }

        return response()->json(['success' => true]);
    }
}
