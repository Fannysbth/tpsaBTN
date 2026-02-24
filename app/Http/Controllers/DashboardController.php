<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Assessment;
use Illuminate\Http\Request;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
use Carbon\Carbon;


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
$heatmapRisk = $this->heatmapRiskTier($assessments);

$totalUniqueVendor = collect($heatmapRisk)
    ->flatMap(function ($tiers) {
        return collect($tiers)->flatMap(function ($cell) {
            return $cell['vendors'] ?? [];
        });
    })
    ->unique()
    ->count();

return view('dashboard.index', [
    'assessments'            => $assessments,
    'totalWithRiskLevel'     => $withRiskLevel,
    'totalWithoutRiskLevel'  => $withoutRiskLevel,
    'totalAssessments'       => $assessments->count(),
    'vendorHeatmap'          => $this->vendorHeatmap($assessments),
    'vendorScoresChart'      => $this->vendorScoresChart($assessments),
    'selectedMonth'          => $month,
    'selectedYear'           => $year,
'heatmapRiskTier' => $heatmapRisk,
'legendGradient' => [
    'low' => $this->scoreToGradientColor(25),
    'medium' => $this->scoreToGradientColor(75),
    'high' => $this->scoreToGradientColor(95),
],
'legendMax' => $totalUniqueVendor,
'pieTierChart' => $this->tierPieChart($assessments),
]);

}

private function tierPieChart($assessments)
{
    $tiers = ['Tier 1', 'Tier 2', 'Tier 3'];

    $result = [];

    foreach ($tiers as $tier) {

        $vendors = $assessments
            ->where('tier_criticality', $tier)
            ->pluck('company_name')
            ->filter()
            ->unique();

        $result[] = [
            'tier' => $tier,
            'count' => $vendors->count()
        ];
    }

    return [
        'labels' => array_column($result, 'tier'),
        'values' => array_column($result, 'count'),
    ];
}

