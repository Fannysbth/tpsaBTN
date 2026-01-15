<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
   public function create()
{
    $categories = Category::all(); // ambil semua kategori
    return view('categories.create', compact('categories'));
}



public function destroy($id)
{
    Category::findOrFail($id)->delete();
    return redirect()->back()->with('success', 'Category deleted');
}


    public function store(Request $request)
    {
        $request->validate([
    'name' => 'required|string|max:255',
    'criteria.high' => 'nullable|string|max:255',
    'criteria.medium' => 'nullable|string|max:255',
    'criteria.low' => 'nullable|string|max:255',
]);


        $category = Category::create([
    'name' => $request->name,
    'criteria' => $request->criteria, // array → otomatis json
]);



        if ($request->questions) {
    foreach ($request->questions as $q) {
        $question = $category->questions()->create([
            'question_text'   => $q['question_text'] ?? null,
            'question_type'   => $q['question_type'] ?? 'pilihan',
            'indicator'       => $q['indicator'] ?? [],
            'attachment_text' => $q['attachment_text'] ?? null,
            'clue'            => $q['clue'] ?? null,
            'has_attachment'  => !empty($q['attachment_text']),
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
    }
}



        return redirect()->route('questionnaire.index')->with('success', 'Category created successfully!');
    }
}
