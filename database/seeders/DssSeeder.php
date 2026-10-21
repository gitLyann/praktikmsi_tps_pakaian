<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DssSeeder extends Seeder
{
    /**
     * Master data pemasok.
     * Nilai lead time dan minimum order dipakai DSS untuk menghitung
     * safety stock, reorder point, dan pembulatan jumlah pemesanan.
     */
    private const SUPPLIERS = [
        [
            'code' => 'SUP-001',
            'name' => 'Konveksi Bandung Sentosa',
            'contact_name' => 'Andri Saputra',
            'phone' => '0812-2233-4455',
            'email' => 'sales@sentosa-konveksi.test',
            'address' => 'Jl. Cibadak No. 45',
            'city' => 'Bandung',
            'lead_time_days' => 7,
            'min_order_qty' => 25,
            'rating' => 4.50,
        ],
        [
            'code' => 'SUP-002',
            'name' => 'Konveksi Semarang Abadi',
            'contact_name' => 'Siti Nurhaliza',
            'phone' => '0813-5566-7788',
            'email' => 'order@abadi-semarang.test',
            'address' => 'Jl. Majapahit No. 12',
            'city' => 'Semarang',
            'lead_time_days' => 10,
            'min_order_qty' => 50,
            'rating' => 4.20,
        ],
        [
            'code' => 'SUP-003',
            'name' => 'Textile Surabaya Jaya',
            'contact_name' => 'Budi Santoso',
            'phone' => '0857-8899-0011',
            'email' => 'cs@surabayajaya-textile.test',
            'address' => 'Jl. Suk industrial No. 8',
            'city' => 'Surabaya',
            'lead_time_days' => 5,
            'min_order_qty' => 20,
            'rating' => 4.80,
        ],
        [
            'code' => 'SUP-004',
            'name' => 'Konveksi Yogyakarta Langgeng',
            'contact_name' => 'Dewi Lestari',
            'phone' => '0878-1122-3344',
            'email' => 'halo@langgeng-yogya.test',
            'address' => 'Jl. Malioboro No. 77',
            'city' => 'Yogyakarta',
            'lead_time_days' => 14,
            'min_order_qty' => 25,
            'rating' => 3.90,
        ],
        [
            'code' => 'SUP-005',
            'name' => 'Konveksi Jakarta Makmur',
            'contact_name' => 'Rudi Hartono',
            'phone' => '0899-5566-7788',
            'email' => 'info@makmur-jakarta.test',
            'address' => 'Jl. Industri No. 3',
            'city' => 'Jakarta',
            'lead_time_days' => 3,
            'min_order_qty' => 10,
            'rating' => 4.10,
        ],
    ];

    /**
     * Kriteria penilaian DSS beserta bobot acuan.
     *
     * Bobot di sini adalah "kebenaran" (ground truth) yang dipakai untuk
     * membangun matriks perbandingan, sehingga matriks yang dihasilkan
     * konsisten (CR = 0) dan bisa dipakai sebagai contoh analisis.
     */
    private const CRITERIA = [
        [
            'code' => 'harga',
            'name' => 'Harga Beli',
            'description' => 'Harga beli per unit dari pemasok. Kriteria cost: semakin murah semakin baik.',
            'type' => 'cost',
            'weight' => 0.30,
            'sort_order' => 1,
        ],
        [
            'code' => 'kualitas',
            'name' => 'Kualitas Material',
            'description' => 'Kualitas kain dan hasil jahitan. Kriteria benefit: semakin tinggi semakin baik.',
            'type' => 'benefit',
            'weight' => 0.25,
            'sort_order' => 2,
        ],
        [
            'code' => 'lead_time',
            'name' => 'Lead Time Pengiriman',
            'description' => 'Lama waktu dari pesanan sampai barang diterima. Kriteria cost: semakin cepat semakin baik.',
            'type' => 'cost',
            'weight' => 0.18,
            'sort_order' => 3,
        ],
        [
            'code' => 'reputasi',
            'name' => 'Reputasi & Relasi',
            'description' => 'Reputasi Supplier dan kelengkapan administrasi. Kriteria benefit.',
            'type' => 'benefit',
            'weight' => 0.12,
            'sort_order' => 4,
        ],
        [
            'code' => 'moq',
            'name' => 'Minimum Order',
            'description' => 'Minimum pembelian per order. Kriteria cost: semakin kecil semakin baik.',
            'type' => 'cost',
            'weight' => 0.08,
            'sort_order' => 5,
        ],
        [
            'code' => 'layanan',
            'name' => 'Layanan & Dukungan',
            'description' => 'Kecepatan respon dan layanan purna jual. Kriteria benefit.',
            'type' => 'benefit',
            'weight' => 0.07,
            'sort_order' => 6,
        ],
    ];

    /**
     * Seeder data dasar DSS: pemasok, penawaran produk, kriteria AHP,
     * dan matriks perbandingan pairwise.
     *
     * Idempoten: dijalankan berkali-kali tidak menggandakan data.
     */
    public function run(): void
    {
        $this->ensureDemoProducts();

        $suppliers = $this->seedSuppliers();
        $this->seedProductSuppliers($suppliers);
        $this->seedCriteria();
    }

    /**
     * Membuat produk contoh bila tabel produk masih kosong.
     *
     * Needed supaya seeder tetap menghasilkan data DSS yang utuh pada
     * database kosong (mis. hasil `migrate:fresh --seed`), tanpa menyentuh
     * produk yang sudah ada.
     */
    private function ensureDemoProducts(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $category = Category::firstOrCreate(['name' => 'Kaos']);

        $demo = [
            ['name' => 'Kemeja Hitam Polos', 'type' => 'Kemeja', 'size' => 'M', 'color' => 'Hitam', 'price' => 150000, 'stock' => 24],
            ['name' => 'Kaos Polos Navy', 'type' => 'Kaos', 'size' => 'L', 'color' => 'Navy', 'price' => 75000, 'stock' => 40],
            ['name' => 'Jeans Modern Slim', 'type' => 'Celana', 'size' => '32', 'color' => 'Biru', 'price' => 180000, 'stock' => 18],
        ];

        foreach ($demo as $item) {
            Product::firstOrCreate(
                ['name' => $item['name']],
                array_merge($item, ['category_id' => $category->id])
            );
        }
    }

    /**
     * Menyimpan master pemasok.
     *
     * @return Collection<int, Supplier>
     */
    private function seedSuppliers(): Collection
    {
        $collection = collect();

        foreach (self::SUPPLIERS as $data) {
            $collection->push(Supplier::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            ));
        }

        return $collection;
    }

    /**
     * Menghubungkan setiap produk dengan 2-3 pemasok beserta harga belinya.
     *
     * @param  Collection<int, Supplier>  $suppliers
     */
    private function seedProductSuppliers(Collection $suppliers): void
    {
        $products = Product::orderBy('id')->get();

        foreach ($products as $product) {
            // Setiap produk dilayani 3 pemasok pertama, digeser mengikuti urutan id produk.
            $offset = ($product->id - 1) % max($suppliers->count(), 1);
            $picked = collect();

            for ($i = 0; $i < min(3, $suppliers->count()); $i++) {
                $picked->push($suppliers[($offset + $i) % $suppliers->count()]);
            }

            foreach ($picked->values() as $index => $supplier) {
                // Harga beli berada di rentang 55%-80% harga jual produk.
                $ratio = [0.78, 0.68, 0.58][$index] ?? 0.65;

                ProductSupplier::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'supplier_id' => $supplier->id,
                    ],
                    [
                        'price' => round($product->price * $ratio, 2),
                        'min_order_qty' => $supplier->min_order_qty,
                        'lead_time_days' => $supplier->lead_time_days,
                        'is_preferred' => $index === 0,
                        'notes' => null,
                    ]
                );
            }
        }
    }

    /**
     * Menyimpan kriteria AHP beserta bobot acuan dan matriks perbandingan.
     */
    private function seedCriteria(): void
    {
        $criteria = collect();

        foreach (self::CRITERIA as $data) {
            $criteria->push(DssCriterion::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            ));
        }

        $criteria = $criteria->sortBy('sort_order')->values();

        // Matriks perbandingan dibangun dari perbandingan bobot acuan,
        // sehingga matriks konsisten (CR = 0) dan bisa dipakai contoh analisis.
        foreach ($criteria as $a) {
            foreach ($criteria as $b) {
                if ($a->id === $b->id) {
                    continue;
                }

                $value = round($a->weight / $b->weight, 4);

                DssCriterionComparison::updateOrCreate(
                    [
                        'criterion_a_id' => $a->id,
                        'criterion_b_id' => $b->id,
                    ],
                    ['value' => $value]
                );
            }
        }
    }
}