private function scoreToGradientColor($score)
{
    $low = 0;
    $medium = 50;
    $high = 100;

    if ($score <= $medium) {
        $ratio = $score / $medium;

        return $this->interpolateColor(
            [255, 107, 107],   // red
            [254, 197, 61],    // yellow
            $ratio
        );
    }

    $ratio = ($score - $medium) / ($high - $medium);

    return $this->interpolateColor(
        [254, 197, 61],    // yellow
        [74, 217, 145],    // green
        $ratio
    );
}
private function interpolateColor($color1, $color2, $ratio)
{
    $ratio = max(0, min(1, $ratio));

    $r = (int) ($color1[0] + ($color2[0] - $color1[0]) * $ratio);
    $g = (int) ($color1[1] + ($color2[1] - $color1[1]) * $ratio);
    $b = (int) ($color1[2] + ($color2[2] - $color1[2]) * $ratio);

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

public function exportPpt(Request $request)
{
    $request->validate([
        'images' => 'required|array|size:5',
        'month'  => 'required|string',
        'year'   => 'required|string',
    ]);

    $ppt = new PhpPresentation();

    // =========================
    // SLIDE SIZE 16:9 NORMAL
    // =========================
    $ppt->getLayout()->setDocumentLayout(
        DocumentLayout::LAYOUT_SCREEN_16X9
    );

    // remove default slide
    $ppt->removeSlideByIndex(0);
    $slide = $ppt->createSlide();

    /**
     * =========================
     * HEADER TITLE (TEXT ONLY)
     * =========================
     */
    $title = $slide->createRichTextShape()
        ->setOffsetX(40)
        ->setOffsetY(20)
        ->setWidth(860)
        ->setHeight(40);

    $title->getActiveParagraph()->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $titleRun = $title->createTextRun(
        'Dashboard TPSA Security Questionnaire'
    );

    $titleRun->getFont()
        ->setBold(true)
        ->setSize(22)
        ->setColor(new Color('FF000000')); // hitam

    /**
     * =========================
     * PERIOD TEXT
     * =========================
     */
    $period = $slide->createRichTextShape()
        ->setOffsetX(40)
        ->setOffsetY(65)
        ->setWidth(860)
        ->setHeight(30);

    // mapping month
$monthText = 'All Period';

if (is_numeric($request->month)) {
    $monthText = Carbon::create()
        ->month((int) $request->month)
        ->locale('id')
        ->translatedFormat('F');
}

$periodRun = $period->createTextRun(
    $monthText . ' ' . $request->year
);


    $periodRun->getFont()
        ->setSize(14)
        ->setColor(new Color('FF555555'));

    /**
     * =========================
     * CARD POSITIONS (PIXEL)
     * =========================
     */
    $positions = [
        // 3 summary cards (atas)
        ['x' => 40,  'y' => 100, 'w' => 260],
        ['x' => 330, 'y' => 100, 'w' => 260],
        ['x' => 620, 'y' => 100, 'w' => 260],

        // 2 chart cards (bawah)
        ['x' => 40,  'y' => 200, 'w' => 320],
        ['x' => 500, 'y' => 200, 'w' => 420],
    ];

    /**
     * =========================
     * INSERT CARD PNGs
     * =========================
     */
    $tempImages = [];

    foreach ($request->images as $i => $base64) {

        if (!isset($positions[$i])) {
            continue;
        }

        // decode base64 image
        $imageData = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $base64)
        );

        // save temp file
        $tmpPath = storage_path('app/card_' . uniqid() . '.png');
        file_put_contents($tmpPath, $imageData);
        $tempImages[] = $tmpPath;

        // insert image to slide
        $slide->createDrawingShape()
            ->setPath($tmpPath)
            ->setOffsetX($positions[$i]['x'])
            ->setOffsetY($positions[$i]['y'])
            ->setWidth($positions[$i]['w'])
            ->setResizeProportional(true);
    }

    /**
     * =========================
     * SAVE & DOWNLOAD
     * =========================
     */
    $pptPath = storage_path('app/dashboard-report.pptx');

    IOFactory::createWriter($ppt, 'PowerPoint2007')
        ->save($pptPath);

    // cleanup temp images
    foreach ($tempImages as $img) {
        if (file_exists($img)) {
            unlink($img);
        }
    }

    return response()
        ->download($pptPath)
        ->deleteFileAfterSend(true);
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
        'title'      => 'Assessment Heatmap',
        'categories' => $categoryNames,
        'vendors'    => $vendors,
        'matrix'     => $matrix,
    ];
}

private function heatmapRiskTier($assessments)
{
    $tiers = ['Tier 1', 'Tier 2', 'Tier 3'];

    $riskLevels = [
        'high'   => 'Kurang Memadai',
        'medium' => 'Cukup Memadai',
        'low'    => 'Sangat Memadai'
    ];

    $heatmap = [];

    foreach ($riskLevels as $riskKey => $riskLabel) {

        foreach ($tiers as $tier) {

            // Filter assessment sesuai cell heatmap
            $cellAssessments = $assessments->filter(function($a) use ($riskKey, $tier) {

                return $a->risk_level === $riskKey &&
                       $a->tier_criticality === $tier &&
                       !empty($a->company_name);
            });

            $vendors = $cellAssessments
                ->pluck('company_name')
                ->unique()
                ->values()
                ->toArray();

            // Ambil rata-rata score untuk gradient
            $scores = $cellAssessments->pluck('total_score')->filter();

            $avgScore = $scores->count() > 0
                ? $scores->avg()
                : 0;

            $heatmap[$riskLabel][$tier] = [
                'count'   => count($vendors),
                'vendors' => $vendors,
                'score'   => round($avgScore, 2),
                'color'   => $this->scoreToGradientColor($avgScore)
            ];
        }
    }

    return $heatmap;
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