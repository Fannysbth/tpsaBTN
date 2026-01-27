<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QuestionnaireExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected array $indicatorGroups = [];
    protected int $indicatorColumnCount = 0;

    protected function lastIndicatorColumn(): string
    {
        return Coordinate::stringFromColumnIndex(5 + $this->indicatorColumnCount);
    }

    public function __construct()
    {
        $categories = Category::all();

        $this->indicatorGroups[] = [
            'key' => 'umum',
            'name' => 'Umum',
            'levels' => ['Umum'],
        ];

        foreach ($categories as $category) {
            if ($category->name === 'Informasi Umum') continue;

            $levels = array_map('ucfirst', array_keys($category->criteria));

            $this->indicatorGroups[] = [
                'key' => $category->id,
                'name' => $category->name,
                'levels' => $levels,
            ];
        }

        foreach ($this->indicatorGroups as $group) {
            $this->indicatorColumnCount += count($group['levels']);
        }
    }

    public function collection()
    {
        $rows = collect();
        $categories = Category::with('questions.options')->get();

        foreach ($categories as $category) {

            // ===== HEADER CATEGORY =====
            $rows->push(array_merge(
                [$category->name, 'HEADER', '', '', ''],
                array_fill(0, $this->indicatorColumnCount, '')
            ));

            $no = 1;
            foreach ($category->questions as $question) {

                $indicator = is_string($question->indicator)
                    ? json_decode($question->indicator, true) ?? []
                    : [];

                $keterangan = in_array($question->question_type, ['dropdown', 'pilihan'])
                    ? ''
                    : ($question->clue ?? '');

                $row = [
                    $question->sub,
                    $no++,
                    $question->question_text,
                    ':',
                    $keterangan,
                ];

                foreach ($this->indicatorGroups as $group) {
                    foreach ($group['levels'] as $level) {
                        if (
                            $group['key'] === 'umum' &&
                            $category->name === 'Informasi Umum'
                        ) {
                            $row[] = 'V';
                        } elseif (
                            $group['key'] === $category->id &&
                            in_array(strtolower($level), $indicator)
                        ) {
                            $row[] = 'V';
                        } else {
                            $row[] = '';
                        }
                    }
                }

                $rows->push($row);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        $row1 = ['Sub','No','Deskripsi',':','Keterangan'];
        $row2 = ['','','','',''];

        foreach ($this->indicatorGroups as $group) {
            foreach ($group['levels'] as $level) {
                $row1[] = $group['name'];
                $row2[] = $level;
            }
        }

        return [$row1, $row2];
    }

    public function styles(Worksheet $sheet)
    {
        
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(6);
        $sheet->getColumnDimension('C')->setWidth(45);
        $sheet->getColumnDimension('D')->setWidth(3);
        $sheet->getColumnDimension('E')->setWidth(40);

        $sheet->getStyle('A1:Z2')->getFont()->setBold(true);
        $sheet->getStyle('A1:Z2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('F:Z')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C')->getAlignment()->setWrapText(true);
        $sheet->getStyle('E')->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $spreadsheet = $event->sheet->getParent();

            $lastRow = $sheet->getHighestRow();
            $lastColumn = $this->lastIndicatorColumn();

            /*
            |------------------------------------------------------------------
            | 1. HEADER TABLE (ROW 1 & 2)
            |------------------------------------------------------------------
            */
            $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0070C0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            /*
|------------------------------------------------------------------
| MERGE HEADER FIXED (SUB, NO, DESKRIPSI, :, KETERANGAN)
|------------------------------------------------------------------
*/
$fixedHeaderCols = ['A', 'B', 'C', 'D', 'E'];

foreach ($fixedHeaderCols as $col) {
    $sheet->mergeCells("{$col}1:{$col}2");

    $sheet->getStyle("{$col}1")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);
}


            /*
|------------------------------------------------------------------
| FIX: MERGE HEADER INDICATOR (ROW 1)
|------------------------------------------------------------------
*/
$startColIndex = 6; // kolom F (setelah Keterangan)

foreach ($this->indicatorGroups as $group) {

    $count = count($group['levels']);
    if ($count <= 1) {
        $startColIndex += $count;
        continue;
    }

    $startCol = Coordinate::stringFromColumnIndex($startColIndex);
    $endCol   = Coordinate::stringFromColumnIndex($startColIndex + $count - 1);

    // merge nama category di row 1
    $sheet->mergeCells("{$startCol}1:{$endCol}1");

    // pastikan text di tengah
    $sheet->getStyle("{$startCol}1")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);

    $startColIndex += $count;
}


            /*
            |------------------------------------------------------------------
            | 2. OPTION SHEET
            |------------------------------------------------------------------
            */
            $optionSheet = $spreadsheet->createSheet();
            $optionSheet->setTitle('options');

            $categories = Category::with('questions.options')->get();

            $optionRanges = [];
            $optRow = 1;

            foreach ($categories as $category) {
                foreach ($category->questions as $question) {

                    if (!in_array($question->question_type, ['dropdown', 'pilihan'])) {
                        continue;
                    }

                    $options = $question->options->pluck('option_text')->toArray();
                    if (empty($options)) continue;

                    $start = $optRow;
                    foreach ($options as $opt) {
                        $optionSheet->setCellValue("A{$optRow}", $opt);
                        $optRow++;
                    }

                    $optionRanges[$question->id] =
                        "options!\$A\${$start}:\$A\$" . ($optRow - 1);
                }
            }

            /*
            |------------------------------------------------------------------
            | 3. DROPDOWN + DEFAULT VALUE (PER BARIS)
            |------------------------------------------------------------------
            */
            $row = 3;

            foreach ($categories as $category) {

                // skip header category
                $row++;

                foreach ($category->questions as $question) {

                    if (
                        in_array($question->question_type, ['dropdown', 'pilihan']) &&
                        isset($optionRanges[$question->id])
                    ) {
                        $cell = $sheet->getCell("E{$row}");

                        // default value = option pertama
                        $firstOption = $question->options->first()->option_text ?? '';
                        if ($cell->getValue() === null || $cell->getValue() === '') {
                            $cell->setValue($firstOption);
                        }

                        $validation = $cell->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(false);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('=' . $optionRanges[$question->id]);
                    }

                    $row++;
                }
            }

            $optionSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

            /*
            |------------------------------------------------------------------
            | 4. CATEGORY ROW (B = HEADER)
            |------------------------------------------------------------------
            */
            for ($r = 3; $r <= $lastRow; $r++) {
                if (
                    $sheet->getCell("A{$r}")->getValue() &&
                    $sheet->getCell("B{$r}")->getValue() === 'HEADER'
                ) {
                    $sheet->mergeCells("A{$r}:{$lastColumn}{$r}");

                    $sheet->getStyle("A{$r}:{$lastColumn}{$r}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'CAEDFB'],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
            }

            /*
            |------------------------------------------------------------------
            | 5. BORDER
            |------------------------------------------------------------------
            */
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
    ];
}
}
