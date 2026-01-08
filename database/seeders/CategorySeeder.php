<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Keuangan',
                'description' => 'Penilaian terkait aspek keuangan perusahaan',
                'weight' => 3,
            ],
            [
                'name' => 'Operasional',
                'description' => 'Penilaian terkait proses operasional perusahaan',
                'weight' => 2,
            ],
            [
                'name' => 'Sumber Daya Manusia',
                'description' => 'Penilaian terkait pengelolaan SDM',
                'weight' => 2,
            ],
            [
                'name' => 'Teknologi Informasi',
                'description' => 'Penilaian terkait sistem dan infrastruktur IT',
                'weight' => 3,
            ],
            [
                'name' => 'Kepatuhan',
                'description' => 'Penilaian terkait kepatuhan terhadap regulasi',
                'weight' => 4,
            ],
            [
                'name' => 'Manajemen Risiko',
                'description' => 'Penilaian terkait sistem manajemen risiko',
                'weight' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Category data seeded successfully!');
    }
}