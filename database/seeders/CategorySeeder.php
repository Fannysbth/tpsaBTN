<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class QuestionnaireExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithEvents
{
    protected array $indicatorGroups = [];
    protected int $indicatorColumnCount = 0;

    public function __construct()
    {
        $categories = Category::all();

        foreach ($categories as $category) {

            // 🔹 INFORMASI UMUM → 1 KOLOM SAJA
            if ($category->name === 'Informasi Umum') {
                $this->indicatorGroups[] = [
                    'type'   => 'umum',
                    'name'   => 'Umum',
                    'levels' => ['Umum'],
                ];
                $this->indicatorColumnCount += 1;
                continue;
            }

            // 🔹 CATEGORY LAIN → HIGH / MEDIUM / LOW
            $levels = array_keys($category->criteria ?? []);

            $this->indicatorGroups[] = [
                'type'   => 'category',
                'id'     => $category->id,
                'name'   => $category->name,
                'levels' => array_map(fn($l) => ucfirst($l), $levels),
            ];

            $this->indicatorColumnCount += count($levels);
        }
    }

    // =====================================================
    // HEADERS (2 ROW)
    // =====================================================
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

    // =====================================================
    // DATA
    // =====================================================
    public function collection()
    {
        $rows = collect();

        $categories = Category::with(['questions'])->get();

        foreach ($categories as $category) {

            // ===== CATEGORY ROW (SECTION TITLE) =====
            $rows->push(array_merge(
                [$category->name, '', '', '', ''],
                array_fill(0, $this->indicatorColumnCount, '')
            ));

            // ===== QUESTIONS =====
            $grouped = $category->questions->groupBy('sub');

            foreach ($grouped as $sub => $questions) {

                $no = 1;
                foreach ($questions as $question) {

                    $row = [
                        $sub,
                        $no++,
                        $question->question_text,
                        ':',
                        $question->clue ?? '',
                    ];

                    foreach ($this->indicatorGroups as $group) {

                        foreach ($group['levels'] as $level) {

                            // INFORMASI UMUM → AUTO CENTANG
                            if (
                                $group['type'] === 'umum' &&
                                $category->name === 'Informasi Umum'
                            ) {
                                $row[] = 'V';
                            }
                            // CATEGORY SESUAI INDIKATOR
                            elseif (
                                ($group['type'] ?? '') === 'category' &&
                                $group['id'] === $category->id &&
                                in_array(
                                    strtolower($level),
                                    $question->indicator ?? []
                                )
                            ) {
                                $row[] = 'V';
                            }
                            else {
                                $row[] = '';
                            }
                        }
                    }

                    $rows->push($row);
                }
            }
        }

        return $rows;
    }

    // =====================================================
    // STYLES
    // =====================================================
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:Z2')->getFont()->setBold(true);
        $sheet->getStyle('A1:Z2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:Z2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('F:Z')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C:E')->getAlignment()->setWrapText(true);

        return [];
    }

    // =====================================================
    // EVENTS
    // =====================================================
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // 🔹 MERGE HEADER GROUP
                $col = 6; // kolom F
                foreach ($this->indicatorGroups as $group) {
                    $count = count($group['levels']);
                    if ($count > 1) {
                        $start = Coordinate::stringFromColumnIndex($col);
                        $end   = Coordinate::stringFromColumnIndex($col + $count - 1);
                        $sheet->mergeCells("{$start}1:{$end}1");
                    }
                    $col += $count;
                }

                // 🔹 MERGE CATEGORY ROW KE KANAN
                for ($row = 3; $row <= $lastRow; $row++) {
                    if (
                        $sheet->getCell("A{$row}")->getValue() &&
                        $sheet->getCell("B{$row}")->getValue() === ''
                    ) {
                        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                        $sheet->getStyle("A{$row}")
                            ->getFont()->setBold(true);
                    }
                }

                // 🔹 BORDER
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
