<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyButtonUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_beli_memakai_url_produk_masing_masing(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);
        $products = Product::factory()->count(2)->create();

        $html = $this->actingAs($pelanggan)
            ->get(route('toko.index'))
            ->assertOk()
            ->getContent();

        foreach ($products as $product) {
            $this->assertStringContainsString(
                'data-buy-url="'.route('toko.buy', ['product_id' => $product->id]).'"',
                $html
            );
        }

        $this->assertStringNotContainsString('const BUY_URL', $html);
        $this->assertStringContainsString('buyForm.action = btn.dataset.buyUrl;', $html);
    }
}
