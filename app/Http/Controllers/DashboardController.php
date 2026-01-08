<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCategories = Category::count();
        $totalQuestions = Question::where('is_active', true)->count();
        $totalAssessments = Assessment::count();

        // Filter berdasarkan bulan
        $month = request('month', date('m'));
        $year = request('year', date('Y'));

        $assessments = Assessment::whereMonth('assessment_date', $month)
            ->whereYear('assessment_date', $year)
            ->orderBy('assessment_date', 'desc')
            ->get()
            ->map(function($assessment, $index) {
                return [
                    'no' => $index + 1,
                    'company_name' => $assessment->company_name,
                    'total_score' => $assessment->total_score,
                    'risk_level' => $assessment->risk_level,
                    'assessment_date' => $assessment->assessment_date->format('d/m/Y'),
                    'id' => $assessment->id
                ];
            });

        return view('dashboard.index', compact(
            'totalCategories',
            'totalQuestions',
            'totalAssessments',
            'assessments',
            'month',
            'year'
        ));
    }
}