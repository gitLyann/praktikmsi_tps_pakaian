<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data pemasok (supplier) sebagai basis penilaian VIKOR
     * dan sumber urutan pemesanan pada rekomendasi restock.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();

            // Lead time (hari) dan minimum order dipakai untuk menghitung
            // safety stock, reorder point, dan pembulatan jumlah pemesanan.
            $table->unsignedInteger('lead_time_days')->default(7);
            $table->unsignedInteger('min_order_qty')->default(1);

            // Reputasi 0-5, dipakai sebagai salah satu kriteria penilaian.
            $table->decimal('rating', 3, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
