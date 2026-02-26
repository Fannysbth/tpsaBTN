<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssessmentHistoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('assessment_histories')->delete();

        $now = Carbon::now();

        DB::table('assessment_histories')->insert([

            // =========================
            // STATUS CHANGE
            // =========================
            [
                'assessment_id' => 2,
                'change_type'   => 'status',
                'old_value'     => json_encode([
                    'status' => 'active',
                ]),
                'new_value'     => json_encode([
                    'status' => 'inactive',
                ]),
                'created_at'    => $now->copy()->subDays(5),
                'updated_at'    => $now->copy()->subDays(5),
            ],

            // =========================
            // TIER CHANGE
            // =========================
            [
                'assessment_id' => 2,
                'change_type'   => 'tier',
                'old_value'     => json_encode([
                    'tier_name'  => 'Tier 3',
                    'indicators' => [
                        'finance'     => 60,
                        'operations'  => 55,
                        'compliance'  => 50,
                    ],
                ]),
                'new_value'     => json_encode([
                    'tier_name'  => 'Tier 2',
                    'indicators' => [
                        'finance'     => 75,
                        'operations'  => 70,
                        'compliance'  => 68,
                    ],
                ]),
                'created_at'    => $now->copy()->subDays(4),
                'updated_at'    => $now->copy()->subDays(4),
            ],

            // =========================
            // RESULT CHANGE
            // =========================
            [
                'assessment_id' => 3,
                'change_type'   => 'result',
                'old_value'     => json_encode([
                    'score'       => 45,
                    'risk_level'  => 'high',
                ]),
                'new_value'     => json_encode([
                    'score'       => 65,
                    'risk_level'  => 'medium',
                ]),
                'created_at'    => $now->copy()->subDays(3),
                'updated_at'    => $now->copy()->subDays(3),
            ],

        ]);

        // =========================
        // RANDOM RESULT HISTORY
        // =========================
        for ($i = 4; $i <= 10; $i++) {

            $oldScore = rand(30, 60);
            $newScore = rand(60, 90);

            DB::table('assessment_histories')->insert([
                'assessment_id' => $i,
                'change_type'   => 'result',
                'old_value'     => json_encode([
                    'score'      => $oldScore,
                    'risk_level' => 'high',
                ]),
                'new_value'     => json_encode([
                    'score'      => $newScore,
                    'risk_level' => 'medium',
                ]),
                'created_at'    => $now->copy()->subDays(rand(1, 10)),
                'updated_at'    => $now,
            ]);
        }
    }
}