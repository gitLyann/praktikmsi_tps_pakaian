<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Chatbot;

class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'queries' => 'jam operasional,jam buka,jam tutup,buka pukul berapa,tutup pukul berapa',
                'replies' => 'Toko kami buka setiap hari dari pukul 09:00 - 21:00 WIB. Hari libur nasional tetap buka, kecuali hari besar tertentu yang akan diumumkan lewat media sosial kami.'
            ],
            [
                'queries' => 'cara beli,beli,bagaimana beli,proses beli,langkah beli',
                'replies' => 'Cara belanja di TPS Pakaian:\n1. Pilih produk yang diinginkan di halaman katalog\n2. Klik tombol "Beli Sekarang"\n3. Masukkan jumlah quantity\n4. Pilih metode pembayaran (E-Wallet, M-Banking, Transfer Bank, Cash)\n5. Klik "Konfirmasi Pembelian"\n6. Lakukan pembayaran sesuai instruksi\n7. Barang akan diproses dan dikirim ke alamat Anda'
            ],
            [
                'queries' => 'metode pembayaran,pembayaran,bayar,cara bayar,ewallet,mbanking,transfer,cash',
                'replies' => 'Metode pembayaran yang tersedia:\n1. E-Wallet (GoPay, OVO, Dana, ShopeePay)\n2. M-Banking (BCA, Mandiri, BRI, BNI)\n3. Transfer Bank (Rekening BCA/Mandiri/BRI/BNI)\n4. Cash (Bayar di tempat / COD)\n\nSilakan pilih metode yang paling nyaman untuk Anda.'
            ],
            [
                'queries' => 'pengembalian,retur,return,komplain,barang rusak,barang tidak sesuai',
                'replies' => 'Kebijakan Pengembalian & Retur:\n- Bisa retur dalam 3x24 jam setelah barang diterima\n- Barang harus dalam kondisi asli, belum digunakan, tag masih terpasang\n- Kirim foto barang ke CS untuk verifikasi\n- Biaya kirim retur ditanggung toko jika barang rusak/tidak sesuai pesanan\n- Proses refund 1-3 hari kerja setelah barang diterima toko\n\nHubungi Customer Service untuk memulai proses retur.'
            ],
            [
                'queries' => 'pengiriman,kirim,ongkir,shipping,ekspedisi,jne,jnt,sicepat',
                'replies' => 'Kami menggunakan ekspedisi JNE, J&T, dan SiCepat. Ongkir dihitung otomatis berdasarkan berat & tujuan. Estimasi pengiriman 1-3 hari kerja (Jawa) dan 3-7 hari kerja (luar Jawa). Nomor resi akan dikirim ke WhatsApp/Email setelah barang dikirim.'
            ],
            [
                'queries' => 'ukuran,size,chart,ukuran baju,ukuran celana,size guide',
                'replies' => 'Panduan Ukuran:\n- Baju: S (88-92cm), M (96-100cm), L (104-108cm), XL (112-116cm), XXL (120-124cm)\n- Celana: 28-38 (pinggang 71-96cm)\n- Detail ukuran tiap produk tersedia di halaman detail produk\n- Jika ragu, hubungi CS untuk rekomendasi ukuran sesuai tinggi/berat badan Anda.'
            ],
            [
                'queries' => 'kontak,cs,customer service,nomor,wa,whatsapp,email,alamat',
                'replies' => 'Customer Service TPS Pakaian:\n- WhatsApp: 0812-3456-7890 (09:00-21:00 WIB)\n- Email: cs@tpspakaian.com\n- Alamat: Jl. Raya Pakaian No. 123, Jakarta Selatan\n\nKami siap membantu Anda!'
            ],
            [
                'queries' => 'diskon,promo,promosi,sale,diskon member,voucher,kode promo',
                'replies' => 'Promo terkini:\n- Member baru: Diskon 10% pembelian pertama (kode: NEWMEMBER)\n- Beli minimal 3 item: Gratis ongkir\n- Flash Sale setiap Jumat pukul 19:00-21:00\n- Follow Instagram @tpspakaian untuk update promo terbaru!'
            ]
        ];

        foreach ($faqs as $faq) {
            Chatbot::create($faq);
        }

        $this->command->info('ChatbotSeeder: ' . count($faqs) . ' FAQ data seeded successfully.');
    }
}