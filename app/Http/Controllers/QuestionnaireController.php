<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class QuestionnaireController extends Controller
{
    public function index()
    {
        $categories = Category::with(['questions' => function($query) {
            $query->orderBy('order');
        }])->get();

        return view('questionnaire.index', compact('categories'));
    }

    // Menampilkan semua pertanyaan untuk diedit
public function editAll()
{
    $categories = Category::with(['questions.options'])->get();
    return view('questionnaire.editAll', compact('categories'));
}

// Update semua pertanyaan sekaligus
public function updateAll(Request $request)
{
    DB::beginTransaction();
    try {
        foreach ($request->questions as $id => $data) {
            $question = Question::findOrFail($id);

            $question->update([
                'question_text' => $data['question_text'],
                'question_type' => $data['question_type'],
                'category_id'   => $data['category_id'],
                'has_attachment'=> isset($data['has_attachment']),
                'indicator'     => $data['indicator'] ?? null,
                'order'         => $data['order'] ?? $question->order,
            ]);

            // Hapus options lama dan simpan baru jika tipe pilihan
            if (in_array($data['question_type'], ['pilihan', 'checkbox'])) {
                $question->options()->delete();

                if (!empty($data['options'])) {
                    foreach ($data['options'] as $option) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $option['text'],
                            'score'       => $option['score'] ?? 0,
                        ]);
                    }
                }
            } else {
                $question->options()->delete();
            }
        }

        DB::commit();

        return redirect()->route('questionnaire.index')
            ->with('success', 'Semua pertanyaan berhasil diupdate');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', $e->getMessage());
    }
}



    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|integer|min:1|max:10'
        ]);

        $category = Category::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $category
            ]);
        }

        return redirect()->route('questionnaire.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    public function storeQuestion(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'question_text' => 'required|string',
                'question_type' => 'required|in:pilihan,isian,checkbox',
                'clue' => 'nullable|string',
                'has_attachment' => 'boolean',
                'indicator' => 'nullable|in:high,medium,low',
                'order' => 'integer'
            ]);

            $question = Question::create($validated);

            // Jika tipe pilihan, simpan options
            if (in_array($validated['question_type'], ['pilihan', 'checkbox']) && $request->has('options')) {
                foreach ($request->options as $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'score' => $option['score']
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'question' => $question->load('options')
                ]);
            }

            return redirect()->route('questionnaire.index')
                ->with('success', 'Pertanyaan berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateQuestion(Request $request, Question $question)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'question_text' => 'required|string',
                'question_type' => 'required|in:pilihan,isian,checkbox',
                'clue' => 'nullable|string',
                'has_attachment' => 'boolean',
                'indicator' => 'nullable|in:high,medium,low',
                'order' => 'integer'
            ]);

            $question->update($validated);

            // Hapus options lama jika ada
            if (in_array($validated['question_type'], ['pilihan', 'checkbox'])) {
                $question->options()->delete();
                
                if ($request->has('options')) {
                    foreach ($request->options as $option) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $option['text'],
                            'score' => $option['score']
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroyQuestion(Question $question)
    {
        $question->delete();
        return response()->json(['success' => true]);
    }

    public function updateOrder(Request $request)
    {
        foreach ($request->questions as $question) {
            Question::where('id', $question['id'])->update(['order' => $question['order']]);
        }

        return response()->json(['success' => true]);
    }
}