<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('migrations')->delete();
        
        DB::table('migrations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'migration' => '2024_01_01_000001_create_categories_table',
                'batch' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'migration' => '2024_01_01_000002_create_questions_table',
                'batch' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'migration' => '2024_01_01_000003_create_question_options_table',
                'batch' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'migration' => '2024_01_01_000004_create_assessments_table',
                'batch' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'migration' => '2024_01_01_000005_create_answers_table',
                'batch' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'migration' => '2026_01_08_094846_create_cache_table',
                'batch' => 1,
            ),
        ));
        
        
    }
}