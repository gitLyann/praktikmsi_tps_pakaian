<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_melihat_seksi_rekomendasi_produk(): void
    {
        Product::factory()->create(['name' => 'Kemeja Flanel', 'stock' => 12]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Rekomendasi untukmu');
        $response->assertSee('Kemeja Flanel');
        $response->assertSee('Rekomendasi populer');
    }

    public function test_produk_habis_stok_tidak_ditampilkan_sebagai_rekomendasi(): void
    {
        Product::factory()->create(['name' => 'Hoodie Habis', 'stock' => 0]);
        Product::factory()->create(['name' => 'Celana Tersedia', 'stock' => 5]);

        $response = $this->get(route('landing'));

        $response->assertOk();

        // Katalog tetap menampilkan semua produk, jadi yang diperiksa hanya seksi rekomendasi.
        $this->assertStringContainsString('Celana Tersedia', $this->recommendationSection($response->getContent()));
        $this->assertStringNotContainsString('Hoodie Habis', $this->recommendationSection($response->getContent()));
    }

    public function test_pelanggan_login_melihat_rekomendasi_personal(): void
    {
        Product::factory()->create(['name' => 'Jaket Kanvas', 'stock' => 7]);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($pelanggan)->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Rekomendasi personal');
        $response->assertSee('Jaket Kanvas');
    }

    public function test_staff_melihat_rekomendasi_umum_bukan_personal(): void
    {
        Product::factory()->create(['name' => 'Kaos Polos', 'stock' => 9]);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('landing'))
            ->assertOk()
            ->assertSee('Rekomendasi populer')
            ->assertDontSee('Rekomendasi personal');
    }

    /**
     * Mengambil bagian HTML seksi rekomendasi saja.
     */
    private function recommendationSection(string $html): string
    {
        preg_match('/<section id="rekomendasi".*?<\/section>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
