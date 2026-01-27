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
        return 2 + $this->categoryColumnCount;
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
                        $question->question_text,
                        $answer->answer_text ?? ($question->clue ?? '-- Pilih Jawaban --'),
                    ],
                    $this->emptyCols($this->categoryColumnCount)
                );

                $this->questionsMap[$row] = $question;
                $row++;
            }

            // spacer
            $data[] = $this->emptyCols($this->matrixColCount());
            $row++;
        }

        return collect($data);
    }

    /* =======================
     * Headings (HANYA MATRIX)
     * ======================= */

    public function headings(): array
    {
        return array_merge(
            ['PERTANYAAN', 'JAWABAN'],
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
                 * ATTACHMENT (KOLOM TERAKHIR)
                 * ======================= */

                $attachmentColIndex = Coordinate::columnIndexFromString($lastMatrix) + 1;
                $attachmentCol      = Coordinate::stringFromColumnIndex($attachmentColIndex);

                // header attachment
                $sheet->setCellValue("{$attachmentCol}1", 'ATTACHMENT');

                foreach ($this->questionsMap as $row => $question) {
                    $answer = $this->assessment->answers
                        ->firstWhere('question_id', $question->id);

                    $sheet->setCellValue(
                        "{$attachmentCol}{$row}",
                        $question->has_attachment
                            ? ($answer->attachment_info ?? '-')
                            : '-'
                    );
                }

                /* =======================
                 * MERGE ROW CATEGORY
                 * ======================= */

                foreach ($this->categoryRowMap as $row) {
                    $sheet->mergeCells("A{$row}:{$lastMatrix}{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                }

                /* =======================
                 * DROPDOWN & FONT
                 * ======================= */

                for ($r = 2; $r <= $lastRow; $r++) {
                    $cell     = "B{$r}";
                    $question = $this->questionsMap[$r] ?? null;

                    if (!$question || $sheet->getCell($cell)->getValue() === '') {
                        $sheet->getStyle($cell)
                            ->getFont()
                            ->getColor()
                            ->setARGB('FF999999');
                    } else {
                        $sheet->getStyle($cell)
                            ->getFont()
                            ->getColor()
                            ->setARGB(Color::COLOR_BLACK);
                    }

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
            }
        ];
    }

    /* =======================
     * Styles & Width
     * ======================= */

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastMatrix = $this->lastMatrixColumnLetter();

        $sheet->getStyle("A1:{$lastMatrix}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastMatrix}{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 40,
        ];
    }
}
