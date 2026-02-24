<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // jenis perubahan
            $table->enum('change_type', ['status', 'tier', 'result']);

            // nilai lama & baru
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_histories');
    }
};