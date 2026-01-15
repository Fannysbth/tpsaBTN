<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->date('assessment_date');
            $table->decimal('total_score', 5, 2)->nullable(); // ubah dari integer ke decimal
            $table->enum('risk_level', ['high', 'medium', 'low'])->nullable();
            $table->json('category_scores')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
};
