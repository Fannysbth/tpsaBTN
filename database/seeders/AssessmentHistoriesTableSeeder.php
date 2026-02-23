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

            [
                'assessment_id' => 2,
                'change_type' => 'status',
                'old_value' => 'active',
                'new_value' => 'inactive',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],

            [
                'assessment_id' => 2,
                'change_type' => 'tier',
                'old_value' => '3',
                'new_value' => '2',
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ],

            [
                'assessment_id' => 3,
                'change_type' => 'result',
                'old_value' => '45 (high)',
                'new_value' => '65 (medium)',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],

        ]);

        // Tambahan history random
        for ($i = 4; $i <= 10; $i++) {
            DB::table('assessment_histories')->insert([
                'assessment_id' => $i,
                'change_type' => 'result',
                'old_value' => rand(30,60).' (high)',
                'new_value' => rand(60,90).' (medium)',
                'created_at' => $now->copy()->subDays(rand(1,10)),
                'updated_at' => $now,
            ]);
        }
    }
}