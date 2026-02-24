<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Exports\AssessmentExport;
use App\Imports\AssessmentImport;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Log;
use App\Exports\AssessmentReportExport;
use App\Models\AssessmentHistory;



class AssessmentController extends Controller
{
    public function index(Request $request)
{
    $month   = $request->input('month');
    $year    = $request->input('year');
    $company = $request->input('company');

    $query = Assessment::orderBy('assessment_date', 'desc');

    if ($company) {
        // buang titik & spasi dari input
        $keyword = strtolower(preg_replace('/[^a-z0-9]/', '', $company));

        $query->whereRaw(
            "LOWER(REPLACE(REPLACE(company_name, '.', ''), ' ', '')) LIKE ?",
            ['%' . $keyword . '%']
        );
    }

    if ($month) {
        $query->whereMonth('assessment_date', $month);
    }

    if ($year) {
        $query->whereYear('assessment_date', $year);
    }

    $assessments = $query->get();
    if ($request->ajax()) {
        return view('assessment._table', compact('assessments'))->render();
    }

    session(['assessment_list_url' => request()->fullUrl()]);

    $totalCategories = Category::count();
    $totalQuestions  = Question::where('is_active', true)->count();
    $totalAssessments = Assessment::count();

    return view('assessment.index', compact(
        'assessments',
        'month',
        'year',
        'totalCategories',
        'totalQuestions',
        'totalAssessments'
    ));
}



public function exportReport(Request $request)
{
    $month   = $request->input('month');
    $year    = $request->input('year');
    $company = $request->input('company');

    $query = Assessment::orderBy('assessment_date', 'desc');

    if ($company) {
        $keyword = strtolower(preg_replace('/[^a-z0-9]/', '', $company));

        $query->whereRaw(
            "LOWER(REPLACE(REPLACE(company_name, '.', ''), ' ', '')) LIKE ?",
            ['%' . $keyword . '%']
        );
    }

    if ($month) {
        $query->whereMonth('assessment_date', $month);
    }

    if ($year) {
        $query->whereYear('assessment_date', $year);
    }

    $assessments = $query->get();

    return Excel::download(
        new AssessmentReportExport($assessments),
        'assessment_report_' . now()->format('Ymd_His') . '.xlsx'
    );
}

    public function create()
{
    $categories = Category::with([
        'activeQuestions' => function($query) {
            $query->orderBy('order_index');
        },
        'activeQuestions.options'
    ])->get();

    $assessment = null; // <-- tambahkan ini supaya Blade aman

    return view('assessment.create', compact('categories', 'assessment'));
}

public function store(Request $request)
{
    $request->validate([
        'company_name'   => 'required|string|max:255',
        'category_level' => 'required|array',
        'category_level.*' => 'in:low,medium,high,umum',
    ]);
    $justifications = $request->input('category_justification', []);

    // debug awal
    Log::info('Assessment store request', $request->all());

    DB::beginTransaction();

    try {
        // --- Prepare category_scores & answers ---
        $categoryScores = [];
        $answersToCreate = [];

        foreach ($request->category_level as $categoryId => $level) {
            // Jika category 0 atau nama category = Umum
   $category = Category::find($categoryId);

if ($category && strtolower($category->name) === 'umum') {
    $level = 'umum';
}
            $categoryScores[$categoryId] = [
    'score' => 0,
    'indicator' => $level,
    'assessor' => null,
    'justification' => $justifications[$categoryId] ?? null,
    'actual_score' => 0,
    'max_score' => 0,
];

            $questions = Question::where('category_id', $categoryId)
                ->where('is_active', true)
                ->where(function ($query) use ($level) {
                    $query->whereJsonContains('indicator', $level)
                          ->orWhere('indicator', 'LIKE', "%{$level}%");
                })
                ->orderBy('order_index')
                ->get();

            foreach ($questions as $question) {
                $answersToCreate[] = [
                    'question_id' => $question->id,
                    'answer_text' => null,
                    'score' => 0,
                ];
            }
        }

        // --- Create Assessment ---
        try {
            $assessment = Assessment::create([
                'company_name'    => $request->company_name,
                'assessment_date' => now(),
                'total_score'     => 0,
                'risk_level'      => null,
                'category_scores' => $categoryScores,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Assessment create failed: '.$e->getMessage());
            return back()->with('alert_error', 'Gagal membuat assessment: '.$e->getMessage())->withInput();
        }

        // --- Create Answers ---
        foreach ($answersToCreate as $answerData) {
            $assessment->answers()->create($answerData);
        }

        DB::commit();

        // --- Redirect atau AJAX response ---
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Assessment berhasil ditambahkan',
                'redirect' => route('assessment.show', $assessment->id)
            ]);
        }

        return redirect()->route('assessment.show', $assessment->id)
                         ->with('alert_success', 'Assessment berhasil ditambahkan');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Assessment store error: '.$e->getMessage());
        return back()->with('alert_error', 'Gagal menambahkan assessment: '.$e->getMessage())
                     ->withInput();
    }
}

