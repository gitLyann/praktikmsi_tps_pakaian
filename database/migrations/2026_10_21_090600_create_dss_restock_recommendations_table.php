<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasil rekomendasi restock DSS per produk.
     *
     * Nilai yang disimpan sudah final (bukan kandidat): stok saat ini,
     * rata-rata penjualan harian, safety stock, reorder point, jumlah
     * pemesanan yang disarankan, dan estimasi biaya.
     */
    public function up(): void
    {
        Schema::create('dss_restock_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dss_run_id')->nullable()->constrained('dss_runs')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->integer('current_stock')->default(0);
            $table->decimal('avg_daily_sales', 12, 4)->default(0);
            $table->unsignedInteger('lead_time_days')->default(7);
            $table->integer('safety_stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('recommended_qty')->default(0);
            $table->decimal('estimated_cost', 14, 2)->default(0);

            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['status', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_restock_recommendations');
    }
};
