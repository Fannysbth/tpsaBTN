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



class QuestionnaireExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{

protected function lastIndicatorColumn(): string
{
    return Coordinate::stringFromColumnIndex(5 + $this->indicatorColumnCount);
}


    protected array $indicatorGroups = [];
    protected int $indicatorColumnCount = 0;

    public function __construct()
    {
        $categories = Category::all();

        // Umum (1 kolom saja)
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

            // ===== CATEGORY ROW (SECTION HEADER) =====
            $rows->push(array_merge(
                [$category->name, '', '', '', ''],
                array_fill(0, $this->indicatorColumnCount, '')
            ));

            $no = 1;
            foreach ($category->questions as $question) {

                $indicator = $question->indicator;
                if (is_string($indicator)) {
                    $indicator = json_decode($indicator, true) ?: [];
                    }

                if (!is_array($indicator)) {
                    $indicator = [];
                }

                // ===== KETERANGAN =====
$keterangan = '';

if (in_array($question->question_type, ['dropdown', 'pilihan'])) {
    $keterangan = '';
} else {
    $keterangan = $question->clue ?? '';
}


$row = [
    $question->sub,
    $no++,
    $question->question_text,
    ':',
    $keterangan,
];


                foreach ($this->indicatorGroups as $group) {
                    foreach ($group['levels'] as $level) {

                        // Informasi Umum → Umum
                        if (
                            $group['key'] === 'umum' &&
                            $category->name === 'Informasi Umum'
                        ) {
                            $row[] = 'V';
                        }
                        // Category lain → High / Medium / Low
                        elseif (
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
        $sheet->getColumnDimension('B')->setWidth(5);
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
            $lastRow = $sheet->getHighestRow();
            $lastColumn = $this->lastIndicatorColumn();

            /*
            |--------------------------------------------------------------------------
            | 1. DROPDOWN (KETERANGAN)
            |--------------------------------------------------------------------------
            */
            $row = 3;
            $categories = Category::with('questions.options')->get();

            foreach ($categories as $category) {
                foreach ($category->questions as $question) {

                    // skip row header kategori
                    if ($sheet->getCell("B{$row}")->getValue() === null) {
                        $row++;
                        continue;
                    }

                    if (in_array($question->question_type, ['dropdown', 'pilihan'])) {

                        $optionsArray = $question->options->pluck('option_text')->toArray();

if (!empty($optionsArray)) {

    // 1️⃣ isi default value (option pertama)
    $sheet->setCellValue("E{$row}", $optionsArray[0]);

    // 2️⃣ pasang dropdown
    $validation = $sheet->getCell("E{$row}")->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setAllowBlank(false);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"' . implode(',', $optionsArray) . '"');
}

                    }

                    $row++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 2. MERGE HEADER KATEGORI (Business / Sensitive / dll)
            |--------------------------------------------------------------------------
            */
            $col = 6;
            foreach ($this->indicatorGroups as $group) {
                $count = count($group['levels']);
                if ($count > 1) {
                    $start = Coordinate::stringFromColumnIndex($col);
                    $end   = Coordinate::stringFromColumnIndex($col + $count - 1);
                    $sheet->mergeCells("{$start}1:{$end}1");
                }
                $col += $count;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. MERGE ROW CATEGORY (Informasi Umum, dll)
            |--------------------------------------------------------------------------
            */
            for ($r = 3; $r <= $lastRow; $r++) {
                if (
                    $sheet->getCell("A{$r}")->getValue() &&
                    $sheet->getCell("B{$r}")->getValue() === null
                ) {
                    $sheet->mergeCells("A{$r}:{$lastColumn}{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4. BORDER
            |--------------------------------------------------------------------------
            */
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
    ];
}
}
