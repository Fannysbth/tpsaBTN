<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class QuestionnaireExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    public function collection()
    {
        $data = collect();
        $categories = Category::with('questions')->get();
        
        foreach ($categories as $category) {
            // Baris kategori
            $data->push([
                'Sub' => $category->name,
                'No' => '',
                'Deskripsi' => '',
                'Keterangan' => '',
                'Umum' => '',
                'High' => '',
                'Med' => '',
                'Low' => '',
                'High_2' => '',
                'Med_2' => '',
                'Low_2' => '',
                'High_3' => '',
                'Med_3' => '',
                'Low_3' => ''
            ]);

            // Kelompokkan pertanyaan berdasarkan sub
            $questionsBySub = $category->questions->groupBy('sub');
            
            foreach ($questionsBySub as $sub => $questions) {
                // Baris sub kategori
                $data->push([
                    'Sub' => $sub ?? '',
                    'No' => '',
                    'Deskripsi' => '',
                    'Keterangan' => '',
                    'Umum' => '',
                    'High' => '',
                    'Med' => '',
                    'Low' => '',
                    'High_2' => '',
                    'Med_2' => '',
                    'Low_2' => '',
                    'High_3' => '',
                    'Med_3' => '',
                    'Low_3' => ''
                ]);

                // Pertanyaan-pertanyaan
                $no = 1;
                foreach ($questions as $question) {
                    // Parse indicator
                    $indicator = json_decode($question->indicator ?? '[]', true) ?: [];
                    
                    // Business & Operational Criticality
                    $umum = empty($indicator) ? 'V' : '';
                    $high = in_array('high', $indicator) ? 'V' : '';
                    $med = in_array('medium', $indicator) ? 'V' : '';
                    $low = in_array('low', $indicator) ? 'V' : '';
                    
                    // Untuk Sensitive Data dan Technology Integration, kosongkan karena tidak ada data
                    $high2 = '';
                    $med2 = '';
                    $low2 = '';
                    $high3 = '';
                    $med3 = '';
                    $low3 = '';

                    $data->push([
                        'Sub' => '',
                        'No' => $no++,
                        'Deskripsi' => $question->question_text,
                        'Keterangan' => $question->clue ?? '',
                        'Umum' => $umum,
                        'High' => $high,
                        'Med' => $med,
                        'Low' => $low,
                        'High_2' => $high2,
                        'Med_2' => $med2,
                        'Low_2' => $low2,
                        'High_3' => $high3,
                        'Med_3' => $med3,
                        'Low_3' => $low3
                    ]);
                }
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Sub',
            'No',
            'Deskripsi',
            'Keterangan',
            'Umum', 'High', 'Med', 'Low', // Business & Operational Criticality
            'High', 'Med', 'Low', // Sensitive Data
            'High', 'Med', 'Low'  // Technology Integration
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header
            1 => ['font' => ['bold' => true]],
            
            // Auto size semua kolom
            'A:N' => ['alignment' => ['vertical' => 'top']],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER, // Format kolom No sebagai angka
        ];
    }
}