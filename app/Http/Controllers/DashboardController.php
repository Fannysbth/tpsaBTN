<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');

        $query = Assessment::orderBy('assessment_date', 'desc');

        if ($month) {
            $query->whereMonth('assessment_date', $month);
        }

        if ($year) {
            $query->whereYear('assessment_date', $year);
        }

        $assessments = $query->get();

        $totalCategories   = Category::count();
        $totalQuestions    = Question::where('is_active', true)->count();
        $totalAssessments  = Assessment::count();

        // ======================
        // HEATMAP 1: Category × Compliance
        // ======================
        $heatmap1 = $this->generateCategoryComplianceHeatmap($assessments);

        // ======================
        // HEATMAP 2: Vendor × Compliance
        // ======================
        $heatmap2 = $this->generateVendorComplianceHeatmap($assessments);

        // ======================
        // HEATMAP 3: Category × Vendor (Global TPSA)
        // ======================
        $heatmap3 = $this->generateCategoryVendorHeatmap($assessments);

        return view('dashboard.index', compact(
            'assessments',
            'month',
            'year',
            'totalCategories',
            'totalQuestions',
            'totalAssessments',
            'heatmap1',
            'heatmap2',
            'heatmap3'
        ));
    }

    /**
     * HEATMAP 1: Category × Compliance
     * X-axis: Categories, Y-axis: Compliance Level
     */
    private function generateCategoryComplianceHeatmap($assessments)
{
    $categories = Category::orderBy('id')->get();
    $complianceLevels = ['Sangat Memadai', 'Cukup Memadai', 'Kurang Memadai'];

    $matrix = [];
    foreach ($complianceLevels as $level) {
        foreach ($categories as $category) {
            $matrix[$level][$category->name] = 0;
        }
    }

    foreach ($assessments as $assessment) {
    $categoryScores = $assessment->category_scores;

if (is_string($categoryScores)) {
    $categoryScores = json_decode($categoryScores, true);
}

if (!is_array($categoryScores)) {
    continue;
}


    if (!is_array($categoryScores)) {
        continue;
    }

    foreach ($categoryScores as $catId => $catData) {
        if (!isset($categories[$catId])) continue;

        $score = $catData['score'] ?? 0;
        $level = $this->getComplianceLevel($score);

        $matrix[$level][$categories[$catId]->name]++;
    }
}


    return [
        'title' => 'Heatmap: Kategori × Tingkat Kepatuhan',
        'subtitle' => 'Distribusi tingkat kepatuhan per kategori TPSA',
        'xAxis' => $categories->pluck('name')->values()->toArray(),
        'yAxis' => $complianceLevels,
        'data' => $matrix,
        'type' => 'category_compliance'
    ];
}


    /**
     * HEATMAP 2: Vendor × Compliance
     * X-axis: Vendors, Y-axis: Compliance Level
     */
    private function generateVendorComplianceHeatmap($assessments)
{
    $complianceLevels = ['Sangat Memadai', 'Cukup Memadai', 'Kurang Memadai'];
    $vendors = $assessments->pluck('company_name')->unique()->values();

    $matrix = [];
    foreach ($complianceLevels as $level) {
        foreach ($vendors as $vendor) {
            $matrix[$level][$vendor] = 0;
        }
    }

    foreach ($assessments as $assessment) {
        $vendor = $assessment->company_name;
        $score  = $assessment->total_score ?? 0;
        $level  = $this->getComplianceLevel($score);

        $matrix[$level][$vendor]++;
    }

    return [
        'title' => 'Heatmap: Vendor × Tingkat Kepatuhan',
        'subtitle' => 'Profil risiko vendor berdasarkan total score',
        'xAxis' => $vendors->toArray(),
        'yAxis' => $complianceLevels,
        'data' => $matrix,
        'type' => 'vendor_compliance'
    ];
}


    /**
     * HEATMAP 3: Category × Vendor (Global TPSA)
     * X-axis: Vendors, Y-axis: Categories
     */
    private function generateCategoryVendorHeatmap($assessments)
{
    $categories = Category::orderBy('id')->get();
    $vendors = $assessments->pluck('company_name')->unique()->values()->toArray();

    $matrix = [];
    $scores = [];

    foreach ($categories as $category) {
        foreach ($vendors as $vendor) {
            $matrix[$category->name][$vendor] = null;
            $scores[$category->name][$vendor] = null;
        }
    }

    foreach ($assessments as $assessment) {
        $vendor = $assessment->company_name;
        $categoryScores = $assessment->category_scores;

if (is_string($categoryScores)) {
    $categoryScores = json_decode($categoryScores, true);
}

if (!is_array($categoryScores)) {
    continue;
}


        foreach ($categoryScores as $catId => $catData) {
            if (!isset($categories[$catId])) continue;

            $score = $catData['score'] ?? null;
            $categoryName = $categories[$catId]->name;

            $matrix[$categoryName][$vendor] = $score;
            $scores[$categoryName][$vendor] = $score;
        }
    }

    return [
        'title' => 'Heatmap: Kategori × Vendor (Global TPSA)',
        'subtitle' => 'Skor per kategori TPSA untuk setiap vendor',
        'xAxis' => $vendors,
        'yAxis' => $categories->pluck('name')->values()->toArray(),
        'data' => $matrix,
        'scores' => $scores,
        'type' => 'category_vendor'
    ];
}

    /**
     * Convert score to compliance level
     */
    private function getComplianceLevel($score)
    {
        if ($score >= 80) return 'Sangat Memadai';
        if ($score >= 50) return 'Cukup Memadai';
        return 'Kurang Memadai';
    }
    
    /**
     * Get color based on score
     */
    private function getScoreColor($score)
    {
        if ($score >= 80) return '#4AD991'; // Hijau
        if ($score >= 50) return '#FEC53D'; // Kuning
        return '#FF6B6B'; // Merah
    }
    
    /**
     * Get color based on count (for heatmap 1 & 2)
     */
    private function getCountColor($count, $maxCount)
    {
        if ($maxCount == 0) return '#f0f0f0';
        
        $intensity = min(0.9, $count / max(1, $maxCount));
        $red = 255;
        $green = 255 - (int)(150 * $intensity);
        $blue = 255 - (int)(150 * $intensity);
        
        return "rgb($red, $green, $blue)";
    }
}