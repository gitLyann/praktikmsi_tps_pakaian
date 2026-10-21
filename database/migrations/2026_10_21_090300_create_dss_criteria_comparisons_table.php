<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matriks perbandingan pairwise antar kriteria (AHP).
     *
     * Setiap pasangan disimpan dua arah (a->b dan b->a) supaya matriks
     * bisa langsung dibangun tanpa perlu membalik nilai saat render.
     * Nilai memakai skala Saaty 1-9 (1 = sama penting, 9 = sangat lebih penting).
     */
    public function up(): void
    {
        Schema::create('dss_criteria_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_a_id')->constrained('dss_criteria')->cascadeOnDelete();
            $table->foreignId('criterion_b_id')->constrained('dss_criteria')->cascadeOnDelete();
            $table->decimal('value', 10, 4);
            $table->timestamps();

            $table->unique(['criterion_a_id', 'criterion_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_criteria_comparisons');
    }
};
