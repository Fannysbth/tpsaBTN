<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AssessmentReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $assessments;
    protected $no = 0;

    public function __construct($assessments)
    {
        $this->assessments = $assessments;
    }

    public function collection()
    {
        return $this->assessments;
    }

    public function headings(): array
    {
        return [
            'No',
            'Company Name',
            'Assessment Date',
            'Risk Level',
            'Score',
        ];
    }

    public function map($assessment): array
    {
        $this->no++;

        return [
            $this->no,
            $assessment->company_name,
            optional($assessment->assessment_date)->format('d/m/Y'),
            $assessment->risk_level_label ? strtoupper($assessment->risk_level_label) : 'Belum Dinilai',
            $assessment->total_score ?? '-',
        ];
    }

    /**
     * Style HEADER
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // baris header
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0070C0'],
                ],
            ],
        ];
    }

    /**
     * Border untuk SEMUA CELL
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $cellRange = 'A1:' . $highestColumn . $highestRow;

                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
