<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Memastikan kategori dasar ada. Idempoten dan tidak menghapus atau mengubah
 * kategori yang sudah ada (dipakai filter katalog pelanggan).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $wajib = ['Atasan', 'Celana', 'Outer', 'Bahan kain', 'Aksesori'];

        foreach ($wajib as $nama) {
            Category::firstOrCreate(['name' => $nama]);
        }
    }
}
