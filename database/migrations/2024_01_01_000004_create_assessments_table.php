<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();

            // Company
            $table->string('company_name');

            // Assessor
            $table->string('assessor')->nullable();

            // Tanggal assessment (tanggal dokumen)
            $table->date('assessment_date')->nullable();

            // Tanggal dinilai / upload excel
            $table->date('evaluated_at')->nullable();

            // Score
            $table->decimal('total_score', 5, 2)->nullable();

            // Risk Level (hasil dari score)
            $table->enum('risk_level', ['high', 'medium', 'low'])->nullable();

            // Tier Criticality (1,2,3)
            $table->unsignedTinyInteger('tier_criticality')->nullable();

            // Status Vendor
            $table->enum('vendor_status', ['active', 'inactive'])
                  ->default('active');

            // Category + indicator + nilai (JSON)
            $table->json('category_scores')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};