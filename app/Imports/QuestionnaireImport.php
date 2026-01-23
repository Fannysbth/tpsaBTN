<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionnaireImport implements ToCollection, WithHeadingRow
{
    public $importData = [];
    public $errors = [];
    public $categories;
    
    public function __construct()
    {
        $this->categories = Category::all()->keyBy('name');
    }
    
    public function collection(Collection $rows)
    {
        $currentCategory = null;
        $currentSub = null;
        $questionNumber = 0;
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 karena header di row 1 dan index mulai dari 0
            
            $sub = $row['sub'] ?? $row['Sub'] ?? null;
            $no = $row['no'] ?? $row['No'] ?? null;
            $deskripsi = $row['deskripsi'] ?? $row['Deskripsi'] ?? null;
            $keterangan = $row['keterangan'] ?? $row['Keterangan'] ?? null;
            
            // Ambil indicator dari Business & Operational Criticality
            $umum = $row['umum'] ?? $row['Umum'] ?? null;
            $high = $row['high'] ?? $row['High'] ?? null;
            $med = $row['med'] ?? $row['Med'] ?? null;
            $low = $row['low'] ?? $row['Low'] ?? null;
            
            // Skip baris kosong
            if (empty($sub) && empty($no) && empty($deskripsi)) {
                continue;
            }
            
            // Jika ada Sub dan No kosong, ini adalah kategori
            if (!empty($sub) && empty($no)) {
                // Cek apakah kategori ada
                if (isset($this->categories[$sub])) {
                    $currentCategory = $this->categories[$sub];
                    $currentSub = null;
                } else {
                    // Jika bukan kategori utama, anggap sebagai sub kategori
                    if ($currentCategory) {
                        $currentSub = $sub;
                    } else {
                        $this->errors[] = "Baris {$rowNumber}: Sub kategori '{$sub}' tidak memiliki kategori utama";
                    }
                }
                continue;
            }
            
            // Jika ada No, ini adalah pertanyaan
            if (!empty($no) && !empty($deskripsi)) {
                if (!$currentCategory) {
                    $this->errors[] = "Baris {$rowNumber}: Pertanyaan '{$deskripsi}' tidak memiliki kategori";
                    continue;
                }
                
                // Tentukan indicator
                $indicators = [];
                if ($umum == 'V') {
                    // Umum tidak masuk ke indicator
                }
                if ($high == 'V') $indicators[] = 'high';
                if ($med == 'V') $indicators[] = 'medium';
                if ($low == 'V') $indicators[] = 'low';
                
                $this->importData[] = [
                    'row_number' => $rowNumber,
                    'category_id' => $currentCategory->id,
                    'category_name' => $currentCategory->name,
                    'sub' => $currentSub,
                    'no' => $no,
                    'question_text' => $deskripsi,
                    'clue' => $keterangan,
                    'indicator' => $indicators,
                    'question_type' => 'isian', // Default, bisa diubah nanti
                    'is_new' => true
                ];
                
                $questionNumber++;
            }
        }
    }
}