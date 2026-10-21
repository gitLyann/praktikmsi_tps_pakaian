<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur approval restock oleh Manager/Admin.
 *
 * Aksi approve/reject harus mengembalikan admin ke menu "Persetujuan Restock",
 * bukan ke dashboard utama, karena fragment hash tidak ikut pada header
 * Referer sehingga redirect()->back() selalu mendarat di section default.
 */
class RestockApprovalRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menyetujui_pengajuan_kembali_ke_menu_persetujuan_restock(): void
    {
        [$admin, $request] = $this->skenarioPengajuan();

        $response = $this->actingAs($admin)->put(route('admin.restock.update', $request->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('admin.dashboard').'#persetujuan-restock');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // Stok produk bertambah setelah approval.
        $this->assertSame(12, Product::find($request->product_id)->stock);
    }

    public function test_admin_menolak_pengajuan_kembali_ke_menu_persetujuan_restock(): void
    {
        [$admin, $request] = $this->skenarioPengajuan();

        $response = $this->actingAs($admin)->put(route('admin.restock.update', $request->id), [
            'status' => 'rejected',
        ]);

        $response->assertRedirect(route('admin.dashboard').'#persetujuan-restock');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'id' => $request->id,
            'status' => 'rejected',
        ]);

        // Stok tidak bertambah bila pengajuan ditolak.
        $this->assertSame(2, Product::find($request->product_id)->stock);
    }

    public function test_pesan_sukses_mentebutkan_produk_dan_status(): void
    {
        [$admin, $request] = $this->skenarioPengajuan();

        $this->actingAs($admin)->put(route('admin.restock.update', $request->id), [
            'status' => 'approved',
        ]);

        $message = session('success');
        $this->assertIsString($message);
        $this->assertStringContainsString('Kaos Polos', $message);
        $this->assertStringContainsString('disetujui', $message);
    }

    /**
     * @return array{0: User, 1: RestockRequest}
     */
    private function skenarioPengajuan(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $categoryId = \DB::table('categories')->insertGetId([
            'name' => 'Kaos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::create([
            'category_id' => $categoryId,
            'name' => 'Kaos Polos',
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Merah',
            'price' => 50000,
            'stock' => 2,
        ]);

        $employeeId = \DB::table('employees')->insertGetId([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Kasir',
        ]);

        $request = RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employeeId,
            'jumlah_restock' => 10,
            'status' => 'pending',
        ]);

        return [$admin, $request];
    }
}
