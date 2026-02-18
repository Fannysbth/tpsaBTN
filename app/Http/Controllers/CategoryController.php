<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(Request $request)
{
    $categories = Category::with([
    'questions' => function ($query) {
        $query->orderByRaw("
    CAST(regexp_replace(question_no, '[^0-9]', '', 'g') AS INTEGER),
    question_no
")
->with('options');
    }
])->get();


    $selectedCategory = null;

    if ($request->filled('selected')) {
        $categories = Category::with([
    'questions' => function ($query) {
        $query->orderByRaw("
            CAST(question_no AS UNSIGNED),
            question_no
        ")->with('options');
    }
])->get();

    }

    // 🔥 JIKA TIDAK ADA CATEGORY SAMA SEKALI
    // ATAU TIDAK ADA YANG DI-SELECT
    // → OTOMATIS CREATE MODE
    if (!$selectedCategory && $categories->count() === 0) {
        return view('questionnaire.index', [
            'categories' => $categories,
            'selectedCategory' => null
        ]);
    }

    // kalau ada category tapi belum select
    if (!$selectedCategory && $categories->count() > 0) {
        $selectedCategory = $categories->first();
    }

    return view('questionnaire.index', compact(
        'categories',
        'selectedCategory'
    ));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name'     => 'required|string|max:255',
        'criteria' => 'nullable|array',
    ]);

    $category = Category::create([
        'name'     => $validated['name'],
        'criteria' => $validated['criteria'] ?? null,
    ]);

    // 🔥 TAMBAHKAN INI
    if ($request->questions) {
        foreach ($request->questions as $q) {

            $question = $category->questions()->create([
                'question_text'   => $q['question_text'] ?? null,
                'question_type'   => $q['question_type'] ?? 'pilihan',
                'indicator'       => $q['indicator'] ?? [],
                'attachment_text' => $q['attachment_text'] ?? null,
                'clue'            => $q['clue'] ?? null,
                'has_attachment'  => !empty($q['attachment_text']),
                'sub'             => $q['sub'] ?? null,
            ]);

            if (
                ($q['question_type'] ?? 'pilihan') === 'pilihan'
                && !empty($q['options'])
            ) {
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
    }

    return redirect()
        ->route('categories.create')
        ->with('success', 'Category & Questions created!');
}



    public function create(Request $request)
    {
        $categories = Category::all();
        $selectedCategory = null;
        
        if ($request->has('selected')) {
            $selectedCategory = Category::with(['questions' => function($query) {
    $query->orderByRaw("
    CAST(regexp_replace(question_no, '[^0-9]', '', 'g') AS INTEGER),
    question_no
")->with('options');

}])->find($request->selected);

        }
        
        return view('categories.create', compact('categories', 'selectedCategory'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'criteria.high' => 'nullable|string|max:255',
            'criteria.medium' => 'nullable|string|max:255',
            'criteria.low' => 'nullable|string|max:255',
        ]);
        
        $category = Category::findOrFail($id);
        $category->update([
            'name' => $request->name,
            'criteria' => $request->criteria,
        ]);
        
        // Handle deleted questions
        if ($request->deleted_questions) {
            $deletedIds = explode(',', $request->deleted_questions);
            foreach ($deletedIds as $questionId) {
                $question = $category->questions()->find($questionId);
                if ($question) {
                    $question->options()->delete();
                    $question->delete();
                }
            }
        }
        
        // Update existing questions and create new ones
        if ($request->questions) {
            foreach ($request->questions as $key => $q) {
                if (str_starts_with($key, 'new_')) {
                    // Create new question
                    $question = $category->questions()->create([
                        'question_text'   => $q['question_text'] ?? null,
                        'question_type'   => $q['question_type'] ?? 'pilihan',
                        'indicator'       => $q['indicator'] ?? [],
                        'attachment_text' => $q['attachment_text'] ?? null,
                        'clue'            => $q['clue'] ?? null,
                        'has_attachment'  => !empty($q['attachment_text']),
                        'sub' => $q['sub'] ?? null,
                    ]);
                    
                    if (($q['question_type'] ?? 'pilihan') === 'pilihan' && !empty($q['options'])) {
                        foreach ($q['options'] as $opt) {
                            if (!empty($opt['text'])) {
                                $question->options()->create([
                                    'option_text' => $opt['text'],
                                    'score'       => $opt['score'] ?? 0,
                                ]);
                            }
                        }
                    }
                } else {
                    // Update existing question
                    $question = $category->questions()->find($key);
                    if ($question) {
                        $question->update([
                            'question_text'   => $q['question_text'] ?? null,
                            'question_type'   => $q['question_type'] ?? 'pilihan',
                            'indicator'       => $q['indicator'] ?? [],
                            'attachment_text' => $q['attachment_text'] ?? null,
                            'clue'            => $q['clue'] ?? null,
                            'has_attachment'  => !empty($q['attachment_text']),
                            'sub' => $q['sub'] ?? null,
                        ]);
                        
                        // Delete existing options
                        $question->options()->delete();
                        
                        // Create new options
                        if (($q['question_type'] ?? 'pilihan') === 'pilihan' && !empty($q['options'])) {
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
                }
            }
        }
        
        return redirect()->back()->with('success', 'Category updated successfully!');
    }
    
    public function destroy($id)
    {
        $category = Category::with('questions.options')->findOrFail($id);
        
        foreach ($category->questions as $question) {
            $question->options()->delete();
            $question->delete();
        }
        
        $category->delete();
        
        return redirect()->route('categories.create')->with('success', 'Category deleted successfully');
    }
}