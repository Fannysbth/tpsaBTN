<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Answer;
use App\Models\AssessmentHistory;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AssessmentExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected mixed $assessment;
    protected array $categoryRowMap = [];
    protected array $questionsMap   = [];
    protected int $lastTableRow = 0;
    protected string $exportMode;
    protected ?int $totalScoreRow = null; 
    protected array $categoryQuestionRange = [];

    /**
     * Sort alphanumeric (1, 1a, 1b, 2, 3a, ...)
     */
    private function alphanumericSort(string $a, string $b): int
    {
        preg_match('/^(\d+)([a-z]*)$/i', $a, $matchA);
        preg_match('/^(\d+)([a-z]*)$/i', $b, $matchB);

        $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
        $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;

        if ($numA !== $numB) {
            return $numA <=> $numB;
        }

        $strA = isset($matchA[2]) ? $matchA[2] : '';
        $strB = isset($matchB[2]) ? $matchB[2] : '';

        return strcmp($strA, $strB);
    }

    public function __construct($history, string $mode = 'list')
    {
        $this->exportMode = $mode;

        if ($history instanceof AssessmentHistory) {
            $snapshotRaw = $mode === 'result' ? $history->new_value : $history->old_value;
            $snapshot = is_string($snapshotRaw) ? json_decode($snapshotRaw, true) : (is_array($snapshotRaw) ? $snapshotRaw : []);

            if ($mode === 'list') {
    // Mode list: hanya gunakan category_scores, abaikan answers
    $snapshotRaw = $history->old_value;
    if (empty($snapshotRaw)) {
        // fallback ke new_value jika old_value kosong
        $snapshotRaw = $history->new_value;
    }
    $snapshot = is_string($snapshotRaw) ? json_decode($snapshotRaw, true) : (is_array($snapshotRaw) ? $snapshotRaw : []);

    $categoryScores = $snapshot['category_scores'] ?? [];

// Paksa key jadi integer
$categoryScores = collect($categoryScores)
    ->mapWithKeys(fn ($v, $k) => [(int) $k => $v]);

// 🔥 Tambahkan category 0 kalau belum ada
if (!$categoryScores->has(0)) {
    $categoryScores->put(0, [
        'score' => 0,
        'indicator' => null,
        'assessor' => null,
        'justification' => null,
        'actual_score' => 0,
        'max_score' => 0,
    ]);
}

// Sort berdasarkan ID
$categoryScores = $categoryScores
    ->sortKeys()
    ->toArray();


$this->assessment = (object) [
    'category_scores' => $categoryScores,
    'risk_level'      => null,
];
} else {
                // Mode result: ambil answer_ids dan muat answers dari database
                $answerIds = $snapshot['answer_ids'] ?? [];
                $answers = collect();
                if (!empty($answerIds)) {
                    $answers = Answer::with(['question.category', 'question.options'])
                        ->whereIn('id', $answerIds)
                        ->get();
                }
$categoryScores = $snapshot['category_scores'] ?? [];

// Paksa key jadi integer
$categoryScores = collect($categoryScores)
    ->mapWithKeys(fn ($v, $k) => [(int) $k => $v]);

// 🔥 Tambahkan category 0 kalau belum ada
if (!$categoryScores->has(0)) {
    $categoryScores->put(0, [
        'score' => 0,
        'indicator' => null,
        'assessor' => null,
        'justification' => null,
        'actual_score' => 0,
        'max_score' => 0,
    ]);
}

// Sort berdasarkan ID
$categoryScores = $categoryScores
    ->sortKeys()
    ->toArray();


$this->assessment = (object) [
    'answers'         => $answers,
    'category_scores' => $categoryScores,
    'risk_level'      => $snapshot['risk_level'] ?? null,
];
            }
        } else {
            // $history adalah model Assessment
            if ($mode === 'list') {
                $this->assessment = (object) [
                    'category_scores' => $history->category_scores ?? [],
                    'risk_level'      => null,
                ];
            } else {
                $this->assessment = $history->load(['answers.question.category', 'answers.question.options']);
            }
        }
    }

    private function showScore(): bool
    {
        return $this->exportMode === 'result'
            && !empty($this->assessment->risk_level);
    }

    private function lastMatrixColumnLetter(): string
    {
        $colCount = 6; // A–F

        if ($this->showScore()) {
            $colCount += 1; // + kolom G
        }

        return Coordinate::stringFromColumnIndex($colCount);
    }

    public function collection()
    {
        $data = [];
        $row  = 2;

        $hasAnswers = isset($this->assessment->answers) && $this->assessment->answers->isNotEmpty();

        if ($hasAnswers) {

    $grouped = $this->assessment->answers
        ->groupBy(fn($a) => $a->question->category_id);

    // Pastikan semua kategori di category_scores tetap ada
    foreach ($this->assessment->category_scores as $categoryId => $catData) {

        if (!$grouped->has($categoryId)) {

            $questions = \App\Models\Question::with('options')
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('order_index')
                ->get();

            $dummyAnswers = $questions->map(function ($q) {
                $answer = new \stdClass();
                $answer->question    = $q;
                $answer->answer_text = null;
                $answer->score       = 0;
                return $answer;
            });

            $grouped->put($categoryId, $dummyAnswers);
        }
    }
    
} else {
            // Mode list: bangun dummy answers berdasarkan category_scores
            $grouped = collect();
            $categoryScores = $this->assessment->category_scores ?? [];
            if (!isset($categoryScores[0])) {
                $categoryScores = array_merge(
                    [0 => ['indicator' => null]],
                    $categoryScores
                );
            }

            foreach ($categoryScores as $categoryId => $catData) {
                $indicator = $catData['indicator'] ?? "null";

                if ($categoryId != 0 && empty($indicator)) {
                    continue;
                }

                $questions = \App\Models\Question::with('options')
                    ->where('category_id', $categoryId)
                    ->where('is_active', true)
                    ->when($categoryId != 0, function ($q) use ($indicator) {
                        $q->where(function ($query) use ($indicator) {
                            if ($indicator) {
                                $query->whereJsonContains('indicator', $indicator)
                                      ->orWhere('indicator', 'LIKE', "%{$indicator}%");
                            }
                        });
                    })
                    ->orderBy('order_index')
                    ->get();

                $dummyAnswers = $questions->map(function ($q) {
                    $answer = new \stdClass();
                    $answer->question    = $q;
                    $answer->answer_text = null;
                    $answer->score       = 0;
                    return $answer;
                });

                $grouped->put($categoryId, $dummyAnswers);
            }
        }

        $totalCategoryScoreSum = 0;
        $categoryCount = 0;
foreach ($this->assessment->category_scores as $categoryId => $catData) {

    $answers = $grouped->get($categoryId, collect());

    if ($answers->isEmpty() && $categoryId != 0) {
        continue;
    }

            $category = $answers->first()->question->category;
           $indicator = $catData['indicator'] ?? 'umum';

            // Hitung skor kategori dari answers
            $totalScore = 0;
            $totalMax = 0;
            foreach ($answers as $ans) {
                $totalScore += $ans->score ?? 0;
                // Hitung max score per question
                $maxScore = 0;
                if ($ans->question->options && $ans->question->options->isNotEmpty()) {
                    $maxScore = $ans->question->options->max('score') ?? 0;
                } elseif (isset($ans->question->max_score)) {
                    $maxScore = $ans->question->max_score;
                }
                $totalMax += $maxScore;
            }
            $categoryScore = ($totalMax > 0) ? round(($totalScore / $totalMax) * 100, 2) : 0;

            $totalCategoryScoreSum += $categoryScore;
            $categoryCount++;

            // Baris kategori
            $rowData = [
                'Kategori: ' . $category->name . ' (Indikator: ' . strtoupper((string) $indicator) . ')',
                '', '', '', '', ''
            ];
            if ($this->showScore()) {
                $rowData[] = $categoryScore;
            }
            $data[] = $rowData;
            $this->categoryRowMap[$categoryId] = $row;
            $row++;
            $startQuestionRow = $row;

            // Urutkan jawaban
            $answers = $answers->sort(fn($a, $b) => $this->alphanumericSort($a->question->question_no, $b->question->question_no));

            foreach ($answers as $answer) {
                $question = $answer->question;

                $rowData = [
                    $question->sub ?? '-',
                    $question->question_no,
                    $question->question_text,
                    ':',
                    $answer->answer_text ?? ($question->clue ?? '-- Pilih Jawaban --'),
                    $question->has_attachment ? ($question->attachment_text ?: '-') : '-',
                ];
                if ($this->showScore()) {
                    $rowData[] = $answer->score ?? 0;
                }

                $data[] = $rowData;
                $this->questionsMap[$row] = $question;
                $row++;
                
            }
            $endQuestionRow = $row - 1;

$this->categoryQuestionRange[$categoryId] = [
    'start' => $startQuestionRow,
    'end'   => $endQuestionRow,
    'max'   => $totalMax
];
        }

        // Baris total skor (hanya untuk mode result)
        if ($this->showScore() && $categoryCount > 0) {
            $rowData = array_fill(0, 6, '');
            $rowData[0] = 'TOTAL SCORE';
            $rowData[] = 0; // nanti diganti rumus Excel
            $data[] = $rowData;
            $this->totalScoreRow = $row; // simpan untuk styling
            $row++;
        }

        $this->lastTableRow = $row - 1;

        return collect($data);
    }

    public function headings(): array
    {
        $headings = [
            'SUB KATEGORI',
            'NO',
            'PERTANYAAN',
            ':',
            'JAWABAN',
            'ATTACHMENT',
        ];

        if ($this->showScore()) {
            $headings[] = 'SCORE';
        }

        return $headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastRow    = $sheet->getHighestRow();
                $lastMatrix = $this->lastMatrixColumnLetter();
                

                // Merge dan style baris kategori
                foreach ($this->categoryRowMap as $row) {
                    
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}:{$lastMatrix}{$row}")->applyFromArray([
                        'font'    => ['bold' => true],
                        'fill'    => [
                            'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFCAEDFB'],
                        ],  
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);

                    if ($this->showScore()) {
    foreach ($this->categoryQuestionRange as $categoryId => $range) {

        $categoryRow = $this->categoryRowMap[$categoryId] ?? null;
        if (!$categoryRow) continue;

        $start = $range['start'];
        $end   = $range['end'];
        $max   = $range['max'];

        if ($max > 0) {
            $formula = "=IFERROR(ROUND(SUM(G{$start}:G{$end})/{$max}*100,2),0)";
            $sheet->setCellValue("G{$categoryRow}", $formula);
        }
    }
}
                }
