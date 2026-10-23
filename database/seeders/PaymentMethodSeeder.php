<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Daftar metode pembayaran (SIMULASI). Idempoten: memakai updateOrCreate
 * berdasarkan `code` sehingga aman dijalankan berulang.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            // Kartu debit/kredit (chip VISA/Mastercard/JCB bersifat tampilan)
            ['group_code' => 'card', 'code' => 'card', 'name' => 'Kartu Debit/Kredit', 'sort_order' => 1],

            // ATM / transfer bank (Virtual Account per bank)
            ['group_code' => 'atm', 'code' => 'atm_bca', 'name' => 'BCA Virtual Account', 'sort_order' => 10],
            ['group_code' => 'atm', 'code' => 'atm_bni', 'name' => 'BNI Virtual Account', 'sort_order' => 11],
            ['group_code' => 'atm', 'code' => 'atm_bri', 'name' => 'BRI Virtual Account', 'sort_order' => 12],
            ['group_code' => 'atm', 'code' => 'atm_mandiri', 'name' => 'Mandiri Virtual Account', 'sort_order' => 13],

            // E-money
            ['group_code' => 'emoney', 'code' => 'emoney_gopay', 'name' => 'GoPay', 'sort_order' => 20],
            ['group_code' => 'emoney', 'code' => 'emoney_ovo', 'name' => 'OVO', 'sort_order' => 21],
            ['group_code' => 'emoney', 'code' => 'emoney_dana', 'name' => 'DANA', 'sort_order' => 22],
            ['group_code' => 'emoney', 'code' => 'emoney_shopeepay', 'name' => 'ShopeePay', 'sort_order' => 23],

            // Direct debit
            ['group_code' => 'direct_debit', 'code' => 'directdebit_bca_klikpay', 'name' => 'BCA KlikPay', 'sort_order' => 30],
            ['group_code' => 'direct_debit', 'code' => 'directdebit_bri', 'name' => 'BRI Direct Debit', 'sort_order' => 31],

            // Bayar di gerai
            ['group_code' => 'gerai', 'code' => 'gerai_alfamart', 'name' => 'Alfamart', 'sort_order' => 40],
            ['group_code' => 'gerai', 'code' => 'gerai_indomaret', 'name' => 'Indomaret', 'sort_order' => 41],

            // Tunai / bayar di tempat
            ['group_code' => 'tunai', 'code' => 'cod', 'name' => 'Bayar di Tempat (Tunai)', 'sort_order' => 50],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'group_code' => $method['group_code'],
                    'name' => $method['name'],
                    'is_active' => true,
                    'sort_order' => $method['sort_order'],
                ]
            );
        }
    }
}
