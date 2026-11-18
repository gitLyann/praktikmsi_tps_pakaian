<?php

namespace App\Providers;

use App\Models\DssSupplierScore;
use App\Models\RestockRequest;
use App\Models\Transaction;
use App\Observers\EisActivityObserver;
use App\Observers\EisRestockObserver;
use App\Observers\EisSupplierScoreObserver;
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

        Transaction::observe(EisActivityObserver::class);
        RestockRequest::observe(EisRestockObserver::class);
        DssSupplierScore::observe(EisSupplierScoreObserver::class);
    }
}
