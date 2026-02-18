<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Category;
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
    protected Assessment $assessment;

    protected array $categoryRowMap = [];
    protected array $questionsMap   = [];
    protected int $lastTableRow = 0;
    protected bool $hasScore = false;
    /**
 * Sort alphanumeric (1, 1a, 1b, 2, 3a, ...)
 */
private function alphanumericSort(string $a, string $b): int
{
    // Pisahkan angka dan huruf
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



    public function __construct(Assessment $assessment)
{
    $this->assessment = $assessment->load([
        'answers.question.category',
        'answers.question.options'
    ]);

    // ✅ SET DI SINI
    $this->hasScore = !empty($this->assessment->risk_level);
}


    /* =======================
     * Helpers
     * ======================= */
    private function emptyCols(int $count): array
    {
        return array_fill(0, $count, '');
    }


    private function lastMatrixColumnLetter(): string
{
    $colCount = 6; // A-F

    if ($this->hasScore) {
        $colCount += 1;
    }

    return Coordinate::stringFromColumnIndex($colCount);
}


    /* =======================
     * Collection (DATA MATRIX)
     * ======================= */
    public function collection()
{

    $data = [];
    $row  = 2;

    $grouped = $this->assessment->answers
        ->groupBy(fn ($a) => $a->question->category_id);

    $no = 1;

    foreach ($grouped as $categoryId => $answers) {
    $category  = $answers->first()->question->category;
    $indicator = $this->assessment->category_scores[$categoryId]['indicator'] ?? null;
    $categoryScore = $this->assessment->category_scores[$categoryId]['score'] ?? null;

    // ===== ROW JUDUL CATEGORY =====
    $rowData = [
        'Kategori: ' . $category->name . ' (Indikator: ' . strtoupper((string) $indicator) . ')',
        '', '', '', '', '' // B–F kosong karena A–F merge nanti
    ];

    // Kolom G → SCORE kategori
    if ($this->hasScore) {
        $rowData[] = $categoryScore ?? 0;
    }


    $data[] = $rowData;
    $this->categoryRowMap[$categoryId] = $row;
    $row++;

    // SORT jawaban
    $answers = $answers->sort(fn($a, $b) => $this->alphanumericSort($a->question->question_no, $b->question->question_no));

    // ===== ROW PERTANYAAN =====
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

        // Kolom G → SCORE jawaban
        if ($this->hasScore) {
            $rowData[] = $answer->score ?? 0;
        }

        $data[] = $rowData;
        $this->questionsMap[$row] = $question;
        $row++;
    }
}


    $this->lastTableRow = $row - 1;

    return collect($data);
}

    /* =======================
     * Headings
     * ======================= */
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

    if ($this->hasScore) {
        $headings[] = 'SCORE';
    }

    return $headings;
}



    /* =======================
     * Events
     * ======================= */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet      = $event->sheet->getDelegate();
                $lastRow    = $sheet->getHighestRow();
                $lastMatrix = $this->lastMatrixColumnLetter();

                /* =======================
                 * MERGE ROW CATEGORY
                 * ======================= */
                foreach ($this->categoryRowMap as $row) {
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFCAEDFB'],
                        ],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    if ($this->hasScore) {
                        $headings[] = 'SCORE';
                        $sheet->getStyle("G{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFCAEDFB'],
                            ],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);}
                }
                /* =======================
                 * DROPDOWN & FONT
                 * ======================= */
                for ($r = 2; $r <= $lastRow; $r++) {
                    $cell     = "E{$r}";
                    $question = $this->questionsMap[$r] ?? null;
                    if ($question) {
        Log::info("Question {$question->id} options:", $question->options->pluck('option_text')->toArray());
    }

                    // Style default kolom JAWABAN
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

                $sheet->freezePane('A2');

                /* =======================
                 * TTD & PERNYATAAN
                 * ======================= */
                $startRow = $this->lastTableRow + 2;
                $cellValue = $sheet->getCell("C{$startRow}")->getValue();
                $drawings  = $sheet->getDrawingCollection();

                $hasDrawing = false;
                foreach ($drawings as $drawing) {
                    if ($drawing->getCoordinates() === "C{$startRow}") {
                        $hasDrawing = true;
                        break;
                    }
                }

                if (empty($cellValue) && !$hasDrawing) {
                    $text = "Saya yang bertanda-tangan di bawah ini menyatakan bahwa data yang saya isi pada formulir ini adalah benar, dan Apabila di kemudian hari ditemukan kecurangan/pemalsuan/penipuan pada data, dokumen atau informasi tersebut, maka saya bersedia diberikan sanksi sesuai dengan perundangan/peraturan yang berlaku.";
                    $sheet->mergeCells("C{$startRow}:E{$startRow}");
                    $sheet->setCellValue("C{$startRow}", $text);

                    $sheet->getStyle("C{$startRow}:E{$startRow}")->applyFromArray([
                        'alignment' => [
                            'wrapText' => true,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        ],
                    ]);

                    $charPerLine = 90;
                    $lineCount  = ceil(strlen($text) / $charPerLine);
                    $rowHeight  = max(60, $lineCount * 18);
                    $sheet->getRowDimension($startRow)->setRowHeight($rowHeight);

                    $sheet->setCellValue("C" . ($startRow + 2), "<Lokasi, Tanggal>");
                    $sheet->setCellValue("C" . ($startRow + 3), "TTD, materai dan CAP perusahaan");
                    $sheet->setCellValue("C" . ($startRow + 7), "(…………………………………….)");
                    $sheet->setCellValue("C" . ($startRow + 8), "<Nama Perusahaan>");

                    $sheet->getStyle("C" . ($startRow + 2) . ":C" . ($startRow + 8))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
            }
        ];
    }

    /* =======================
     * Styles & Width
     * ======================= */
    public function styles(Worksheet $sheet)
{
    $lastRow = $this->lastTableRow;

    // 🔥 ambil kolom terakhir otomatis (F atau G)
    $lastCol = $this->lastMatrixColumnLetter();

    // HEADER
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF0070C0'],
        ],
        'alignment' => [
            'wrapText' => true,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);

    // DATA
    $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
        'alignment' => [
            'wrapText' => true,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);

    // Kalau ada score, center-kan kolom terakhir
    if ($this->hasScore) {
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
