<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Keuangan',
                'criteria' => [
                    'high' => 'Risiko tinggi pada aspek keuangan',
                    'medium' => 'Risiko sedang pada aspek keuangan',
                    'low' => 'Risiko rendah pada aspek keuangan',
                ],
            ],
            [
                'name' => 'Operasional',
                'criteria' => [
                    'high' => 'Risiko tinggi pada proses operasional',
                    'medium' => 'Risiko sedang pada proses operasional',
                    'low' => 'Risiko rendah pada proses operasional',
                ],
            ],
            [
                'name' => 'Sumber Daya Manusia',
                'criteria' => [
                    'high' => 'Risiko tinggi pada pengelolaan SDM',
                    'medium' => 'Risiko sedang pada pengelolaan SDM',
                    'low' => 'Risiko rendah pada pengelolaan SDM',
                ],
            ],
            [
                'name' => 'Teknologi Informasi',
                'criteria' => [
                    'high' => 'Risiko tinggi pada sistem IT',
                    'medium' => 'Risiko sedang pada sistem IT',
                    'low' => 'Risiko rendah pada sistem IT',
                ],
            ],
            [
                'name' => 'Kepatuhan',
                'criteria' => [
                    'high' => 'Risiko tinggi pada kepatuhan regulasi',
                    'medium' => 'Risiko sedang pada kepatuhan regulasi',
                    'low' => 'Risiko rendah pada kepatuhan regulasi',
                ],
            ],
            [
                'name' => 'Manajemen Risiko',
                'criteria' => [
                    'high' => 'Risiko tinggi pada manajemen risiko',
                    'medium' => 'Risiko sedang pada manajemen risiko',
                    'low' => 'Risiko rendah pada manajemen risiko',
                ],
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Category data seeded successfully!');
    }
}
