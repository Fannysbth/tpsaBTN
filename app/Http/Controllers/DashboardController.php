<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Assessment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $query = Assessment::orderBy('assessment_date', 'desc');

        if ($month) $query->whereMonth('assessment_date', $month);
        if ($year) $query->whereYear('assessment_date', $year);

        $assessments = $query->get();
        
        // Data untuk card summary
        $totalCategories = Category::count();
        $totalQuestions = Question::count();
        $totalAssessmentsCount = Assessment::count();

        return view('dashboard.index', [
            'assessments' => $assessments,
            'totalCategories' => $totalCategories,
            'totalQuestions' => $totalQuestions,
            'totalAssessments' => $totalAssessmentsCount,
            'vendorHeatmap' => $this->vendorHeatmap($assessments),
            'vendorScoresChart' => $this->vendorScoresChart($assessments),
        ]);
    }

    private function vendorHeatmap($assessments)
{
    // Ambil semua kategori
    $categories = Category::orderBy('id')->get();

    // Ambil semua vendor unik
    $vendors = $assessments->pluck('company_name')->unique()->values();

    $matrix = [];

    foreach ($vendors as $vendor) {
        // Ambil assessment terbaru untuk vendor ini
        $vendorAssessment = $assessments->where('company_name', $vendor)->first();

        if (!$vendorAssessment) continue;

        $categoryScores = $vendorAssessment->category_scores ?? [];
        $totalScore = $vendorAssessment->total_score ?? 0;

        $matrix[$vendor] = [
            'total' => [
                'score' => $totalScore,
                'color' => Assessment::getComplianceColor($totalScore), // total masih bisa pakai score
                'level' => Assessment::getComplianceLevel($totalScore),
            ],
            'categories' => []
        ];

        foreach ($categories as $category) {
            $categoryName = $category->category_level ?? $category->name;

            if (isset($categoryScores[$category->id])) {
                $indicator = $categoryScores[$category->id]['indicator'] ?? null;

                // Gunakan warna berdasarkan indikator, bukan score
                $color = $this->getColorByIndicator($indicator);

                $matrix[$vendor]['categories'][$categoryName] = [
    'score' => $categoryScores[$category->id]['score'] ?? 0,
    'indicator' => $indicator,
];

            } else {
                $matrix[$vendor]['categories'][$categoryName] = [
                    'score' => 0,
                    'indicator' => null,
                    'color' => '#f8f9fa', // default no data
                ];
            }
        }
    }

    $categoryNames = $categories->map(function($category) {
        return $category->category_level ?? $category->name;
    })->unique()->values()->toArray();

    return [
        'title' => 'Vendor Assessment Heatmap',
        'categories' => $categoryNames,
        'vendors' => $vendors->toArray(),
        'matrix' => $matrix,
    ];
}

/**
 * Contoh fungsi untuk mapping indikator ke warna.
 * Sesuaikan dengan kebutuhan indikator perusahaan.
 */
private function getColorByIndicator($indicator)
{
    $indicator = strtolower((string) $indicator);

    return match ($indicator) {
        'high'   => '#4AD991', // hijau
        'medium' => '#FEC53D', // kuning
        'low'    => '#FF6B6B', // merah
        default  => '#f8f9fa', // no data
    };
}




    private function vendorScoresChart($assessments)
    {
        // Ambil data untuk chart
        $chartData = [];
        
        foreach ($assessments as $assessment) {
            $chartData[] = [
                'company' => $assessment->company_name,
                'score' => $assessment->total_score,
                'color' => Assessment::getComplianceColor($assessment->total_score),
                'level' => Assessment::getComplianceLevel($assessment->total_score),
            ];
        }
        
        // Urutkan berdasarkan score (tertinggi ke terendah)
        usort($chartData, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Batasi jumlah data yang ditampilkan untuk kejelasan
        $chartData = array_slice($chartData, 0, 15); // Maksimal 15 vendor
        
        return [
            'labels' => array_column($chartData, 'company'),
            'scores' => array_column($chartData, 'score'),
            'colors' => array_column($chartData, 'color'),
            'levels' => array_column($chartData, 'level'),
        ];
    }
}