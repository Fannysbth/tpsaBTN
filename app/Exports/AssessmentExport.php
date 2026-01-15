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
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;

class AssessmentExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{

    protected array $categoryRowMap = [];
    protected array $questionsMap = [];
    protected $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment->load(['answers.question', 'answers.question.options']);
    }

    public function collection()
{
    $data = [];
    $this->categoryRowMap = [];
    $this->questionsMap = [];
    $row = 2;
    
    $assessment = $this->assessment;
    
    // Group jawaban berdasarkan kategori
    $grouped = $assessment->answers
        ->load('question.category', 'question.options')
        ->groupBy(fn($a) => $a->question->category_id);
    
    foreach ($grouped as $categoryId => $answers) {
        $category = $answers->first()->question->category;
        $indicator = $assessment->category_scores[$categoryId]['indicator'] ?? null;
        $categoryScore = $assessment->category_scores[$categoryId]['score'] ?? 0;
        
        // Baris kategori dengan score
        $data[] = [
            'Kategori: ' . $category->name . ' (Indikator: ' . strtoupper($indicator) . ')',
            '', 
            ''
        ];
        
        $this->categoryRowMap[$categoryId] = $row;
        $row++;
        
        foreach ($answers as $answer) {
            $question = $answer->question;
            
            // Pastikan sesuai indikator
            $questionIndicators = $question->indicator;
            
            if (is_string($questionIndicators)) {
                $decoded = json_decode($questionIndicators, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $questionIndicators = $decoded;
                } else {
                    $questionIndicators = array_map('trim', explode(',', $questionIndicators));
                }
            }
            
            $questionIndicators = $questionIndicators ?? [];
            
            if ($indicator && !in_array($indicator, $questionIndicators)) {
                continue;
            }
            
            $data[] = [
                $question->question_text,
                $answer->answer_text ?? ($question->clue ?? '-- Pilih Jawaban --'),
                $question->has_attachment ? ($answer->attachment_info ?? $question->attachment_text ?? 'Perlu Upload') : '-'
            ];
            
            $this->questionsMap[$row] = $question;
            $row++;
        }
        
        // Tambah baris kosong antar kategori
        $data[] = ['', '', ''];
        $row++;
    }
    
    
    return collect($data);
}


public function registerEvents(): array
{
    return [
        AfterSheet::class => function(AfterSheet $event){
            $sheet = $event->sheet->getDelegate();
            $lastRow = $sheet->getHighestRow();

            // 1. Merge kategori
            
foreach ($this->categoryRowMap as $row) {
    $sheet->mergeCells("A{$row}:C{$row}"); // sesuaikan jumlah kolom
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');

    // Tambahkan border untuk merge cell
    $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);
}

$lastRow = $sheet->getHighestRow(); // pastikan lastRow terbaru setelah tabel
$statementStartRow = $lastRow + 2; // 1 baris kosong setelah tabel


$statement = [
    "Saya yang bertanda-tangan di bawah ini menyatakan bahwa data yang saya isi pada formulir ini adalah benar, dan Apabila di kemudian hari ditemukan kecurangan/pemalsuan/penipuan pada data, dokumen atau informasi tersebut, maka saya bersedia diberikan sanksi sesuai dengan perundangan/peraturan yang berlaku.",
    "<Lokasi, Tanggal>",
    "",
    "TTD, materai dan CAP perusahaan",
    "",
    "(…………………………………….)",
    "<Nama Perusahaan>"
];

foreach ($statement as $i => $line) {
    $rowNum = $statementStartRow + $i;
    $sheet->setCellValue("A{$rowNum}", $line);
    // Merge agar teks melebar sesuai kolom
    $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
    $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal('left');
    $sheet->getStyle("A{$rowNum}")->getAlignment()->setWrapText(true);

    $sheet->getStyle("A{$rowNum}")->getFont()->setBold(false);
    // Tidak pakai border
}



            // 2. Iterasi pertanyaan untuk dropdown & conditional font
            for ($row = 2; $row <= $lastRow; $row++) {
    $cell = "B{$row}";
    $value = $sheet->getCell($cell)->getValue();

    // Ambil pertanyaan dari map
    $question = $this->questionsMap[$row] ?? null;

    // Conditional font color
    if (!$question || $value == "" || $value == "-- Pilih Jawaban --" || $value == ($question->clue ?? '')) {
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FF999999');
    } else {
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB(Color::COLOR_BLACK);
    }

    // Dropdown untuk pertanyaan pilihan
    if ($question && $question->question_type === 'pilihan') {
        $options = $question->options->pluck('option_text')->toArray();
        array_unshift($options, '-- Pilih Jawaban --');
        $formula = '"' . implode(',', $options) . '"';

        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);
    }
}



            $event->sheet->freezePane('A2');
        }
    ];
}



    public function headings(): array
    {
        return ['PERTANYAAN','JAWABAN','ATTACHMENT'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:C{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    public function columnWidths(): array
    {
        return ['A'=>50,'B'=>40,'C'=>15,'D'=>20];
    }

    
}
