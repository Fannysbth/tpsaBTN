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
    public function index(Request $request)
    {
        $month = $request->input('month'); // bisa null
        $year = $request->input('year');   // bisa null

        // Ambil semua assessments, bisa filter bulan/tahun jika dipilih
        $query = Assessment::orderBy('assessment_date', 'desc');

        if ($month) {
            $query->whereMonth('assessment_date', $month);
        }

        if ($year) {
            $query->whereYear('assessment_date', $year);
        }

        $assessments = $query->get();

        // Summary: total seluruh data (jika mau summary juga ikut filter, ubah query sama seperti di atas)
        $totalCategories = Category::count();
        $totalQuestions = Question::where('is_active', true)->count();
        $totalAssessments = Assessment::count();

        return view('dashboard.index', compact(
            'assessments', 
            'month', 
            'year', 
            'totalCategories', 
            'totalQuestions', 
            'totalAssessments'
        ));
    }

}