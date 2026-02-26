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

    protected function lastIndicatorColumn(): string
    {
        return Coordinate::stringFromColumnIndex(6 + $this->indicatorColumnCount + 1);
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

$categories->each(function ($category) {

    $category->questions = $category->questions
        ->sortBy(function ($q) {

            preg_match('/^(\d+)([a-zA-Z]*)$/', $q->question_no, $matches);

            $number = isset($matches[1]) ? (int) $matches[1] : 0;
            $suffix = $matches[2] ?? '';

            return [$number, $suffix];
        })
        ->values();
});



        foreach ($categories as $category) {

            // ===== CATEGORY HEADER =====
            $rows->push(array_merge(
                [$category->name, 'HEADER', '', '', '', ''],
                array_fill(0, $this->indicatorColumnCount, ''),
                ['']
            ));

            $no = 1;

            foreach ($category->questions as $question) {

                $indicator = is_array($question->indicator)
    ? $question->indicator
    : (json_decode($question->indicator, true) ?? []);

                $isPilihan = $question->question_type === 'pilihan';
                $options   = $isPilihan ? $question->options : collect();
                $firstOpt  = $options->first();

                // ===== BARIS PERTANYAAN (OPTION PERTAMA LANGSUNG DI SINI) =====
                $baseRow = [
                    $question->sub,
                    $question->question_no,
                    $question->question_text,
                    ':',
                    $isPilihan ? ($firstOpt->option_text ?? '') : ($question->clue ?? '-'),
                    $isPilihan ? ($firstOpt->score ?? '') : '-',
                ];

                foreach ($this->indicatorGroups as $group) {
    foreach ($group['levels'] as $level) {

        if (
            $group['key'] === 'umum' &&
            $category->name === 'Umum'
        ) {
            $baseRow[] = 'V';

        } elseif (
            $group['key'] === $category->id &&
            in_array(strtolower($level), array_map('strtolower', $indicator))
        ) {
            $baseRow[] = 'V';

        } else {
            $baseRow[] = '';
        }
    }
}

                $baseRow[] = $question->attachment_text ?? '-';
                $rows->push($baseRow);

                // ===== OPTION LANJUTAN (TANPA GAP) =====
                if ($isPilihan && $options->count() > 1) {
                    foreach ($options->slice(1) as $opt) {
                        $rows->push(array_merge(
                            ['', '', '', '', $opt->option_text, (string) $opt->score],
                            array_fill(0, $this->indicatorColumnCount, ''),
                            ['']
                        ));
                    }
                }
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

        $row1[] = 'Attachment';
        $row2[] = '';

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

        $attachmentCol = Coordinate::stringFromColumnIndex(6 + $this->indicatorColumnCount + 1);
        $sheet->getColumnDimension($attachmentCol)->setWidth(35);

        $sheet->getStyle("C:E")->getAlignment()->setWrapText(true);
        $sheet->getStyle($attachmentCol)->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $this->lastIndicatorColumn();
                $sheet->freezePane('A3');


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

                // merge kolom statis header
                foreach (['A','B','C','D','E','F', $lastColumn] as $col) {
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

                // ===== CATEGORY STYLE =====
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

                // ===== BORDER =====
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
