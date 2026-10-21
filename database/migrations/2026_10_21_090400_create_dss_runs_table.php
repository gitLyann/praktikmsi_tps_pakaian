<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu eksekusi analisis DSS (AHP / VIKOR / restock).
     *
     * Header hasil perhitungan disimpan di sini agar riwayat analisis
     * tetap bisa dibaca ulang tanpa harus menjalankan perhitungan ulang.
     */
    public function up(): void
    {
        Schema::create('dss_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('type', ['ahp', 'vikor', 'restock'])->default('ahp');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Ringkasan konsistensi AHP
            $table->unsignedTinyInteger('criteria_count')->nullable();
            $table->decimal('lambda_max', 12, 6)->nullable();
            $table->decimal('ci', 12, 6)->nullable();
            $table->decimal('cr', 12, 6)->nullable();
            $table->decimal('ri', 12, 6)->nullable();
            $table->boolean('is_consistent')->nullable();

            // Rincian matriks, bobot, dan ranking dalam bentuk JSON.
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_runs');
    }
};
