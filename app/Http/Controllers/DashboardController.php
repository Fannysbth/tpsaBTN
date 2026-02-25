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
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard dengan filter tahun.
     */
    public function index(Request $request)
    {
        // Filter hanya tahun (nilai 'all' berarti semua tahun)
        $year = $request->filled('year') ? $request->year : 'all';

        // Query assessment berdasarkan tahun (jika bukan 'all')
        $query = Assessment::orderBy('assessment_date', 'desc')
    ->where('vendor_status', 'active');
        if ($year !== 'all') {
            $query->whereYear('assessment_date', $year);
        }
        $assessments = $query->get();
        $inactiveVendorThisYear = Assessment::where('vendor_status', 'inactive');

if ($year !== 'all') {
    $inactiveVendorThisYear->whereYear('assessment_date', $year);
}

$inactiveVendorThisYear = $inactiveVendorThisYear
    ->distinct('company_name')
    ->count('company_name');

    $inactiveVendorTotal = Assessment::where('vendor_status', 'inactive')
    ->distinct('company_name')
    ->count('company_name');

        // Ambil assessment TERAKHIR per vendor dalam periode terpilih
        $latestAssessments = $assessments
            ->groupBy('company_name')
            ->map(fn($group) => $group->sortByDesc('assessment_date')->first())
            ->filter(fn($a) => !is_null($a->company_name)); // buang vendor tanpa nama

        // ===================== SUMMARY CARD =====================
        // Risk level dengan label Indonesia
        $riskLabels = [
            'low'    => 'Sangat Memadai',
            'medium' => 'Memadai',
            'high'   => 'Kurang Memadai',
        ];
        $summaryRisk = [
            'Sangat Memadai' => 0,
            'Memadai'  => 0,
            'Kurang Memadai' => 0,
        ];
        foreach ($latestAssessments as $a) {
            $riskKey = $a->risk_level; // low, medium, high
            if ($riskKey && isset($riskLabels[$riskKey])) {
                $label = $riskLabels[$riskKey];
                $summaryRisk[$label]++;
            }
        }

        // ===================== PIE CHART (Assessed vs Not Assessed this year) =====================
        // Semua vendor yang pernah muncul (tanpa filter tahun)
        $allVendors = Assessment::distinct()
    ->whereNotNull('company_name')
    ->where('vendor_status', 'active')
    ->pluck('company_name');
        $assessedVendorsThisYear = $latestAssessments->pluck('company_name')->unique();
        $notAssessedVendors = $allVendors->diff($assessedVendorsThisYear);

        $pieComparison = [
            'labels' => ['Sudah Dinilai', 'Belum Dinilai'],
            'values' => [$assessedVendorsThisYear->count(), $notAssessedVendors->count()],
        ];

        // ===================== BAR CHART (Jumlah Vendor per Tier) =====================
        // Bar chart vertikal: sumbu Y = jumlah vendor, sumbu X = Tier 1,2,3
        $tierLabels = ['Tier 1', 'Tier 2', 'Tier 3'];
        $tierCounts = [];
        foreach ($tierLabels as $tier) {
            $tierCounts[] = $latestAssessments
                ->where('tier_criticality', $tier)
                ->pluck('company_name')
                ->unique()
                ->count();
        }
        $barTier = [
            'labels' => $tierLabels,
            'values' => $tierCounts,
        ];

        // ===================== AVERAGE SCORE =====================
        $scores = $latestAssessments->pluck('total_score')->filter();
        $averageScore = $scores->count() > 0 ? round($scores->avg(), 2) : 0;

        // ===================== HEATMAP (Risk vs Tier) =====================
        $heatmapRiskTier = $this->heatmapRiskTier($latestAssessments);

        // Total unique vendor untuk legend (dari heatmap)
        $totalUniqueVendor = collect($heatmapRiskTier)
            ->flatMap(fn($tiers) => collect($tiers)->flatMap(fn($cell) => $cell['vendors'] ?? []))
            ->unique()
            ->count();

        // ===================== DATA UNTUK VIEW =====================
        return view('dashboard.index', [
            'assessments'            => $assessments,
            'selectedYear'           => $year,
            'summaryRisk'            => $summaryRisk,
            'pieComparison'          => $pieComparison,
            'barTier'                => $barTier,
            'averageScore'           => $averageScore,
            'heatmapRiskTier'        => $heatmapRiskTier,
            'legendGradient'         => [
                'low'    => $this->scoreToGradientColor(25),
                'medium' => $this->scoreToGradientColor(75),
                'high'   => $this->scoreToGradientColor(95),
            ],
            'legendMax'              => $totalUniqueVendor,
            'inactiveVendorThisYear' => $inactiveVendorThisYear,
'inactiveVendorTotal' => $inactiveVendorTotal ?? 0,
            // Data lama (jika masih dipakai di view) kita kirim dengan nilai dari latestAssessments
            'totalWithRiskLevel'     => $latestAssessments->whereNotNull('risk_level')->count(),
            'totalWithoutRiskLevel'  => $latestAssessments->whereNull('risk_level')->count(),
            'totalAssessments' => $assessments->where('vendor_status', 'active')->count(),
            'vendorHeatmap'          => $this->vendorHeatmap($latestAssessments),
            'vendorScoresChart'      => $this->vendorScoresChart($latestAssessments),
            'pieTierChart'           => $this->tierPieChart($latestAssessments),
        ]);
    }

    /**
     * Membuat data heatmap risk vs tier.
     */
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
                $avgScore = $scores->count() > 0 ? $scores->avg() : 0;

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

    /**
     * Mengubah skor (0-100) menjadi warna gradien.
     */
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

    /**
     * Interpolasi antara dua warna.
     */
    private function interpolateColor($color1, $color2, $ratio)
    {
        $ratio = max(0, min(1, $ratio));

        $r = (int) ($color1[0] + ($color2[0] - $color1[0]) * $ratio);
        $g = (int) ($color1[1] + ($color2[1] - $color1[1]) * $ratio);
        $b = (int) ($color1[2] + ($color2[2] - $color1[2]) * $ratio);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Membuat data untuk heatmap vendor per kategori (fungsi lama, masih dipakai).
     */
    private function vendorHeatmap($assessments)
    {
        $categories = Category::orderBy('id')->get();

        $vendors = [];
        $matrix  = [];

        foreach ($assessments->pluck('company_name')->unique() as $vendor) {
            // ambil assessment terbaru per vendor (sebenarnya sudah latest, tapi amankan)
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
            ->map(fn($c) => $c->category_level ?? $c->name)
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

    /**
     * Membuat data chart vendor vs score (fungsi lama).
     */
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
        
        usort($chartData, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $chartData = array_slice($chartData, 0, 15);
        
        return [
            'labels' => array_column($chartData, 'company'),
            'scores' => array_column($chartData, 'score'),
            'colors' => array_column($chartData, 'color'),
            'levels' => array_column($chartData, 'level'),
        ];
    }

    /**
     * Membuat pie chart per tier (fungsi lama).
     */
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

    /**
     * Ekspor dashboard ke PowerPoint (fungsi tetap).
     */
    public function exportPpt(Request $request)
{
    $request->validate([
        'images' => 'required|array',
        'year'   => 'required|string',
    ]);

    // ── Palet warna ───────────────────────────────────────────────────
    $bgDark  = 'FF1A2544';
    $bgLight = 'FFF0F4FB';
    $purple  = 'FF8280FF';
    $white   = 'FFFFFFFF';
    $muted   = 'FF8A94A6';

    // ── Init PPT ──────────────────────────────────────────────────────
    $ppt = new PhpPresentation();
    $ppt->getDocumentProperties()
        ->setCreator('TPSA System')
        ->setTitle('Dashboard TPSA Security Questionnaire');
    $ppt->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
    $ppt->removeSlideByIndex(0);

    $slide = $ppt->createSlide();

    // Background
   
$background = new BackgroundColor();
$background->setColor(new Color($bgLight));
$slide->setBackground($background);

    // ─────────────────────────────────────────────────────────────────
    // HEADER
    // ─────────────────────────────────────────────────────────────────
    $hdr = $slide->createRichTextShape();
    $hdr->setOffsetX(0)->setOffsetY(0)->setWidth(960)->setHeight(54);
    $hdr->getFill()->setFillType(Fill::FILL_SOLID);
    $hdr->getFill()->setStartColor(new Color($bgDark));

    $strip = $slide->createRichTextShape();
    $strip->setOffsetX(0)->setOffsetY(52)->setWidth(960)->setHeight(4);
    $strip->getFill()->setFillType(Fill::FILL_SOLID);
    $strip->getFill()->setStartColor(new Color($purple));

    $titleSh = $slide->createRichTextShape();
    $titleSh->setOffsetX(16)->setOffsetY(10)->setWidth(620)->setHeight(24);
    $titleSh->getFill()->setFillType(Fill::FILL_NONE);
    $titleSh->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $tr = $titleSh->createTextRun('Dashboard TPSA Security Questionnaire');
    $tr->getFont()->setBold(true)->setSize(14)->setColor(new Color($white));

    $yearLabel = ($request->year === 'all') ? 'Semua Periode' : 'Periode Tahun ' . $request->year;
    $subSh = $slide->createRichTextShape();
    $subSh->setOffsetX(16)->setOffsetY(33)->setWidth(400)->setHeight(16);
    $subSh->getFill()->setFillType(Fill::FILL_NONE);
    $subSh->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $subSh->createTextRun($yearLabel)->getFont()->setSize(8)->setColor(new Color($muted));

    $tsSh = $slide->createRichTextShape();
    $tsSh->setOffsetX(680)->setOffsetY(19)->setWidth(268)->setHeight(16);
    $tsSh->getFill()->setFillType(Fill::FILL_NONE);
    $tsSh->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $tsSh->createTextRun('Generated: ' . now()->format('d M Y'))
         ->getFont()->setSize(8)->setColor(new Color($muted));

    // ─────────────────────────────────────────────────────────────────
    // DECODE IMAGES
    // ─────────────────────────────────────────────────────────────────
    $tmpFiles = [];
    $decode   = function ($b64) use (&$tmpFiles) {
        if (empty($b64)) return null;
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $b64));
        $path = storage_path('app/ppt_' . uniqid() . '.png');
        file_put_contents($path, $data);
        $tmpFiles[] = $path;
        return $path;
    };

    $imgs = $request->images;

    $cards = [
        'total'   => $decode($imgs['total']   ?? null),
        'avg'     => $decode($imgs['avg']     ?? null),
        'pie'     => $decode($imgs['pie']     ?? null),
        'bar'     => $decode($imgs['bar']     ?? null),
        'heatmap' => $decode($imgs['heatmap'] ?? null),
        'summary' => $decode($imgs['summary'] ?? null),  // ← BARU
    ];

    // ─────────────────────────────────────────────────────────────────
    // LAYOUT CONSTANTS
    // Slide: 960 × 540 px
    // Content: y=60 → y=524  (464 px tall)
    // ─────────────────────────────────────────────────────────────────
    $pad    = 10;
    $gap    = 8;
    $topY   = 60;
    $footer = 524;
    $footerY = 524;

    $totalW = 960 - 2 * $pad;   // 940
    $totalH = $footer - $topY;  // 464

    // Grid layout baru
$row1H = 130;
$row1Y = $topY;

$row2Y = $row1Y + $row1H + $gap;
$row2H = $footerY - $row2Y - $gap;

// Split area menjadi 2 kolom utama
$col1W = (int)($totalW * 0.55);   // area kiri (total, avg, summary, heatmap)
$col2W = $totalW - $col1W - $gap;

// Kolom 1 subgrid
$col1CardW = (int)(($col1W - $gap) / 2); // total & avg
$sumW = (int)($col1W * 0.35);
$hmW  = $col1W - $sumW - $gap;

// Kolom 2 subgrid
$col2CardW = $col2W; // pie dan bar full column

$layout = [

    // ─── Kolom 1 Row 1 ───
    'total' => [
        'x' => $pad,
        'y' => 62.496062992,
        'w' => 245.47244094,
        'h' => 109.03149606
    ],

    'avg' => [
        'x' => 280.95275591,
        'y' => 62.496062992,
        'w' => 245.47244094,
        'h' => 109.03149606
    ],

    // ─── Kolom 2 Row 1 ───
    'pie' => [
        'x' => 555.88188976,
        'y' =>65.496062992,
      'w' => 357.02362205,
        'h' => 210.08661417
    ],

    // ─── Row 2 ───
    'summary' => [
        'x' => $pad,
        'y' =>190.48818898,
        'w' => $sumW,
        'h' => $row2H,
        'transparent' => true
    ],

    'heatmap' => [
        'x' => 148.04724409,
        'y' => 190.48818898,
        'w' => $hmW,
        'h' => $row2H
    ],

    'bar' => [
        'x' => 555.88188976,
        'y' => 282.68503937,
        'w' => 377.02362205,
        'h' => 220.08661417
    ],
];

//     ─────────────────────────────────────────────────────────────────
//     RENDER CARDS
//     ─────────────────────────────────────────────────────────────────
    foreach ($layout as $key => $pos) {
        $transparent = $pos['transparent'] ?? false;

 

        // Image overlay
        if (!empty($cards[$key])) {
            $img = $slide->createDrawingShape();
            $img->setPath($cards[$key])
                ->setOffsetX($pos['x'])
                ->setOffsetY($pos['y'])
                ->setWidth($pos['w'])
                ->setHeight($pos['h']);
        } else {
            // Placeholder teks jika gambar tidak ada
            $ph = $slide->createRichTextShape();
            $ph->setOffsetX($pos['x'] + 4)
               ->setOffsetY($pos['y'] + 4)
               ->setWidth($pos['w'] - 8)
               ->setHeight(18);
            $ph->getFill()->setFillType(Fill::FILL_NONE);
            $ph->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ph->createTextRun(strtoupper($key))->getFont()->setSize(8)->setColor(new Color($muted));
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // FOOTER
    // ─────────────────────────────────────────────────────────────────
    $ftBg = $slide->createRichTextShape();
    $ftBg->setOffsetX(0)->setOffsetY(524)->setWidth(960)->setHeight(16);
    $ftBg->getFill()->setFillType(Fill::FILL_SOLID);
    $ftBg->getFill()->setStartColor(new Color($bgDark));
    $ftBg->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $ftBg->createTextRun('TPSA Security Questionnaire   ·  ' . now()->format('Y'))
         ->getFont()->setSize(7)->setColor(new Color($muted));

    // ─────────────────────────────────────────────────────────────────
    // SAVE & DOWNLOAD
    // ─────────────────────────────────────────────────────────────────
    $pptPath = storage_path('app/dashboard-report-' . uniqid() . '.pptx');
    IOFactory::createWriter($ppt, 'PowerPoint2007')->save($pptPath);

    foreach ($tmpFiles as $f) {
        if (file_exists($f)) unlink($f);
    }

    return response()->download(
        $pptPath,
        'Dashboard-TPSA-' . now()->format('Ymd') . '.pptx'
    )->deleteFileAfterSend(true);
}
}