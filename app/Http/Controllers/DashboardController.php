<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Assessment;
use Illuminate\Http\Request;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;
use Illuminate\Support\Str;

class DashboardController extends Controller
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

    $withRiskLevel = $assessments->whereNotNull('risk_level')->count();
$withoutRiskLevel = $assessments->whereNull('risk_level')->count();

return view('dashboard.index', [
    'assessments'            => $assessments,
    'totalWithRiskLevel'     => $withRiskLevel,
    'totalWithoutRiskLevel'  => $withoutRiskLevel,
    'totalAssessments'       => $assessments->count(),
    'vendorHeatmap'          => $this->vendorHeatmap($assessments),
    'vendorScoresChart'      => $this->vendorScoresChart($assessments),
    'selectedMonth'          => $month,
    'selectedYear'           => $year,
]);

}


public function exportPpt(Request $request)
{
    $ppt = new PhpPresentation();

    foreach ($request->images as $image) {

        $slide = $ppt->createSlide();

        $imageData = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $image)
        );

        $file = storage_path('app/tmp_' . Str::random(8) . '.png');
        file_put_contents($file, $imageData);

        $slide->createDrawingShape()
            ->setName('Dashboard Card')
            ->setPath($file)
            ->setWidth(900)
            ->setHeight(500)
            ->setOffsetX(30)
            ->setOffsetY(30);

        unlink($file);
    }

    $fileName = 'dashboard-report.pptx';
    $path = storage_path("app/$fileName");

    IOFactory::createWriter($ppt, 'PowerPoint2007')->save($path);

    return response()->download($path)->deleteFileAfterSend(true);
}
    private function vendorHeatmap($assessments)
{
    $categories = Category::orderBy('id')->get();

    $vendors = [];
    $matrix  = [];

    foreach ($assessments->pluck('company_name')->unique() as $vendor) {

        // ambil assessment terbaru per vendor
        $vendorAssessment = $assessments
            ->where('company_name', $vendor)
            ->sortByDesc('assessment_date')
            ->first();

        // ❌ skip kalau belum ada risk level
        if (!$vendorAssessment || empty($vendorAssessment->risk_level)) {
            continue;
        }

        // ✅ vendor valid
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

            if (isset($categoryScores[$category->id])) {
                $matrix[$vendor]['categories'][$categoryName] = [
                    'score'     => $categoryScores[$category->id]['score'] ?? 0,
                    'indicator' => $categoryScores[$category->id]['indicator'] ?? null,
                ];
            } else {
                $matrix[$vendor]['categories'][$categoryName] = [
                    'score'     => 0,
                    'indicator' => null,
                ];
            }
        }
    }

    $categoryNames = $categories
        ->map(fn ($c) => $c->category_level ?? $c->name)
        ->unique()
        ->values()
        ->toArray();

    return [
        'title'      => 'Vendor Assessment Heatmap',
        'categories' => $categoryNames,
        'vendors'    => $vendors,
        'matrix'     => $matrix,
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

    if (empty($assessment->risk_level)) {
        continue; // skip kalau belum ada risk level
    }

    $chartData[] = [
        'company' => $assessment->company_name,
        'score'   => $assessment->total_score,
        'color'   => Assessment::getComplianceColor($assessment->total_score),
        'level'   => Assessment::getComplianceLevel($assessment->total_score),
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