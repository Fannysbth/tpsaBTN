<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('categories')->delete();
        
        DB::table('categories')->insert(array (
            0 => 
            array (
                'id' => 0,
                'name' => 'Umum',
                'criteria' => '{"high":"-","medium":"-","low":"-"}',
                'created_at' => '2026-01-20 08:00:05',
                'updated_at' => '2026-02-11 02:12:11',
            ),
            1 => 
            array (
                'id' => 1,
                'name' => 'Business & Operational Criticality',
            'criteria' => '{"high":"Berpengaruh langsung pada layanan utama bank (core banking, mobile app, transaksi, compliance, dll)","medium":"Mendukung proses penting tapi bukan layanan utama (admin, support, dll)","low":"Hanya mendukung proses minor atau tidak berpengaruh langsung"}',
                'created_at' => '2026-02-11 02:52:46',
                'updated_at' => '2026-02-11 02:52:46',
            ),
            2 => 
            array (
                'id' => 2,
                'name' => 'Sensitive Data',
                'criteria' => '{"high":"Mengelola data sensitive PII atau akses ke production level administrator","medium":"Mengelola data sensitive non PII atau akses ke production level non administrator","low":"Tidak mengelola data sensitive Bank atau Tidak memiliki akses ke production"}',
                'created_at' => '2026-02-11 02:56:46',
                'updated_at' => '2026-02-11 02:56:46',
            ),
            3 => 
            array (
                'id' => 3,
                'name' => 'Technology Integration',
                'criteria' => '{"high":"Terhubung ke sistem kritikal","medium":"Terhubung ke sistem non-kritikal atau batch-based","low":"Tidak ada interkoneksi"}',
                'created_at' => '2026-02-11 02:57:31',
                'updated_at' => '2026-02-11 02:57:31',
            ),
        ));
        
        
    }
}