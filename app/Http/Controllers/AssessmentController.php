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

class AssessmentController extends Controller
{
    public function index()
    {
        $month = request('month', date('m'));
        $year = request('year', date('Y'));

        $assessments = Assessment::whereMonth('assessment_date', $month)
            ->whereYear('assessment_date', $year)
            ->orderBy('assessment_date', 'desc')
            ->get();

        return view('assessment.index', compact('assessments', 'month', 'year'));
    }

    public function create()
    {
        $categories = Category::with(['activeQuestions' => function($query) {
            $query->orderBy('order');
        }, 'activeQuestions.options'])->get();

        return view('assessment.create', compact('categories'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $assessment = Assessment::create([
                'company_name' => $request->company_name,
                'assessment_date' => now(),
            ]);

            foreach ($request->answers as $questionId => $answerData) {
                $question = Question::find($questionId);
                
                $score = 0;
                if ($question->question_type === 'pilihan' || $question->question_type === 'checkbox') {
                    $selectedOption = $question->options()
                        ->where('option_text', $answerData['answer'])
                        ->first();
                    $score = $selectedOption ? $selectedOption->score : 0;
                }

                Answer::create([
                    'assessment_id' => $assessment->id,
                    'question_id' => $questionId,
                    'answer_text' => $answerData['answer'],
                    'score' => $score,
                    'attachment_path' => $answerData['attachment'] ?? null
                ]);
            }

            $assessment->calculateTotalScore();
            $assessment->calculateCategoryScores();

            DB::commit();

            return response()->json([
                'success' => true,
                'assessment_id' => $assessment->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function show(Assessment $assessment)
    {
        $assessment->load(['answers.question.category', 'answers.question.options']);
        
        $categories = Category::with(['questions' => function($query) {
            $query->where('is_active', true)->orderBy('order');
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