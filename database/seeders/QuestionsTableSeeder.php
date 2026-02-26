<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Category;

class QuestionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel (reset auto increment)
        Question::truncate();

        // Ambil category berdasarkan nama (LEBIH AMAN)
        $informasiUmum = Category::where('name', 'Umum')->first();
        $pengamananData = Category::where('name', 'Pengamanan Data')->first();

        if (!$informasiUmum) {
            $this->command->error('Category Informasi Umum tidak ditemukan!');
            return;
        }

        Question::create([
            'category_id'   => $informasiUmum->id,
            'question_no'   => '1',
            'order_index'   => 1,
            'question_text' => 'Lokasi perusahaan (kantor utama)',
            'question_type' => 'isian',
            'clue'          => 'Lokasi kantor utama perusahaan',
            'has_attachment'=> true,
            'indicator'     => ['umum'], // <-- JSON asli (bukan string)
            'is_active'     => true,
            'attachment_text' => null,
            'sub'           => 'Informasi Umum',
        ]);

        Question::create([
            'category_id'   => $informasiUmum->id,
            'question_no'   => '2',
            'order_index'   => 2,
            'question_text' => 'Jenis Bisnis (Pilih salah satu)',
            'question_type' => 'pilihan',
            'clue'          => null,
            'has_attachment'=> true,
            'indicator'     => ['umum'],
            'is_active'     => true,
            'attachment_text' => null,
            'sub'           => 'Informasi Umum',
        ]);

        // Contoh kategori lain
        if ($pengamananData) {
            Question::create([
                'category_id'   => $pengamananData->id,
                'question_no'   => '1',
                'order_index'   => 1,
                'question_text' => 'Apakah Perusahaan menyimpan data credential milik BTN?',
                'question_type' => 'pilihan',
                'clue'          => null,
                'has_attachment'=> false,
                'indicator'     => ['high', 'medium', 'low'],
                'is_active'     => true,
                'attachment_text' => null,
                'sub'           => 'Pengamanan Data',
            ]);
        }
    }
}