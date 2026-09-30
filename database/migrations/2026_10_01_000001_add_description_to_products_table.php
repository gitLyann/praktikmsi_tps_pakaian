<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom 'image' sudah ada dari migration 2026_09_30_120000, jadi di sini
        // hanya menambah deskripsi produk. Nullable supaya produk lama tetap aman.
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
