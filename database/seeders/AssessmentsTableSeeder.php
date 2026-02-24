<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssessmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('assessments')->truncate();

        $now = Carbon::now();

        for ($i = 1; $i <= 15; $i++) {

            $score = rand(30, 95);
            $risk  = $this->determineRisk($score);
            $tier  = $this->determineTier();

            DB::table('assessments')->insert([
                'company_name'     => $i === 1 ? 'PT. Maju Bersama' :
                                      ($i === 2 ? 'CV. Sejahtera Abadi' : 'PT. Vendor '.$i),

                'assessor'         => $i === 1 ? 'Budi Santoso' :
                                      ($i === 2 ? 'Siti Aminah' : 'Assessor '.$i),

                'assessment_date'  => now()->subDays($i),
                'evaluated_at'     => now()->subDays($i - 1),

                'total_score'      => $score,
                'risk_level'       => $risk,
                'tier_criticality' => $tier,
                'vendor_status'    => $i % 4 === 0 ? 'inactive' : 'active',

                'category_scores'  => json_encode($this->generateCategoryScores()),

                'notes'            => 'Generated seed data.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    private function determineRisk($score): string
    {
        if ($score >= 80) return 'low';
        if ($score >= 50) return 'medium';
        return 'high';
    }

    private function determineTier(): string
    {
        return match (rand(1,3)) {
            1 => 'Tier 1',
            2 => 'Tier 2',
            default => 'Tier 3',
        };
    }

    private function generateCategoryScores(): array
    {
        return [
            1 => [
                'indicator'     => 'low',
                'actual_score'  => rand(40, 80),
                'max_score'     => 100,
                'score'         => rand(40, 90),
                'justification' => 'Risiko operasional rendah, kontrol internal memadai.'
            ],
            2 => [
                'indicator'     => 'medium',
                'actual_score'  => rand(50, 85),
                'max_score'     => 100,
                'score'         => rand(50, 85),
                'justification' => 'Terdapat beberapa gap minor namun masih dalam toleransi.'
            ],
            3 => [
                'indicator'     => 'high',
                'actual_score'  => rand(60, 95),
                'max_score'     => 100,
                'score'         => rand(60, 95),
                'justification' => 'Vendor memiliki akses ke sistem kritikal dan data sensitif.'
            ],
        ];
    }
}