public function edit($id)
{
    $assessment = Assessment::with(['answers.question.category', 'answers.question.options'])
                            ->findOrFail($id);

    $categories = Category::with(['activeQuestions' => function($q) use ($assessment) {
        $q->where('is_active', true)
          ->orderBy('order_index');
    }, 'activeQuestions.options'])->get();

    // Ambil indikator terakhir dari category_scores
    $categoryLevels = $assessment->category_scores ?? [];

    return view('assessment.create', compact('assessment', 'categories', 'categoryLevels'));
}

public function update(Request $request, $id)
{
    $assessment = Assessment::findOrFail($id);

    $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'vendor_status' => 'nullable|string',
        'tier_criticality' => 'nullable|string',
        'category_level' => 'required|array',
        'category_level.*' => 'in:low,medium,high,umum'
    ]);
    $justifications = $request->input('category_justification', []);

    DB::beginTransaction();

    try {

        /*
        =========================================
        SNAPSHOT OLD STATE
        =========================================
        */

        $oldSnapshot = [
            'company_name' => $assessment->company_name,
            'vendor_status' => $assessment->vendor_status,
            'tier_criticality' => $assessment->tier_criticality,
            'category_scores' => $assessment->category_scores,
        ];

        /*
        =========================================
        BUILD CATEGORY METADATA ONLY
        =========================================
        */

        $categoryScores = [];

        foreach ($validated['category_level'] as $categoryId => $level) {

            $category = Category::find($categoryId);

            if ($category && strtolower($category->name) === 'umum') {
                $level = 'umum';
            }

            $existing = $assessment->category_scores[$categoryId] ?? [];

            $categoryScores[$categoryId] = [
                'indicator' => $level,
                'assessor' => $existing['assessor'] ?? null,
                'justification' => $justifications[$categoryId] ?? ($existing['justification'] ?? null),
                'actual_score' => $existing['actual_score'] ?? 0,
                'max_score' => $existing['max_score'] ?? 0,
                'score' => $existing['score'] ?? 0,
            ];
        }

        /*
        =========================================
        UPDATE MODEL
        =========================================
        */

        $assessment->fill([
            'company_name' => $validated['company_name'],
            'vendor_status' => $validated['vendor_status'] ?? $assessment->vendor_status,
            'tier_criticality' => $validated['tier_criticality'] ?? $assessment->tier_criticality,
            'category_scores' => $categoryScores,
            'evaluated_at' => now()
        ]);

        $assessment->calculateTierCriticality();
        $assessment->save();

        /*
        =========================================
        SNAPSHOT NEW STATE
        =========================================
        */

        $newSnapshot = [
            'company_name' => $assessment->company_name,
            'vendor_status' => $assessment->vendor_status,
            'tier_criticality' => $assessment->tier_criticality,
            'category_scores' => $assessment->category_scores,
        ];

        /*
        =========================================
        AUDIT HISTORY → STATUS ONLY
        =========================================
        */

        $changeType = null;

if (($oldSnapshot['vendor_status'] ?? null) !== ($newSnapshot['vendor_status'] ?? null)) {
    $changeType = 'status';
}

if (($oldSnapshot['tier_criticality'] ?? null) !== ($newSnapshot['tier_criticality'] ?? null)) {
    $changeType = 'tier';
}

if ($changeType) {
    AssessmentHistory::create([
        'assessment_id' => $assessment->id,
        'change_type' => $changeType,
        'old_value' => $oldSnapshot,
        'new_value' => $newSnapshot
    ]);
}

        DB::commit();

        return redirect()
            ->route('assessment.show', $assessment->id)
            ->with('success', 'Assessment berhasil diperbarui');

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error('Assessment update error: '.$e->getMessage());

        return back()->with('error', 'Gagal memperbarui assessment');
    }
}

public function heatmapTPSA()
{
    $assessments = Assessment::all();

    $matrix = [
        'high' => [
            'Kurang Memadai' => [],
            'Cukup Memadai'  => [],
            'Sangat Memadai' => [],
        ],
        'medium' => [
            'Kurang Memadai' => [],
            'Cukup Memadai'  => [],
            'Sangat Memadai' => [],
        ],
        'low' => [
            'Kurang Memadai' => [],
            'Cukup Memadai'  => [],
            'Sangat Memadai' => [],
        ],
    ];

    foreach ($assessments as $assessment) {
        $cell = $assessment->heatmap_cell;

        $matrix[$cell['x']][$cell['y']][] = [
            'company' => $cell['company'],
            'score'   => $cell['score'],
        ];
    }

    return view('assessment.heatmap-tpsa', compact('matrix'));
}




// App/Http/Controllers/AssessmentController.php

public function destroy(Assessment $assessment)
{
    try {
        // Hapus jawaban dulu
        $assessment->answers()->delete();

        // Hapus assessment
        $assessment->delete();

        return redirect()->route('assessment.index')
                         ->with('success', 'Assessment berhasil dihapus.');
    } catch (\Exception $e) {
        return redirect()->route('assessment.index')
                         ->with('error', 'Gagal menghapus assessment: ' . $e->getMessage());
    }
}

