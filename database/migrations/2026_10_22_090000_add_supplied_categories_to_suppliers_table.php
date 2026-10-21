<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Supplier butuh kolom kategori bahan/produk yang dipasok.
     *
     * Kolom disimpan sebagai teks (dipisah koma) supaya tidak menambah tabel
     * relasi baru dan tetap aman untuk SQLite maupun MySQL. Data lama tidak
     * berubah, kolom baru saja nullable.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplied_categories', 255)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('supplied_categories');
        });
    }
};
