<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relasi many-to-many produk <-> pemasok beserta Offer Commercial
     * (harga belian, minimum order, lead time) dan penanda pemasok pilihan.
     */
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->unsignedInteger('lead_time_days')->default(7);
            $table->boolean('is_preferred')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Satu produk tidak boleh punya dua baris untuk pemasok yang sama.
            $table->unique(['product_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