private function determineChangeType(array $old, array $new): string
{
    if ($old['company_name'] !== $new['company_name']) {
        return 'tier';
    }

    if (($old['tier_criticality'] ?? null) !== ($new['tier_criticality'] ?? null)) {
        return 'tier';
    }

    if (($old['vendor_status'] ?? null) !== ($new['vendor_status'] ?? null)) {
        return 'status';
    }

    return 'tier';
}
 public function show(Assessment $assessment)
{
    $assessment->load('answers.question.category', 'answers.question.options');

    $categories = Category::all();

    $month = request('month');
    $year  = request('year');

    // LEFT SIDE (status & tier) + FILTER
    $historyDetails = AssessmentHistory::where('assessment_id', $assessment->id)
        ->whereIn('change_type', ['status', 'tier'])
        ->when($month, function($query) use ($month){
            $query->whereMonth('created_at', $month);
        })
        ->when($year, function($query) use ($year){
            $query->whereYear('created_at', $year);
        })
        ->orderByDesc('created_at')
        ->get();

    // RIGHT SIDE (result upload) + FILTER
    $historyScores = AssessmentHistory::where('assessment_id', $assessment->id)
        ->where('change_type', 'result')
        ->when($month, function($query) use ($month){
            $query->whereMonth('created_at', $month);
        })
        ->when($year, function($query) use ($year){
            $query->whereYear('created_at', $year);
        })
        ->orderByDesc('created_at')
        ->get();

    return view('assessment.show', compact(
        'assessment',
        'categories',
        'historyDetails',
        'historyScores'
    ));
}


public function exportBlankHistory($id)
{
    $history = AssessmentHistory::findOrFail($id);

    return Excel::download(
        new AssessmentExport($history), // nanti export class baca dari snapshot
        'assessment_blank_'.$history->id.'.xlsx'
    );
}

public function exportResultHistory($id)
{
    $history = AssessmentHistory::findOrFail($id);

    return Excel::download(
        new AssessmentExport($history, true),
        'assessment_result_'.$history->id.'.xlsx'
    );
}

    public function export(Assessment $assessment)
    {
        return Excel::download(
            new AssessmentExport($assessment), 
            'assessment_'.$assessment->company_name.'.xlsx');
    }

    public function previewExport(Assessment $assessment)
    {
        $assessment->load(['answers.question', 'answers.question.options']);
        $categories = Category::with('activeQuestions')->get();
        
        return view('assessment.preview-excel', compact('assessment', 'categories'));
    }

    public function import(Request $request, Assessment $assessment)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xls,xlsx|max:5120',
    ]);

    DB::beginTransaction();

    try {

        /*
        ----------------------------------
        OLD SNAPSHOT BEFORE IMPORT
        ----------------------------------
        */

        $oldSnapshot = [
    'evaluated_at' => $assessment->evaluated_at,
    'assessor' => $assessment->assessor ?? null,
    'vendor_status' => $assessment->vendor_status ?? null,
    'tier_criticality' => $assessment->tier_criticality ?? null,
    'total_score' => $assessment->total_score,
    'risk_level' => $assessment->risk_level,
    'category_scores' => $assessment->category_scores,

    /*
    ----------------------------------
    ANSWER REFERENCE ONLY
    ----------------------------------
    */

    'answer_ids' => $assessment->answers()
        ->where('is_active', true)
        ->pluck('id')
        ->toArray()
];
        /*
        ----------------------------------
        IMPORT EXCEL
        ----------------------------------
        */

        $file = $request->file('excel_file');

        Excel::import(new AssessmentImport($assessment), $file);

        $assessment->evaluated_at = now();

        $assessment->calculateCategoryScores();
        $assessment->calculateTierCriticality();

        $assessment->save();

        /*
        ----------------------------------
        NEW SNAPSHOT AFTER IMPORT
        ----------------------------------
        */

       $newSnapshot = [
    'evaluated_at' => $assessment->evaluated_at,
    'assessor' => $assessment->assessor ?? null,
    'vendor_status' => $assessment->vendor_status ?? null,
    'tier_criticality' => $assessment->tier_criticality ?? null,
    'total_score' => $assessment->total_score,
    'risk_level' => $assessment->risk_level,
    'category_scores' => $assessment->category_scores,

    'answer_ids' => $assessment->answers()
        ->where('is_active', true)
        ->pluck('id')
        ->toArray()
];

        /*
        ----------------------------------
        HISTORY RESULT
        ----------------------------------
        */

        AssessmentHistory::create([
            'assessment_id' => $assessment->id,
            'change_type' => 'result',

            'old_value' => $oldSnapshot,
            'new_value' => $newSnapshot
        ]);

        DB::commit();

        return redirect()
            ->route('assessment.index')
            ->with('success', 'Data berhasil diimport.');

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error('Assessment import error: ' . $e->getMessage());

        return redirect()
            ->route('assessment.index')
            ->with('import_errors', ['Import gagal', $e->getMessage()]);
    }
}


}