<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama jika ada
        Category::truncate();

        // Data kategori berdasarkan sheet
        $categories = [
            [
                'name' => 'Business & Operational Criticality',
                'criteria' => [
                    'high' => 'Berpengaruh langsung pada layanan utama bank (core banking, mobile app, transaksi, compliance, dll)',
                    'medium' => 'Mendukung proses penting tapi bukan layanan utama (admin, support, dll)',
                    'low' => 'Hanya mendukung proses minor atau tidak berpengaruh langsung',
                ],
            ],
            [
                'name' => 'Sensitive Data',
                'criteria' => [
                    'high' => 'Mengelola data sensitive PII atau akses ke production level administrator',
                    'medium' => 'Mengelola data sensitive non PII atau akses ke production level non administrator',
                    'low' => 'Tidak mengelola data sensitive Bank atau Tidak memiliki akses ke production',
                ],
            ],
            [
                'name' => 'Technology Integration',
                'criteria' => [
                    'high' => 'Terhubung ke sistem kritikal',
                    'medium' => 'Terhubung ke sistem non-kritikal atau batch-based',
                    'low' => 'Tidak ada interkoneksi',
                ],
            ],
            [
                'name' => 'Informasi Umum',
                'criteria' => [
                    'high' => 'Informasi kritis dan sensitif',
                    'medium' => 'Informasi penting tetapi tidak kritis',
                    'low' => 'Informasi umum yang tersedia publik',
                ],
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Category data seeded successfully!');
    }
}