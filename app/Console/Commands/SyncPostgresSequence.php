<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPostgresSequence extends Command
{
    protected $signature = 'app:sync-postgres-sequence';
    protected $description = 'Sync PostgreSQL sequences with table max id';

    public function handle()
    {
        $tables = [
            'answers',
            'assessment_histories',
            'assessments',
            'categories',
            'question_options',
            'questions'
        ];

        foreach ($tables as $table) {

            $sequence = $table . '_id_seq';

            $maxId = DB::table($table)->max('id') ?? 0;

            DB::statement("
                SELECT setval('$sequence', GREATEST((SELECT MAX(id) FROM $table), 1))
            ");

            $this->info("Synced sequence: $sequence");
        }

        $this->info("Sequence sync completed!");

        return 0;
    }
}