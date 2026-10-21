<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memperlebar presisi kolom `dss_supplier_scores.score`.
     *
     * Kolom awal `decimal(6,2)` hanya muat sampai 9.999,99, padahal skor harga
     * beli memakai nilai mentah rupiah (mis. 150.000). Kolom dilebar ke
     * `decimal(12,2)` supaya harga beli, lead time, dan rating muat tanpa
     * penyesuaian satuan.
     */
    public function up(): void
    {
        Schema::table('dss_supplier_scores', function (Blueprint $table) {
            $table->decimal('score', 12, 2)->change();
        });
    }

    /**
     * Kembalikan kolom ke presisi semula.
     */
    public function down(): void
    {
        Schema::table('dss_supplier_scores', function (Blueprint $table) {
            $table->decimal('score', 6, 2)->change();
        });
    }
};
