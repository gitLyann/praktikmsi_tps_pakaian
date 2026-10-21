<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kriteria penilaian DSS. Dipakai untuk matriks perbandingan AHP
     * dan sebagai kolom penilaian VIKOR terhadap tiap pemasok.
     *
     * type = benefit  -> nilai lebih besar lebih baik (mis. kualitas)
     * type = cost     -> nilai lebih kecil lebih baik (mis. harga, lead time)
     */
    public function up(): void
    {
        Schema::create('dss_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('type', ['benefit', 'cost'])->default('benefit');

            // Bobot hasil perhitungan AHP terakhir (bisa diisi manual/di-override).
            $table->decimal('weight', 8, 4)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_criteria');
    }
};
