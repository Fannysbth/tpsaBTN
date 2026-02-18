<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Category;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Exports\AssessmentExport;
use App\Imports\AssessmentImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssessmentReport;
use Illuminate\Support\Facades\Log;
use App\Exports\AssessmentReportExport;



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

    // debug awal
    Log::info('Assessment store request', $request->all());

    DB::beginTransaction();

    try {
        // --- Prepare category_scores & answers ---
        $categoryScores = [];
        $answersToCreate = [];

        foreach ($request->category_level as $categoryId => $level) {
            $categoryScores[$categoryId] = [
                'score' => 0,
                'indicator' => $level,
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
        'category_level' => 'required|array',
        'category_level.*' => 'in:low,medium,high,umum'
    ]);

    DB::beginTransaction();
    try {
        // 1. Update nama perusahaan
        $assessment->update([
            'company_name' => $validated['company_name']
        ]);

        // 2. Hapus semua jawaban lama
        $assessment->answers()->delete();

        // 3. Reset category_scores sesuai indikator baru
        $categoryScores = [];
        $answersToCreate = [];

        foreach ($validated['category_level'] as $categoryId => $level) {
            $categoryScores[$categoryId] = [
                'score' => 0,
                'indicator' => $level,
                'actual_score' => 0,
                'max_score' => 0
            ];

            // Ambil pertanyaan sesuai indikator
            $questions = Question::where('category_id', $categoryId)
                ->where('is_active', true)
                ->where(function($query) use ($level) {
                    $query->whereJsonContains('indicator', $level)
                          ->orWhere('indicator', 'LIKE', "%{$level}%");
                })
                ->orderBy('order_index')
                ->get();



            foreach ($questions as $question) {
                $answersToCreate[] = [
                    'question_id' => $question->id,
                    'answer_text' =>  null,
                    'score' => 0
                ];
            }
        }

        // 4. Update category_scores & reset total score / risk level
        $assessment->update([
            'category_scores' => $categoryScores,
            'total_score' => 0,
            'risk_level' => null
        ]);

        // 5. Simpan jawaban baru
        foreach ($answersToCreate as $answerData) {
            $assessment->answers()->create($answerData);
        }

        DB::commit();

        // Redirect kembali ke halaman list yang disimpan di session
return redirect()->route('assessment.show', $assessment->id)
       ->with('success', 'Assessment berhasil diperbarui. Jawaban lama dihapus dan siap diisi ulang.');


    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Assessment update error: ' . $e->getMessage());
        return back()->with('error', 'Gagal memperbarui assessment: '.$e->getMessage())->withInput();
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


    public function show(Assessment $assessment)
{
    $assessment->load('answers.question.category', 'answers.question.options');

    $categories = Category::with(['questions' => function($q) use ($assessment) {
        $q->where('is_active', true)
          ->orderBy('order_index')
          ->where(function($query) use ($assessment) {
              // Ambil indikator yang dipilih untuk kategori ini
              $categoryId = $query->getModel()->category_id;
              $selectedIndicator = $assessment->category_scores[$categoryId]['indicator'] ?? null;
              
              if ($selectedIndicator) {
                  $query->whereJsonContains('indicator', $selectedIndicator);
              }
          });
    }])->get();

    return view('assessment.show', compact('assessment', 'categories'));
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

    try {
        $file = $request->file('excel_file');

        // --- Load Excel ---
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        // --- Ambil header (baris pertama) ---
        $header = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $header[] = trim((string)$cell->getValue());
            }
        }

        // --- Kolom wajib sesuai template ---
        $requiredColumns = ['SUB KATEGORI', 'NO', 'PERTANYAAN', ':', 'JAWABAN', 'ATTACHMENT'];

        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            return redirect()
                ->route('assessment.index')
                ->with('import_errors', [
                    'Template Excel tidak sesuai.',
                    'Kolom hilang: ' . implode(', ', $missingColumns)
                ]);
        }

        // --- Jalankan import ---
        Excel::import(new AssessmentImport($assessment), $file);

        return redirect()
            ->route('assessment.index')
            ->with('success', 'Data berhasil diimport.');

    } catch (\Illuminate\Validation\ValidationException $e) {

        // 🔥 Ambil semua error dari Import Class
        $errors = collect($e->errors())->flatten()->toArray();

        return redirect()
            ->route('assessment.index')
            ->with('import_errors', $errors);

    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {

        return redirect()
            ->route('assessment.index')
            ->with('import_errors', [
                'File Excel tidak bisa dibaca.',
                'Pastikan file tidak corrupt dan format .xlsx atau .xls.'
            ]);

    } catch (\Throwable $e) {

        Log::error('Assessment import error: ' . $e->getMessage());

        return redirect()
            ->route('assessment.index')
            ->with('import_errors', [
                'Terjadi kesalahan saat proses import.',
                $e->getMessage()
            ]);
    }
}


}