if ($this->totalScoreRow) {

    $categoryRows = array_values($this->categoryRowMap);

    // ❌ Buang row 2 (G2) dari average
    $categoryRows = array_filter($categoryRows, fn($r) => $r != 2);

    if (!empty($categoryRows)) {
        $rowsString = implode(',', array_map(fn($r) => "G{$r}", $categoryRows));
        $formula = "=IFERROR(ROUND(AVERAGE({$rowsString}),2),0)";
        $sheet->setCellValue("G{$this->totalScoreRow}", $formula);
    }
}

                // Dropdown untuk kolom JAWABAN (E)
                for ($r = 2; $r <= $lastRow; $r++) {
                    $cell     = "E{$r}";
                    $question = $this->questionsMap[$r] ?? null;

                    $sheet->getStyle($cell)->getFont()
                        ->setItalic(true)
                        ->getColor()->setARGB('FF808080');

                    if ($question && $question->question_type === 'pilihan') {
                        $options = $question->options->pluck('option_text')->toArray();
                        array_unshift($options, '-- Pilih Jawaban --');

                        $validation = $sheet->getCell($cell)->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('"' . implode(',', $options) . '"');
                    }
                }

                // Style baris total score jika ada
                if ($this->totalScoreRow) {
                    $totalRow = $this->totalScoreRow;
                    $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
                    $sheet->getStyle("A{$totalRow}:{$lastMatrix}{$totalRow}")->applyFromArray([
                        'font'    => ['bold' => true],
                        'fill'    => [
                            'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF0F0F0'],
                        ],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                }

                $sheet->freezePane('A2');

                // // Bagian tanda tangan (sama seperti kode lama)
                // $startRow = $this->lastTableRow + 2;
                // $cellValue = $sheet->getCell("C{$startRow}")->getValue();
                // $drawings  = $sheet->getDrawingCollection();

                // $hasDrawing = false;
                // foreach ($drawings as $drawing) {
                //     if ($drawing->getCoordinates() === "C{$startRow}") {
                //         $hasDrawing = true;
                //         break;
                //     }
                // }

                // if (empty($cellValue) && !$hasDrawing) {
                //     $text = "Saya yang bertanda-tangan di bawah ini menyatakan bahwa data yang saya isi pada formulir ini adalah benar, dan Apabila di kemudian hari ditemukan kecurangan/pemalsuan/penipuan pada data, dokumen atau informasi tersebut, maka saya bersedia diberikan sanksi sesuai dengan perundangan/peraturan yang berlaku.";
                //     $sheet->mergeCells("C{$startRow}:E{$startRow}");
                //     $sheet->setCellValue("C{$startRow}", $text);

                //     $sheet->getStyle("C{$startRow}:E{$startRow}")->applyFromArray([
                //         'alignment' => [
                //             'wrapText'   => true,
                //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                //         ],
                //     ]);

                //     $charPerLine = 90;
                //     $lineCount   = ceil(strlen($text) / $charPerLine);
                //     $rowHeight   = max(60, $lineCount * 18);
                //     $sheet->getRowDimension($startRow)->setRowHeight($rowHeight);

                //     $sheet->setCellValue("C" . ($startRow + 2), "<Lokasi, Tanggal>");
                //     $sheet->setCellValue("C" . ($startRow + 3), "TTD, materai dan CAP perusahaan");
                //     $sheet->setCellValue("C" . ($startRow + 7), "(…………………………………….)");
                //     $sheet->setCellValue("C" . ($startRow + 8), "<Nama Perusahaan>");

                //     $sheet->getStyle("C" . ($startRow + 2) . ":C" . ($startRow + 8))->applyFromArray([
                //         'alignment' => [
                //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                //         ],
                //     ]);
                // }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->lastTableRow;
        $lastCol = $this->lastMatrixColumnLetter();

        // Header
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'],
            ],
            'alignment' => [
                'wrapText'   => true,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Data
        $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
            'alignment' => [
                'wrapText' => true,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        if ($this->showScore()) {
            $sheet->getStyle("{$lastCol}2:{$lastCol}{$lastRow}")
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 5,
            'C' => 45,
            'D' => 2,
            'E' => 40,
            'F' => 30,
        ];
    }
}