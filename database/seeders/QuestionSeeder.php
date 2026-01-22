<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama jika ada
        Question::truncate();

        // Ambil kategori
        $infoUmum = Category::where('name', 'Informasi Umum')->first();
        $businessCriticality = Category::where('name', 'Business & Operational Criticality')->first();
        $sensitiveData = Category::where('name', 'Sensitive Data')->first();
        $techIntegration = Category::where('name', 'Technology Integration')->first();

        // Data pertanyaan berdasarkan sheet
        $questions = [
            // Informasi Umum - Sub: Umum
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Nama legal perusahaan',
                'question_type' => 'isian',
                'clue' => 'Nama legal perusahaan',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Website perusahaan',
                'question_type' => 'isian',
                'clue' => 'Website perusahaan',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Tanggal berdiri perusahaan',
                'question_type' => 'isian',
                'clue' => 'Tanggal berdiri perusahaan',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Lokasi perusahaan (kantor utama)',
                'question_type' => 'isian',
                'clue' => 'Lokasi kantor utama perusahaan',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Jenis Bisnis (Pilih salah satu)',
                'question_type' => 'pilihan',
                'clue' => null,
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            // Tambahkan opsi untuk jenis bisnis
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Struktur organisasi perusahaan',
                'question_type' => 'isian',
                'clue' => 'Struktur organisasi perusahaan',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Penanggung jawab perusahaan (CEO)',
                'question_type' => 'isian',
                'clue' => 'Nama pemimpin tertinggi di perusahaan (Level CEO)',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Jumlah karyawan IT',
                'question_type' => 'isian',
                'clue' => 'Total jumlah karyawan IT',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            [
                'category_id' => $infoUmum->id,
                'sub' => 'Umum',
                'question_text' => 'Apakah perusahaan telah terdaftar di OJK / BI (Apabila perusahaan merupakan Tekfin)',
                'question_type' => 'pilihan',
                'clue' => 'Dijawab Ya atau Tidak',
                'indicator' => json_encode(['high']),
                'attachment_text' => null,
            ],
            // Business & Operational Criticality questions
            [
                'category_id' => $businessCriticality->id,
                'sub' => 'Operasional',
                'question_text' => 'Apakah sistem mendukung layanan utama bank?',
                'question_type' => 'pilihan',
                'clue' => null,
                'indicator' => json_encode(['high']),
                'attachment_text' => 'Bukti dokumentasi sistem',
            ],
            // Sensitive Data questions
            [
                'category_id' => $sensitiveData->id,
                'sub' => 'Data',
                'question_text' => 'Apakah sistem mengelola data PII (Personally Identifiable Information)?',
                'question_type' => 'pilihan',
                'clue' => null,
                'indicator' => json_encode(['high']),
                'attachment_text' => 'Dokumen klasifikasi data',
            ],
            // Technology Integration questions
            [
                'category_id' => $techIntegration->id,
                'sub' => 'Integrasi',
                'question_text' => 'Apakah sistem terhubung dengan sistem kritikal bank?',
                'question_type' => 'pilihan',
                'clue' => null,
                'indicator' => json_encode(['high']),
                'attachment_text' => 'Diagram arsitektur sistem',
            ],
        ];

        foreach ($questions as $question) {
            $q = Question::create($question);
            
            // Tambahkan opsi untuk pertanyaan pilihan
            if ($question['question_type'] === 'pilihan') {
                if ($question['question_text'] === 'Jenis Bisnis (Pilih salah satu)') {
                    $options = [
                        ['option_text' => 'Perbankan', 'score' => 3],
                        ['option_text' => 'Fintech', 'score' => 2],
                        ['option_text' => 'Teknologi', 'score' => 1],
                        ['option_text' => 'Lainnya', 'score' => 0],
                    ];
                } elseif ($question['question_text'] === 'Apakah perusahaan telah terdaftar di OJK / BI (Apabila perusahaan merupakan Tekfin)') {
                    $options = [
                        ['option_text' => 'Ya', 'score' => 1],
                        ['option_text' => 'Tidak', 'score' => 0],
                    ];
                } else {
                    $options = [
                        ['option_text' => 'Ya', 'score' => 1],
                        ['option_text' => 'Tidak', 'score' => 0],
                        ['option_text' => 'Sebagian', 'score' => 0.5],
                    ];
                }
                
                foreach ($options as $option) {
                    $q->options()->create($option);
                }
            }
        }

        $this->command->info('Question data seeded successfully!');
    }
}