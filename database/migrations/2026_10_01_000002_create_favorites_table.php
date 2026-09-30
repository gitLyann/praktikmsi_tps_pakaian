<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            // Satu produk bisa dihapus/disimpan ulang tanpa menyisakan baris yatim,
            // cascade ikut membersihkan produk yang dihapus Manager.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->timestamps();

            // Cegah satu user memfavoritkan produk yang sama dua kali,
            // sekaligus melayani query "produk yang difavoritkan user X".
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
