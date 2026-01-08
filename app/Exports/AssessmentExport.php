<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AssessmentExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment->load(['answers.question', 'answers.question.options']);
    }

    public function collection()
    {
        $data = [];
        $categories = \App\Models\Category::with(['activeQuestions' => function($query) {
            $query->orderBy('order');
        }])->get();

        foreach ($categories as $category) {
            $data[] = ['Kategori: ' . $category->name];
            
            foreach ($category->activeQuestions as $question) {
                $answer = $this->assessment->answers
                    ->where('question_id', $question->id)
                    ->first();

                $row = [
                    $question->question_text,
                    $answer ? $answer->answer_text : '',
                    $answer ? $answer->score : 0,
                    $question->has_attachment ? ($answer && $answer->attachment_path ? '✓' : '') : 'N/A'
                ];
                $data[] = $row;
            }
            $data[] = []; // Empty row between categories
        }

        // Total Score
        $data[] = ['TOTAL SCORE', '', $this->assessment->total_score, ''];
        $data[] = ['RISK LEVEL', '', strtoupper($this->assessment->risk_level), ''];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'PERTANYAAN',
            'JAWABAN',
            'SCORE',
            'ATTACHMENT'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        
        // Set borders
        $lastRow = $sheet->getHighestRow();
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:D'.$lastRow)->applyFromArray($styleArray);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 40,
            'C' => 15,
            'D' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Freeze first row
                $event->sheet->freezePane('A2');
            },
        ];
    }
}