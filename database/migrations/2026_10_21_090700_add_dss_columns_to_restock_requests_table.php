<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     *Menyambungkan pengajuan restock ke data DSS: pemasok yang dipilih
     * dan run analisis yang menjadi dasar rekomendasi.
     *
     * Catatan driver:
     * - Kolom + index bisa ditambahkan di semua driver (SQLite & MySQL).
     * - Constraint FK hanya bisa ditambahkan di MySQL. SQLite tidak mendukung
     *   penambahan FK ke tabel yang sudah ada (ALTER TABLE ... ADD CONSTRAINT),
     *   jadi di SQLite kolom sengaja dibiarkan tanpa constraint FK.
     */
    public function up(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('employee_id');
            $table->unsignedBigInteger('dss_run_id')->nullable()->after('supplier_id');
            $table->index('supplier_id');
            $table->index('dss_run_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('restock_requests', function (Blueprint $table) {
                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
                $table->foreign('dss_run_id')->references('id')->on('dss_runs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('restock_requests', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropForeign(['dss_run_id']);
            });
        }

        Schema::table('restock_requests', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['dss_run_id']);
            $table->dropColumn(['supplier_id', 'dss_run_id']);
        });
    }
};
