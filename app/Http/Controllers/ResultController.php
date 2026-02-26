<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Assessment;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month') ? $request->month : now()->month;
        $year  = $request->filled('year') ? $request->year : now()->year;

        $query = Assessment::orderBy('assessment_date', 'desc');

        if ($month !== 'all') {
            $query->whereMonth('assessment_date', $month);
        }

        if ($year !== 'all') {
            $query->whereYear('assessment_date', $year);
        }

        $assessments = $query->get();

        return view('result.index', [
    'vendorHeatmap'     => $this->vendorHeatmap($assessments),
    'vendorScoresChart' => $this->vendorScoresChart($assessments),
    'selectedMonth'     => $month,
    'selectedYear'      => $year,
    'selectedFilter'    => $filter ?? 'latest', // ⭐ default safety

    'availableYears' => Assessment::query()
        ->selectRaw('EXTRACT(YEAR FROM assessment_date) as year')
        ->distinct()
        ->orderByRaw('year DESC')
        ->pluck('year'),
]);
    }

    private function vendorHeatmap($assessments)
    {
        $categories = Category::where('id', '>=', 1)
    ->orderBy('id')
    ->get();

        $vendors = [];
        $matrix  = [];

        foreach ($assessments->pluck('company_name')->unique() as $vendor) {

            // Ambil assessment terbaru per vendor
            $vendorAssessment = $assessments
                ->where('company_name', $vendor)
                ->sortByDesc('assessment_date')
                ->first();

            if (!$vendorAssessment || empty($vendorAssessment->risk_level)) {
                continue;
            }

            $vendors[] = $vendor;

            $categoryScores = $vendorAssessment->category_scores ?? [];
            $totalScore     = $vendorAssessment->total_score ?? 0;

            $matrix[$vendor] = [
                'total' => [
                    'score' => $totalScore,
                    'color' => Assessment::getComplianceColor($totalScore),
                    'level' => Assessment::getComplianceLevel($totalScore),
                ],
                'categories' => []
            ];

            foreach ($categories as $category) {

                $categoryName = $category->category_level ?? $category->name;

                $matrix[$vendor]['categories'][$categoryName] = [
                    'score'     => $categoryScores[$category->id]['score'] ?? 0,
                    'indicator' => $categoryScores[$category->id]['indicator'] ?? null,
                ];
            }
        }

        $categoryNames = $categories
            ->map(fn ($c) => $c->category_level ?? $c->name)
            ->unique()
            ->values()
            ->toArray();

        return [
            'categories' => $categoryNames,
            'vendors'    => $vendors,
            'matrix'     => $matrix,
        ];
    }

    private function vendorScoresChart($assessments)
    {
        $chartData = [];

        foreach ($assessments as $assessment) {

            if (empty($assessment->risk_level)) {
                continue;
            }

            $chartData[] = [
                'company' => $assessment->company_name,
                'score'   => $assessment->total_score,
                'color'   => Assessment::getComplianceColor($assessment->total_score),
                'level'   => Assessment::getComplianceLevel($assessment->total_score),
            ];
        }

        // Urutkan dari score tertinggi
        usort($chartData, fn($a, $b) => $b['score'] <=> $a['score']);

        // Ambil maksimal 15 vendor
        $chartData = array_slice($chartData, 0, 15);

        return [
            'labels' => array_column($chartData, 'company'),
            'scores' => array_column($chartData, 'score'),
            'colors' => array_column($chartData, 'color'),
            'levels' => array_column($chartData, 'level'),
        ];
    }
}