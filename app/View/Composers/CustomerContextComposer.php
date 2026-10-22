<?php

namespace App\View\Composers;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Konteks pelanggan yang dipakai bersama oleh seluruh halaman toko.
 *
 * Badge keranjang dan tombol "tambah ke keranjang" hanya relevan untuk role
 * `pelanggan`, sehingga nilainya dihitung satu kali per request.
 */
class CustomerContextComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $isPelanggan = $user !== null && $user->role === 'pelanggan';

        $view->with([
            'isPelanggan' => $isPelanggan,
            'cartCount' => $isPelanggan
                ? (int) Cart::where('user_id', $user->id)->sum('quantity')
                : 0,
        ]);
    }
}
