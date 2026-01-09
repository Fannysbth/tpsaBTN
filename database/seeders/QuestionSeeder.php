<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Category;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama
        Question::truncate();
        QuestionOption::truncate();

        // Ambil semua kategori
        $categories = Category::all();

        foreach ($categories as $category) {
            // Buat pertanyaan berdasarkan kategori
            switch ($category->name) {
                case 'Keuangan':
                    $this->createFinancialQuestions($category);
                    break;
                case 'Operasional':
                    $this->createOperationalQuestions($category);
                    break;
                case 'Sumber Daya Manusia':
                    $this->createHRQuestions($category);
                    break;
                case 'Teknologi Informasi':
                    $this->createITQuestions($category);
                    break;
                case 'Kepatuhan':
                    $this->createComplianceQuestions($category);
                    break;
                case 'Manajemen Risiko':
                    $this->createRiskManagementQuestions($category);
                    break;
            }
        }

        $this->command->info('Question data seeded successfully!');
    }

    private function createFinancialQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan memiliki laporan keuangan yang diaudit secara rutin?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih salah satu opsi yang sesuai',
                'has_attachment' => true,
                'indicator' => 'high',
                'order' => 1,
                'options' => [
                    ['text' => 'Ya, diaudit oleh KAP Big Four', 'score' => 10],
                    ['text' => 'Ya, diaudit oleh KAP ternama', 'score' => 8],
                    ['text' => 'Ya, diaudit oleh KAP lokal', 'score' => 6],
                    ['text' => 'Tidak diaudit secara eksternal', 'score' => 3],
                    ['text' => 'Tidak memiliki laporan keuangan formal', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Berapa rasio current ratio perusahaan?',
                'question_type' => 'pilihan',
                'clue' => 'Rasio likuiditas',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 2,
                'options' => [
                    ['text' => '> 2.0', 'score' => 10],
                    ['text' => '1.5 - 2.0', 'score' => 8],
                    ['text' => '1.0 - 1.5', 'score' => 6],
                    ['text' => '< 1.0', 'score' => 3],
                    ['text' => 'Tidak diketahui', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Apakah perusahaan memiliki sistem pengendalian internal yang memadai?',
                'question_type' => 'isian',
                'clue' => 'Jelaskan sistem pengendalian internal yang dimiliki',
                'has_attachment' => true,
                'indicator' => 'high',
                'order' => 3,
                'options' => []
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

    private function createOperationalQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan memiliki sistem manajemen mutu (ISO 9001)?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih status sertifikasi',
                'has_attachment' => true,
                'indicator' => 'medium',
                'order' => 1,
                'options' => [
                    ['text' => 'Ya, tersertifikasi dan masih berlaku', 'score' => 10],
                    ['text' => 'Sudah diterapkan tapi belum tersertifikasi', 'score' => 7],
                    ['text' => 'Dalam proses sertifikasi', 'score' => 5],
                    ['text' => 'Tidak memiliki', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Berapa tingkat efisiensi produksi?',
                'question_type' => 'isian',
                'clue' => 'Masukkan persentase efisiensi (contoh: 85%)',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 2,
                'options' => []
            ],
            [
                'question_text' => 'Apakah perusahaan melakukan maintenance rutin pada mesin produksi?',
                'question_type' => 'checkbox',
                'clue' => 'Pilih semua yang berlaku',
                'has_attachment' => false,
                'indicator' => 'low',
                'order' => 3,
                'options' => [
                    ['text' => 'Maintenance harian', 'score' => 3],
                    ['text' => 'Maintenance mingguan', 'score' => 2],
                    ['text' => 'Maintenance bulanan', 'score' => 2],
                    ['text' => 'Maintenance tahunan', 'score' => 2],
                    ['text' => 'Maintenance saat rusak saja', 'score' => 1],
                ]
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

    private function createHRQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan memiliki program pelatihan karyawan?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih frekuensi pelatihan',
                'has_attachment' => true,
                'indicator' => 'medium',
                'order' => 1,
                'options' => [
                    ['text' => 'Rutin setiap bulan', 'score' => 10],
                    ['text' => 'Rutin setiap 3 bulan', 'score' => 8],
                    ['text' => 'Rutin setiap 6 bulan', 'score' => 6],
                    ['text' => 'Sesekali', 'score' => 3],
                    ['text' => 'Tidak ada', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Berapa tingkat turnover karyawan?',
                'question_type' => 'isian',
                'clue' => 'Masukkan persentase turnover tahunan',
                'has_attachment' => false,
                'indicator' => 'high',
                'order' => 2,
                'options' => []
            ],
            [
                'question_text' => 'Apa saja benefit yang diberikan kepada karyawan?',
                'question_type' => 'checkbox',
                'clue' => 'Pilih semua benefit yang tersedia',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 3,
                'options' => [
                    ['text' => 'BPJS Kesehatan', 'score' => 3],
                    ['text' => 'BPJS Ketenagakerjaan', 'score' => 3],
                    ['text' => 'Asuransi tambahan', 'score' => 2],
                    ['text' => 'THR', 'score' => 2],
                    ['text' => 'Bonus kinerja', 'score' => 2],
                    ['text' => 'Cuti tahunan', 'score' => 2],
                ]
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

    private function mapQuestionType(string $type): string
{
    return match ($type) {
        'isian' => 'isian',
        default => 'pilihan', // pilihan + checkbox masuk sini
    };
}


    private function createITQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan memiliki sistem backup data?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih frekuensi backup',
                'has_attachment' => true,
                'indicator' => 'high',
                'order' => 1,
                'options' => [
                    ['text' => 'Backup real-time dan offsite', 'score' => 10],
                    ['text' => 'Backup harian otomatis', 'score' => 8],
                    ['text' => 'Backup mingguan manual', 'score' => 5],
                    ['text' => 'Tidak ada backup', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Apakah sistem keamanan informasi sudah memadai?',
                'question_type' => 'isian',
                'clue' => 'Jelaskan sistem keamanan yang diterapkan',
                'has_attachment' => false,
                'indicator' => 'high',
                'order' => 2,
                'options' => []
            ],
            [
                'question_text' => 'Apa saja langkah keamanan IT yang diterapkan?',
                'question_type' => 'checkbox',
                'clue' => 'Pilih semua yang diterapkan',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 3,
                'options' => [
                    ['text' => 'Firewall', 'score' => 2],
                    ['text' => 'Antivirus', 'score' => 2],
                    ['text' => 'Enkripsi data', 'score' => 3],
                    ['text' => 'Two-factor authentication', 'score' => 3],
                    ['text' => 'Monitoring sistem 24/7', 'score' => 3],
                    ['text' => 'Regular penetration testing', 'score' => 2],
                ]
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

    private function createComplianceQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan mematuhi semua peraturan perpajakan?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih status kepatuhan',
                'has_attachment' => true,
                'indicator' => 'high',
                'order' => 1,
                'options' => [
                    ['text' => 'Selalu patuh dan mendapat penghargaan', 'score' => 10],
                    ['text' => 'Selalu patuh tanpa kendala', 'score' => 8],
                    ['text' => 'Kadang ada keterlambatan kecil', 'score' => 5],
                    ['text' => 'Sering terlambat', 'score' => 2],
                    ['text' => 'Ada masalah dengan otoritas', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Bagaimana sistem pelaporan kepatuhan perusahaan?',
                'question_type' => 'isian',
                'clue' => 'Jelaskan sistem yang digunakan',
                'has_attachment' => true,
                'indicator' => 'medium',
                'order' => 2,
                'options' => []
            ],
            [
                'question_text' => 'Peraturan apa saja yang harus dipatuhi?',
                'question_type' => 'checkbox',
                'clue' => 'Pilih semua peraturan yang berlaku',
                'has_attachment' => false,
                'indicator' => 'high',
                'order' => 3,
                'options' => [
                    ['text' => 'UU Ketenagakerjaan', 'score' => 2],
                    ['text' => 'UU Perseroan Terbatas', 'score' => 2],
                    ['text' => 'Peraturan Perpajakan', 'score' => 3],
                    ['text' => 'UU Lingkungan Hidup', 'score' => 3],
                    ['text' => 'UU Perlindungan Konsumen', 'score' => 2],
                    ['text' => 'UU Anti Korupsi', 'score' => 3],
                ]
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

    private function createRiskManagementQuestions($category)
    {
        $questions = [
            [
                'question_text' => 'Apakah perusahaan memiliki sistem manajemen risiko?',
                'question_type' => 'pilihan',
                'clue' => 'Pilih tingkat implementasi',
                'has_attachment' => true,
                'indicator' => 'high',
                'order' => 1,
                'options' => [
                    ['text' => 'Sudah diimplementasikan dengan baik', 'score' => 10],
                    ['text' => 'Sedang dalam implementasi', 'score' => 7],
                    ['text' => 'Ada dokumen tapi belum diterapkan', 'score' => 4],
                    ['text' => 'Tidak memiliki', 'score' => 0],
                ]
            ],
            [
                'question_text' => 'Seberapa sering risk assessment dilakukan?',
                'question_type' => 'isian',
                'clue' => 'Jelaskan frekuensi assessment risiko',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 2,
                'options' => []
            ],
            [
                'question_text' => 'Apa saja jenis risiko yang sudah diidentifikasi?',
                'question_type' => 'checkbox',
                'clue' => 'Pilih semua jenis risiko',
                'has_attachment' => false,
                'indicator' => 'medium',
                'order' => 3,
                'options' => [
                    ['text' => 'Risiko operasional', 'score' => 2],
                    ['text' => 'Risiko keuangan', 'score' => 2],
                    ['text' => 'Risiko strategis', 'score' => 2],
                    ['text' => 'Risiko compliance', 'score' => 3],
                    ['text' => 'Risiko reputasi', 'score' => 2],
                    ['text' => 'Risiko teknologi', 'score' => 2],
                ]
            ],
        ];

        $this->createQuestionsForCategory($category, $questions);
    }

   private function createQuestionsForCategory($category, $questions)
{
    foreach ($questions as $q) {
        $question = Question::create([
            'category_id'   => $category->id,
            'question_text' => $q['question_text'],
            'question_type' => $this->mapQuestionType($q['question_type']), // 👈 PENTING
            'clue'          => $q['clue'],
            'has_attachment'=> $q['has_attachment'],
            'indicator'     => $q['indicator'],
            'order'         => $q['order'],
            'is_active'     => true,
        ]);

        if (in_array($q['question_type'], ['pilihan', 'checkbox']) && !empty($q['options'])) {
            foreach ($q['options'] as $option) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['text'],
                    'score'       => $option['score'],
                ]);
            }
        }
    }
}

}