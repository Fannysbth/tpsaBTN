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
            $query->orderBy('order');
        },
        'activeQuestions.options'
    ])->get();

    $assessment = null; // <-- tambahkan ini supaya Blade aman

    return view('assessment.create', compact('categories', 'assessment'));
}


   public function store(Request $request)
{
    DB::beginTransaction();
    try {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'category_level' => 'required|array',
        ]);
        
        $categoryScores = [];
        $answersToCreate = [];
        
        foreach ($request->category_level as $categoryId => $level) {
            // Simpan indikator untuk kategori
            $categoryScores[$categoryId] = [
                'score' => 0,
                'indicator' => $level,
                'actual_score' => 0,
                'max_score' => 0
            ];
            
            // Ambil pertanyaan yang sesuai dengan indikator
            $questions = Question::where('category_id', $categoryId)
                ->where('is_active', true)
                ->where(function($query) use ($level) {
                    $query->whereJsonContains('indicator', $level)
                          ->orWhere('indicator', 'LIKE', "%{$level}%");
                })
                ->orderBy('order')
                ->get();
            
            foreach ($questions as $question) {
                // Set jawaban default
                $defaultAnswer = null;
                $defaultScore = 0;
                
                // Jika ada clue, gunakan sebagai default
                if ($question->clue) {
                    $defaultAnswer = $question->clue;
                }
                
                $answersToCreate[] = [
                    'question_id' => $question->id,
                    'score' => $defaultScore,
                    'answer_text' => $defaultAnswer,
                ];
            }
        }
        
        // Simpan assessment
        $assessment = Assessment::create([
            'company_name' => $request->company_name,
            'assessment_date' => now(),
            'total_score' => 0,
            'risk_level' => null,
            'category_scores' => $categoryScores,
        ]);
        
        // Simpan semua jawaban
        foreach ($answersToCreate as $answerData) {
            $assessment->answers()->create($answerData);
        }
        
        DB::commit();
        
        return redirect()->route('assessment.show', $assessment)
            ->with('alert_success', 'Assessment berhasil ditambahkan.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Assessment store error: ' . $e->getMessage());
        
        return back()
            ->with('alert_error', 'Gagal menambahkan assessment: ' . $e->getMessage())
            ->withInput();
    }
}

public function edit($id)
{
    $assessment = Assessment::with(['answers.question.category', 'answers.question.options'])
                            ->findOrFail($id);

    $categories = Category::with(['activeQuestions' => function($q) use ($assessment) {
        $q->where('is_active', true)
          ->orderBy('order');
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
        'category_level.*' => 'in:low,medium,high'
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
                ->orderBy('order')
                ->get();

            foreach ($questions as $question) {
                $answersToCreate[] = [
                    'question_id' => $question->id,
                    'answer_text' => $question->clue ?? null,
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
$redirectUrl = session('assessment_list_url', route('assessment.index'));

return redirect($redirectUrl)
       ->with('success', 'Assessment berhasil diperbarui. Jawaban lama dihapus dan siap diisi ulang.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Assessment update error: ' . $e->getMessage());
        return back()->with('error', 'Gagal memperbarui assessment: '.$e->getMessage())->withInput();
    }
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




private function getLevelsByIndicator($indicator)
{
    switch ($indicator) {
        case 'low':
            return ['low'];
        case 'medium':
            return ['low','medium'];
        case 'high':
            return ['low','medium','high'];
        default:
            return [];
    }
}


    public function show(Assessment $assessment)
{
    $assessment->load('answers.question.category', 'answers.question.options');

    $categories = Category::with(['questions' => function($q) use ($assessment) {
        $q->where('is_active', true)
          ->orderBy('order')
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
        return Excel::download(new AssessmentExport($assessment), 'assessment_'.$assessment->id.'.xlsx');
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
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new AssessmentImport($assessment), $request->file('excel_file'));

        $assessment->calculateTotalScore();
        $assessment->calculateCategoryScores();

        return redirect()->back()->with('success', 'Data berhasil diimport');
    }

    public function sendEmail(Request $request, Assessment $assessment)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);

        $export = new AssessmentExport($assessment);
        $filePath = 'exports/assessment_'.$assessment->id.'_'.time().'.xlsx';
        Excel::store($export, $filePath);

        Mail::to($request->email)
            ->send(new AssessmentReport($assessment, $filePath, $request->subject, $request->message));

        return response()->json(['success' => true]);
    }
}