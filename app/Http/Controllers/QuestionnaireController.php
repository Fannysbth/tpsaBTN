<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exports\QuestionnaireExport;
use App\Imports\QuestionnaireImport;
use Maatwebsite\Excel\Facades\Excel;



class QuestionnaireController extends Controller
{
    public function export()
{
    return Excel::download(new QuestionnaireExport, 'questionnaire.xlsx');
}

    public function index()
{
    $categories = Category::with(['questions' => function ($query) {
        $query->orderBy('order');
    }])->get();

    // 🔥 tentukan berapa kategori yang tampil di bar utama
    $mainCategories = $categories->take(3);
    $moreCategories = $categories->skip(3);

    return view('questionnaire.index', compact(
        'categories',
        'mainCategories',
        'moreCategories'
    ));
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
            foreach ($request->questions ?? [] as $id => $data) {
                // Skip if marked for deletion
                if (isset($data['_delete']) && $data['_delete'] == '1') {
                    if (is_numeric($id)) {
                        Question::where('id', $id)->delete();
                    }
                    continue;
                }

                // Skip if question text is empty
                $text = trim($data['question_text'] ?? '');

        if ($text === '') {
            continue;
        }


                $isNew = str_starts_with($id, 'new_');

                if ($isNew) {
                    // Create new question
                    $question = new Question();
                } else {
                    // Update existing question
                    $question = Question::find($id);
                    if (!$question) {
                        continue;
                    }
                }

                // Update question data
                $question->question_text = $text;
                $question->question_type = $data['question_type'] ?? 'pilihan';
                $question->category_id = $data['category_id'] ?? null;
                $question->indicator = json_encode($data['indicator'] ?? []);
                $question->attachment_text = $data['attachment_text'] ?? null;
                $question->clue = $data['clue'] ?? null;
                $question->sub = $data['sub'] ?? null;


                $question->has_attachment = !empty($data['attachment_text']);
                $question->order = 0;

                $question->save();

                // Handle options based on question type
                if ($question->question_type === 'pilihan') {
                    // Delete existing options for existing questions
                    if (!$isNew) {
                        $question->options()->delete();
                    }

                    // Add new options
                    foreach ($data['options'] ?? [] as $optionId => $optionData) {
                        if (!empty($optionData['text'])) {
                           QuestionOption::create([
        'question_id' => $question->id,
        'option_text' => $optionData['text'],
        'score' => $optionData['score'] ?? 0,
        ]);

                        }
                    }
                } else {
                    // For text answer questions, remove all options
                    $question->options()->delete();
                }
            }

            DB::commit();

            return redirect()
                ->route('questionnaire.index')
                ->with('success', 'All changes have been saved successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to save questionnaire: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to save changes. Please try again.');
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
            ->with('success', 'Category added successfully');
    }

    public function storeQuestion(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'question_text' => 'required|string',
                'question_type' => 'required|in:pilihan,isian',
                'clue' => 'nullable|string',
                'indicator' => 'nullable|array',
                'indicator.*' => 'in:high,medium,low',
                'attachment_text' => 'nullable|string',
                'order' => 'integer'
            ]);

            $question = Question::create($validated);

            // Jika tipe pilihan, simpan options
            if ($validated['question_type'] == 'pilihan' && $request->has('options')) {
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
                ->with('success', 'Question added successfully');
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
                'question_type' => 'required|in:pilihan,isian',
                'clue' => 'nullable|string',
                'has_attachment' => 'boolean',
                'indicator' => 'nullable|array',
                'indicator.*' => 'in:high,medium,low',
                'attachment_text' => 'nullable|string',
                'order' => 'integer'
            ]);

            $question->update($validated);

            // Hapus options lama jika ada
            if (in_array($validated['question_type'], ['pilihan'])) {
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