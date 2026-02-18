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