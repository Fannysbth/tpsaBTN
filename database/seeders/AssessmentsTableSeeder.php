<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('assessments')->delete();
        
        DB::table('assessments')->insert(array (
            0 => 
            array (
                'id' => 2,
                'company_name' => 'CV. Sejahtera Abadi',
                'assessment_date' => '2026-02-03',
                'total_score' => '40.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":0.5,"max_score":1,"score":50},"2":{"indicator":"high","actual_score":2,"max_score":4,"score":50},"3":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"medium","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            1 => 
            array (
                'id' => 3,
                'company_name' => 'PT. Teknologi Indonesia',
                'assessment_date' => '2026-02-02',
                'total_score' => '66.67',
                'risk_level' => 'medium',
                'category_scores' => '{"1":{"indicator":"low","actual_score":1,"max_score":1,"score":100},"2":{"indicator":"medium","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"4":{"indicator":"high","actual_score":4,"max_score":4,"score":100},"5":{"indicator":"high","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan cukup baik. Terdapat isu ketidakpatuhan namun masih dalam batas toleransi Bank.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            2 => 
            array (
                'id' => 4,
                'company_name' => 'UD. Jaya Makmur',
                'assessment_date' => '2026-02-01',
                'total_score' => '40.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"2":{"indicator":"medium","actual_score":3,"max_score":3,"score":100},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            3 => 
            array (
                'id' => 5,
                'company_name' => 'PT. Global Solution',
                'assessment_date' => '2026-01-31',
                'total_score' => '50.00',
                'risk_level' => 'medium',
                'category_scores' => '{"1":{"indicator":"low","actual_score":0.5,"max_score":1,"score":50},"2":{"indicator":"high","actual_score":4,"max_score":4,"score":100},"3":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"high","actual_score":4,"max_score":4,"score":100},"5":{"indicator":"high","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan cukup baik. Terdapat isu ketidakpatuhan namun masih dalam batas toleransi Bank.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            4 => 
            array (
                'id' => 6,
                'company_name' => 'PT. Sinar Jaya 1',
                'assessment_date' => '2026-01-04',
                'total_score' => '15.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"low","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"high","actual_score":2,"max_score":4,"score":50},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"high","actual_score":1,"max_score":4,"score":25},"5":{"indicator":"medium","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            5 => 
            array (
                'id' => 7,
                'company_name' => 'CV. Anugerah 1',
                'assessment_date' => '2026-01-05',
                'total_score' => '25.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"high","actual_score":1,"max_score":4,"score":25},"3":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"4":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            6 => 
            array (
                'id' => 8,
                'company_name' => 'PT. Mandiri Sejahtera 1',
                'assessment_date' => '2026-01-06',
                'total_score' => '26.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"low","actual_score":1,"max_score":1,"score":100},"2":{"indicator":"low","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"high","actual_score":0,"max_score":4,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            7 => 
            array (
                'id' => 9,
                'company_name' => 'UD. Berkah 1',
                'assessment_date' => '2026-01-07',
                'total_score' => '6.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"low","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"4":{"indicator":"high","actual_score":0,"max_score":4,"score":0},"5":{"indicator":"high","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            8 => 
            array (
                'id' => 10,
                'company_name' => 'PT. Prima Utama 1',
                'assessment_date' => '2026-01-08',
                'total_score' => '16.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"medium","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"high","actual_score":0.5,"max_score":1,"score":50},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            9 => 
            array (
                'id' => 11,
                'company_name' => 'PT. Sinar Jaya 2',
                'assessment_date' => '2025-12-04',
                'total_score' => '20.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"low","actual_score":3,"max_score":3,"score":100},"3":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"high","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            10 => 
            array (
                'id' => 12,
                'company_name' => 'CV. Anugerah 2',
                'assessment_date' => '2025-12-05',
                'total_score' => '6.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"medium","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            11 => 
            array (
                'id' => 13,
                'company_name' => 'PT. Mandiri Sejahtera 2',
                'assessment_date' => '2025-12-06',
                'total_score' => '26.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"low","actual_score":1,"max_score":1,"score":100},"2":{"indicator":"medium","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            12 => 
            array (
                'id' => 14,
                'company_name' => 'UD. Berkah 2',
                'assessment_date' => '2025-12-07',
                'total_score' => '20.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"low","actual_score":3,"max_score":3,"score":100},"3":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"4":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"high","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            13 => 
            array (
                'id' => 15,
                'company_name' => 'PT. Prima Utama 2',
                'assessment_date' => '2025-12-08',
                'total_score' => '6.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"low","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"medium","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            14 => 
            array (
                'id' => 1,
                'company_name' => 'PT. Maju Bersama',
                'assessment_date' => '2026-02-04',
                'total_score' => '6.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"low","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-05 09:05:46',
            ),
            15 => 
            array (
                'id' => 16,
                'company_name' => 'PT. Sinar Jaya 3',
                'assessment_date' => '2025-11-04',
                'total_score' => '45.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"2":{"indicator":"medium","actual_score":3,"max_score":3,"score":100},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"high","actual_score":1,"max_score":4,"score":25},"5":{"indicator":"medium","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            16 => 
            array (
                'id' => 17,
                'company_name' => 'CV. Anugerah 3',
                'assessment_date' => '2025-11-05',
                'total_score' => '37.50',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"high","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"high","actual_score":3.5,"max_score":4,"score":87.5},"3":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"4":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            17 => 
            array (
                'id' => 18,
                'company_name' => 'PT. Mandiri Sejahtera 3',
                'assessment_date' => '2025-11-06',
                'total_score' => '16.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"low","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"medium","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"high","actual_score":0.5,"max_score":1,"score":50},"4":{"indicator":"low","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            18 => 
            array (
                'id' => 19,
                'company_name' => 'UD. Berkah 3',
                'assessment_date' => '2025-11-07',
                'total_score' => '15.00',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"high","actual_score":3,"max_score":4,"score":75},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"medium","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            19 => 
            array (
                'id' => 20,
                'company_name' => 'PT. Prima Utama 3',
                'assessment_date' => '2025-11-08',
                'total_score' => '6.67',
                'risk_level' => 'high',
                'category_scores' => '{"1":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"2":{"indicator":"low","actual_score":1,"max_score":3,"score":33.33},"3":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"4":{"indicator":"medium","actual_score":0,"max_score":0,"score":0},"5":{"indicator":"low","actual_score":0,"max_score":0,"score":0}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan buruk.',
                'created_at' => '2026-02-04 08:10:37',
                'updated_at' => '2026-02-04 08:10:37',
            ),
            20 => 
            array (
                'id' => 26,
                'company_name' => 'hjhj',
                'assessment_date' => '2026-02-06',
                'total_score' => '68.75',
                'risk_level' => 'medium',
                'category_scores' => '{"1":{"indicator":"low","actual_score":0,"max_score":1,"score":0},"2":{"indicator":"medium","actual_score":3,"max_score":3,"score":100},"3":{"indicator":"high","actual_score":1,"max_score":1,"score":100},"4":{"indicator":"high","actual_score":3,"max_score":4,"score":75}}',
                'notes' => 'Kepatuhan terhadap regulasi dan standar keamanan cukup baik. Terdapat isu ketidakpatuhan namun masih dalam batas toleransi Bank.',
                'created_at' => '2026-02-06 03:10:33',
                'updated_at' => '2026-02-06 03:13:30',
            ),
            21 => 
            array (
                'id' => 27,
                'company_name' => 'coba',
                'assessment_date' => '2026-02-11',
                'total_score' => '0.00',
                'risk_level' => NULL,
                'category_scores' => '{"1":{"score":0,"indicator":"high","actual_score":0,"max_score":0},"2":{"score":0,"indicator":"medium","actual_score":0,"max_score":0},"3":{"score":0,"indicator":"low","actual_score":0,"max_score":0},"4":{"score":0,"indicator":"umum","actual_score":0,"max_score":0}}',
                'notes' => NULL,
                'created_at' => '2026-02-11 01:52:54',
                'updated_at' => '2026-02-11 01:52:54',
            ),
        ));
        
        
    }
}