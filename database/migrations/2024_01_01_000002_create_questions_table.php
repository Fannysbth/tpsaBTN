<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // 🔢 NOMOR SOAL DARI EXCEL (1, 8a, 10b, dll)
            $table->string('question_no');

            // 🧭 URUTAN TAMPIL (IKUT EXCEL / DRAG & DROP)
            $table->integer('order_index')->default(0);

            $table->text('question_text');

            $table->enum('question_type', ['pilihan', 'isian'])
                ->default('pilihan');

            $table->json('indicator')->nullable();

            $table->text('clue')->nullable();

            $table->boolean('has_attachment')->default(false);

            $table->text('attachment_text')->nullable();

            $table->boolean('is_active')->default(true);

            // optional grouping (kalau memang masih dipakai)
            $table->string('sub')->nullable();

            $table->timestamps();

            // 🚀 PENTING: NO TIDAK BOLEH DUPLIKAT DALAM 1 CATEGORY
            $table->unique(['category_id', 'question_no']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('questions');
    }
};
