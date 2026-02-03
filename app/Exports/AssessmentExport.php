<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
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
    protected int $categoryColumnCount = 0;
    protected int $lastTableRow = 0;


    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment->load([
            'answers.question.category',
            'answers.question.options'
        ]);
    }

    /* =======================
     * Helpers
     * ======================= */

    private function emptyCols(int $count): array
    {
        return array_fill(0, $count, '');
    }

    // kolom matrix = 2 kolom awal + (category * 3)
    private function matrixColCount(): int
    {
        return 6 + $this->categoryColumnCount;
    }

    private function lastMatrixColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex($this->matrixColCount());
    }

    /* =======================
     * Collection (DATA MATRIX)
     * ======================= */

    public function collection()
    {
        // 1 category = 3 kolom (High, Medium, Low)
        $this->categoryColumnCount = Category::count() * 3;

        $data = [];
        $row  = 2;

        $grouped = $this->assessment->answers
            ->groupBy(fn ($a) => $a->question->category_id);

        $no = 1;
    

        foreach ($grouped as $categoryId => $answers) {
            $category  = $answers->first()->question->category;
            $indicator = $this->assessment->category_scores[$categoryId]['indicator'] ?? null;

            // ===== ROW JUDUL CATEGORY =====
            $data[] = array_merge(
    ['Kategori: ' . $category->name . ' (Indikator: ' . strtoupper((string) $indicator) . ')'],
    $this->emptyCols($this->matrixColCount() - 1)
);



            $this->categoryRowMap[$categoryId] = $row;
            $row++;

            // ===== ROW PERTANYAAN =====
            foreach ($answers as $answer) {
                $question = $answer->question;

                $data[] = array_merge(
    [
        $question->sub ?? '-',              // A: Sub Kategori
        $no++,                               // B: Nomor urut global
        $question->question_text,            // C: Pertanyaan
        ':',                                 // D
        $answer->answer_text
            ?? ($question->clue ?? '-- Pilih Jawaban --'), // E
        $question->has_attachment
            ? ($question->attachment_text ?: '-')
            : '-',                           // F
    ],
    $this->emptyCols($this->categoryColumnCount)
);



                $this->questionsMap[$row] = $question;
                $row++;
            }
        }
$this->lastTableRow = $row - 1;

        return collect($data);
    }

    /* =======================
     * Headings (HANYA MATRIX)
     * ======================= */

   public function headings(): array
{
    return array_merge(
        ['SUB KATEGORI', 'NO', 'PERTANYAAN', ':', 'JAWABAN', 'ATTACHMENT'],
        $this->emptyCols($this->categoryColumnCount)
    );
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
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFCAEDFB'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);
}



                /* =======================
                 * DROPDOWN & FONT
                 * ======================= */

                for ($r = 2; $r <= $lastRow; $r++) {
    $cell     = "E{$r}";
    $question = $this->questionsMap[$r] ?? null;

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
                $startRow = $this->lastTableRow + 2;

$sheet->mergeCells("C{$startRow}:E{$startRow}");

$text = "Saya yang bertanda-tangan di bawah ini menyatakan bahwa data yang saya isi pada formulir ini adalah benar, dan Apabila di kemudian hari ditemukan kecurangan/pemalsuan/penipuan pada data, dokumen atau informasi tersebut, maka saya bersedia diberikan sanksi sesuai dengan perundangan/peraturan yang berlaku.";

$sheet->setCellValue("C{$startRow}", $text);

// wrap & alignment
$sheet->getStyle("C{$startRow}:E{$startRow}")->applyFromArray([
    'alignment' => [
        'wrapText' => true,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
    ],
]);

// 🔥 HITUNG TINGGI BARIS (KUNCI)
$charPerLine = 90; // sesuaikan dengan lebar C:E
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
        ];
    }

    /* =======================
     * Styles & Width
     * ======================= */

    public function styles(Worksheet $sheet)
{
    $lastRow = $this->lastTableRow;

    // HEADER
    $sheet->getStyle("A1:F1")->applyFromArray([
        'font' => [
            'bold'  => true,
            'color' => ['argb' => 'FFFFFFFF'],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF0070C0'],
        ],
        'alignment' => [
            'wrapText' => true,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    // DATA
    $sheet->getStyle("A2:F{$lastRow}")->applyFromArray([
        'alignment' => [
            'wrapText' => true,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);
}






    public function columnWidths(): array
{
    return [
        'A' => 20, // Sub kategori
        'B' => 5,  // No
        'C' => 45, // Pertanyaan
        'D' => 2,  // :
        'E' => 40, // Jawaban
        'F' => 30, // Attachment
    ];
}

}
