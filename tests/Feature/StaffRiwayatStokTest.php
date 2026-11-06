<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRiwayatStokTest extends TestCase
{
    use RefreshDatabase;

    private function makeRestock(User $staff, string $status = 'pending'): RestockRequest
    {
        $categoryId = \DB::table('categories')->insertGetId(['name' => 'Kaos', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create(['category_id' => $categoryId, 'name' => 'Kaos Test', 'price' => 10000, 'stock' => 5]);
        $employeeId = \DB::table('employees')->insertGetId(['user_id' => $staff->id, 'name' => $staff->name, 'position' => 'Kasir']);

        return RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employeeId,
            'jumlah_restock' => 10,
            'status' => $status,
            'catatan' => 'catatan uji',
        ]);
    }

    public function test_staff_can_open_riwayat_stok_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->makeRestock($staff);

        $response = $this->actingAs($staff)->get(route('staff.riwayat-stok'));
        $response->assertOk();
        $response->assertSee('Riwayat Stok');
        $response->assertSee('Riwayat Pengajuan');
        $response->assertSee('Kaos Test');
        $response->assertSee('data-open-settings'); // pengaturan modal available on this page
    }

    public function test_staff_riwayat_stok_supports_search_and_filter(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->makeRestock($staff, 'approved');

        $this->actingAs($staff)->get(route('staff.riwayat-stok', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('Approved');

        $this->actingAs($staff)->get(route('staff.riwayat-stok', ['q' => 'Kaos']))
            ->assertOk()
            ->assertSee('Kaos Test');
    }

    public function test_staff_cannot_mutate_restock_approval(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $req = $this->makeRestock($staff, 'pending');

        // Mutasi approval hanya route admin (role:admin). Staff harus 403.
        $this->actingAs($staff)
            ->put(route('admin.restock.update', $req->id), ['status' => 'approved'])
            ->assertStatus(403);
    }

    public function test_pelanggan_cannot_access_staff_riwayat_stok(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);
        $this->actingAs($pelanggan)->get(route('staff.riwayat-stok'))->assertStatus(403);
    }

    public function test_guest_redirected_to_login_for_staff_riwayat_stok(): void
    {
        $this->get(route('staff.riwayat-stok'))->assertRedirect(route('login'));
    }

    public function test_settings_link_and_modal_present_on_all_staff_pages(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        foreach ([route('staff.dashboard'), route('staff.riwayat-stok'), route('staff.dss'), route('supplier.master')] as $url) {
            $html = $this->actingAs($staff)->get($url)->assertOk()->getContent();
            $hasSettings = str_contains($html, 'data-open-settings') || str_contains($html, 'data-tab="pengaturan"');
            $this->assertTrue($hasSettings, "Pengaturan trigger missing on {$url}");
            if ($url !== route('staff.dashboard')) {
                $this->assertStringContainsString('id="settingsModal"', $html, "settingsModal missing on {$url}");
                $this->assertStringContainsString('action="'.route('logout').'"', $html);
            }
        }
    }
}
