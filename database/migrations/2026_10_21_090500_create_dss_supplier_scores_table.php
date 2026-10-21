<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skor tiap pemasok terhadap tiap kriteria pada satu run analisis VIKOR.
     *
     * score  = nilai mentah pada kriteria tsb (mis. harga beli, lead time, rating).
     *          Arah "baik/tidak" ditentukan oleh `dss_criteria.type` saat
     *          perhitungan VIKOR: benefit = makin besar makin baik,
     *          cost = makin kecil makin baik.
     * weight = bobot kriteria hasil AHP yang dipakai saat analisis berjalan
     */
    public function up(): void
    {
        Schema::create('dss_supplier_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dss_run_id')->constrained('dss_runs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('dss_criterion_id')->constrained('dss_criteria')->cascadeOnDelete();
            $table->decimal('score', 6, 2);
            $table->decimal('weight', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(
                ['dss_run_id', 'supplier_id', 'dss_criterion_id'],
                'dss_supplier_scores_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_supplier_scores');
    }
};
