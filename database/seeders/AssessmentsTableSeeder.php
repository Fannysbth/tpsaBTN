<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssessmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('assessments')->delete();

        $now = Carbon::now();

        DB::table('assessments')->insert([

            [
                'id' => 1,
                'company_name' => 'PT. Maju Bersama',
                'assessor' => 'Budi Santoso',
                'assessment_date' => '2026-02-04',
                'evaluated_at' => '2026-02-05',
                'total_score' => 85.00,
                'risk_level' => 'low',
                'tier_criticality' => 1,
                'vendor_status' => 'active',
                'category_scores' => json_encode([
                    1 => ['indicator'=>'high','score'=>90],
                    2 => ['indicator'=>'high','score'=>85],
                    3 => ['indicator'=>'medium','score'=>80],
                ]),
                'notes' => 'Kepatuhan sangat baik.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 2,
                'company_name' => 'CV. Sejahtera Abadi',
                'assessor' => 'Siti Aminah',
                'assessment_date' => '2026-02-03',
                'evaluated_at' => '2026-02-04',
                'total_score' => 65.00,
                'risk_level' => 'medium',
                'tier_criticality' => 2,
                'vendor_status' => 'active',
                'category_scores' => json_encode([
                    1 => ['indicator'=>'medium','score'=>70],
                    2 => ['indicator'=>'low','score'=>60],
                    3 => ['indicator'=>'medium','score'=>65],
                ]),
                'notes' => 'Cukup baik.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

        ]);

        // Tambah 13 perusahaan random variasi
        for ($i = 3; $i <= 15; $i++) {

            $score = rand(30,95);

            if ($score >= 80) {
                $risk = 'low';
            } elseif ($score >= 50) {
                $risk = 'medium';
            } else {
                $risk = 'high';
            }

            DB::table('assessments')->insert([
                'id' => $i,
                'company_name' => 'PT. Vendor '.$i,
                'assessor' => 'Assessor '.$i,
                'assessment_date' => now()->subDays($i),
                'evaluated_at' => now()->subDays($i-1),
                'total_score' => $score,
                'risk_level' => $risk,
                'tier_criticality' => rand(1,3),
                'vendor_status' => $i % 4 == 0 ? 'inactive' : 'active',
                'category_scores' => json_encode([
                    1 => ['indicator'=>'low','score'=>rand(40,90)],
                    2 => ['indicator'=>'medium','score'=>rand(40,90)],
                    3 => ['indicator'=>'high','score'=>rand(40,90)],
                ]),
                'notes' => 'Generated data.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}