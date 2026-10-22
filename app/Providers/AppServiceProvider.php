<?php

namespace App\Providers;

use App\View\Composers\CustomerContextComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Badge jumlah item keranjang dan penanda role dipakai bersama oleh
        // seluruh halaman toko. Blade merender halaman anak lebih dulu dari
        // layout, jadi keduanya perlu terdaftar.
        View::composer(['toko.*', 'layouts.toko-shell', 'welcome'], CustomerContextComposer::class);
    }
}
