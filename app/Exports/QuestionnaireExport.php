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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QuestionnaireExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected array $indicatorGroups = [];
    protected int $indicatorColumnCount = 0;
    protected array $mergeMap = [];

    protected function lastIndicatorColumn(): string
    {
        return Coordinate::stringFromColumnIndex(6 + $this->indicatorColumnCount);
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

        $excelRow = 3; // data mulai setelah header 2 baris

        foreach ($categories as $category) {

            // ===== CATEGORY HEADER =====
            $rows->push(array_merge(
                [$category->name, 'HEADER', '', '', '', ''],
                array_fill(0, $this->indicatorColumnCount, '')
            ));
            $excelRow++;

            $no = 1;

            foreach ($category->questions as $question) {

                $indicator = is_string($question->indicator)
                    ? json_decode($question->indicator, true) ?? []
                    : [];

                $options = $question->question_type === 'pilihan'
                    ? $question->options
                    : collect();

                $rowSpan = max(1, $options->count());
                $startRow = $excelRow;

                // ===== BARIS PERTAMA (PERTANYAAN + PILIHAN PERTAMA) =====
                $firstOption = $options->first();

                $isPilihan = $question->question_type === 'pilihan';

$baseRow = [
    $question->sub,
    $no++,
    $question->question_text,
    ':',
    $isPilihan
        ? ($firstOption->option_text ?? '')
        : ($question->clue ?? '-'),
    $isPilihan
        ? ($firstOption->score ?? '')
        : '-', // 👈 SCORE CLUE = STRIP
];


                foreach ($this->indicatorGroups as $group) {
                    foreach ($group['levels'] as $level) {
                        if (
                            $group['key'] === 'umum' &&
                            $category->name === 'Informasi Umum'
                        ) {
                            $baseRow[] = 'V';
                        } elseif (
                            $group['key'] === $category->id &&
                            in_array(strtolower($level), $indicator)
                        ) {
                            $baseRow[] = 'V';
                        } else {
                            $baseRow[] = '';
                        }
                    }
                }

                $rows->push($baseRow);
                $excelRow++;

                // ===== BARIS PILIHAN SELANJUTNYA =====
                if ($options->count() > 1) {
                    foreach ($options->slice(1) as $opt) {
                        $rows->push(array_merge(
                            ['', '', '', '', $opt->option_text, (string) $opt->score],
                            array_fill(0, $this->indicatorColumnCount, '')
                        ));
                        $excelRow++;
                    }
                }

                // simpan untuk merge
                $this->mergeMap[] = [
    'start' => $startRow,
    'end' => $startRow + $rowSpan - 1,
    'hasScore' => $isPilihan
];

            }
        }

        return $rows;
    }

    public function headings(): array
    {
        $row1 = ['Sub', 'No', 'Deskripsi', ':', 'Pilihan', 'Score'];
        $row2 = ['', '', '', '', '', ''];

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
        $sheet->getColumnDimension('F')->setWidth(10);

        $sheet->getStyle('C:E')->getAlignment()->setWrapText(true);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $this->lastIndicatorColumn();

                // ===== HEADER STYLE =====
                $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0070C0'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // merge header tetap
                foreach (['A','B','C','D','E','F'] as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // merge header indikator
                $startColIndex = 7;
                foreach ($this->indicatorGroups as $group) {
                    $count = count($group['levels']);
                    if ($count > 1) {
                        $start = Coordinate::stringFromColumnIndex($startColIndex);
                        $end = Coordinate::stringFromColumnIndex($startColIndex + $count - 1);
                        $sheet->mergeCells("{$start}1:{$end}1");
                    }
                    $startColIndex += $count;
                }

                // ===== CATEGORY ROW STYLE =====
                for ($r = 3; $r <= $lastRow; $r++) {
                    if ($sheet->getCell("B{$r}")->getValue() === 'HEADER') {
                        $sheet->mergeCells("A{$r}:{$lastColumn}{$r}");
                        $sheet->getStyle("A{$r}:{$lastColumn}{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'CAEDFB'],
                            ],
                        ]);
                    }
                }

                // ===== MERGE PERTANYAAN & CLUE =====
                foreach ($this->mergeMap as $m) {
                    if ($m['start'] < $m['end']) {
                        foreach (['A','B','C','D'] as $col) {
                            $sheet->mergeCells("{$col}{$m['start']}:{$col}{$m['end']}");
                        }
                        if (!$m['hasScore']) {
                            $sheet->mergeCells("E{$m['start']}:F{$m['end']}");
                        }
                    }
                }

                // ===== BORDER =====
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
