<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionOptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('question_options')->delete();
        
        DB::table('question_options')->insert(array (
            0 => 
            array (
                'id' => 356,
                'question_id' => 44,
                'option_text' => 'Ya',
                'score' => '1.00',
                'created_at' => '2026-02-11 02:56:46',
                'updated_at' => '2026-02-11 02:56:46',
            ),
            1 => 
            array (
                'id' => 357,
                'question_id' => 44,
                'option_text' => 'Tidak',
                'score' => '3.00',
                'created_at' => '2026-02-11 02:56:46',
                'updated_at' => '2026-02-11 02:56:46',
            ),
            2 => 
            array (
                'id' => 325,
                'question_id' => 5,
            'option_text' => 'IT / Teknologi Informasi (namun tidak terbatas pada)',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            3 => 
            array (
                'id' => 326,
                'question_id' => 5,
            'option_text' => 'Business Process Outsourcing (BPO) / Operational Support',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            4 => 
            array (
                'id' => 327,
                'question_id' => 5,
                'option_text' => 'Financial & Payment Services',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            5 => 
            array (
                'id' => 328,
                'question_id' => 5,
                'option_text' => 'Information & Data Service',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            6 => 
            array (
                'id' => 329,
                'question_id' => 5,
                'option_text' => 'HR, Training & Professional Services',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            7 => 
            array (
                'id' => 330,
                'question_id' => 5,
                'option_text' => 'Non-IT / Non-Core Support Services',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            8 => 
            array (
                'id' => 331,
                'question_id' => 5,
                'option_text' => 'Marketing & Communication',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            9 => 
            array (
                'id' => 332,
                'question_id' => 5,
                'option_text' => 'Hardware & Equipment Provider',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            10 => 
            array (
                'id' => 333,
                'question_id' => 5,
                'option_text' => 'Legal & Compliance Related Services',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            11 => 
            array (
                'id' => 334,
                'question_id' => 5,
                'option_text' => 'Others',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            12 => 
            array (
                'id' => 335,
                'question_id' => 9,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            13 => 
            array (
                'id' => 336,
                'question_id' => 9,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            14 => 
            array (
                'id' => 337,
                'question_id' => 43,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            15 => 
            array (
                'id' => 338,
                'question_id' => 43,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            16 => 
            array (
                'id' => 339,
                'question_id' => 32,
                'option_text' => 'a. 24 jam x 7 hari',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            17 => 
            array (
                'id' => 340,
                'question_id' => 32,
                'option_text' => 'b. 24 jam x 5 hari',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            18 => 
            array (
                'id' => 341,
                'question_id' => 32,
                'option_text' => 'c. 8 jam x 5 hari',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            19 => 
            array (
                'id' => 342,
                'question_id' => 32,
            'option_text' => 'd. Lainnya (mohon disebutkan)',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            20 => 
            array (
                'id' => 343,
                'question_id' => 34,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            21 => 
            array (
                'id' => 344,
                'question_id' => 34,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            22 => 
            array (
                'id' => 345,
                'question_id' => 35,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            23 => 
            array (
                'id' => 346,
                'question_id' => 35,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            24 => 
            array (
                'id' => 347,
                'question_id' => 36,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            25 => 
            array (
                'id' => 348,
                'question_id' => 36,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            26 => 
            array (
                'id' => 349,
                'question_id' => 39,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            27 => 
            array (
                'id' => 350,
                'question_id' => 39,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            28 => 
            array (
                'id' => 351,
                'question_id' => 40,
                'option_text' => 'Ya',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            29 => 
            array (
                'id' => 352,
                'question_id' => 40,
                'option_text' => 'Tidak',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            30 => 
            array (
                'id' => 353,
                'question_id' => 41,
                'option_text' => '1-2 Kali',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            31 => 
            array (
                'id' => 354,
                'question_id' => 41,
                'option_text' => '3-4 Kali',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            32 => 
            array (
                'id' => 355,
                'question_id' => 41,
                'option_text' => '>5 Kali',
                'score' => '0.00',
                'created_at' => '2026-02-11 02:12:11',
                'updated_at' => '2026-02-11 02:12:11',
            ),
        ));
        
        
    }
}