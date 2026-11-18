<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiRestockSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_api_restock_menolak_tamu(): void
    {
        $this->getJson('/api/oas/restock')->assertUnauthorized();
        $this->postJson('/api/oas/restock', [])->assertUnauthorized();
        $this->putJson('/api/oas/restock/1/status', ['status' => 'approved'])->assertUnauthorized();
    }

    public function test_pelanggan_tidak_dapat_mengakses_api_restock(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($pelanggan, 'sanctum')->getJson('/api/oas/restock')->assertForbidden();
    }

    public function test_staff_membuat_pengajuan_dengan_employee_dari_akun_login(): void
    {
        [$staff, $employeeId, $product] = $this->skenarioStaff();

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/oas/restock', [
            'product_id' => $product->id,
            'employee_id' => 999999,
            'jumlah_restock' => 5,
            'catatan' => 'Restock API',
        ]);

        $response->assertCreated()->assertJsonPath('data.employee_id', $employeeId);
        $this->assertDatabaseHas('restock_requests', [
            'product_id' => $product->id,
            'employee_id' => $employeeId,
            'jumlah_restock' => 5,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('restock_requests', ['employee_id' => 999999]);
    }

    public function test_approval_api_hanya_menambah_stok_satu_kali(): void
    {
        [$admin, $employeeId, $product, $request] = $this->skenarioPersetujuan();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/oas/restock/'.$request->id.'/status', ['status' => 'approved'])
            ->assertOk();

        $this->assertSame(7, $product->fresh()->stock);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/oas/restock/'.$request->id.'/status', ['status' => 'approved'])
            ->assertStatus(409);

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_staff_tidak_dapat_menyetujui_pengajuan_melalui_api(): void
    {
        [$staff, $employeeId, $product, $request] = $this->skenarioPersetujuan(true);

        $this->actingAs($staff, 'sanctum')
            ->putJson('/api/oas/restock/'.$request->id.'/status', ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame(2, $product->fresh()->stock);
    }

    /**
     * @return array{0: User, 1: int, 2: Product}
     */
    private function skenarioStaff(): array
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $employee = Employee::create([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Staff',
        ]);

        return [$staff, $employee->id, $this->produk()];
    }

    /**
     * @return array{0: User, 1: int, 2: Product, 3: RestockRequest}
     */
    private function skenarioPersetujuan(bool $roleStaff = false): array
    {
        $user = User::factory()->create(['role' => $roleStaff ? 'staff' : 'admin']);

        $employee = Employee::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $roleStaff ? 'Staff' : 'Manager',
        ]);

        $product = $this->produk();

        $request = RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'jumlah_restock' => 5,
            'status' => 'pending',
        ]);

        return [$user, $employee->id, $product, $request];
    }

    private function produk(): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Kaos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::create([
            'category_id' => $categoryId,
            'name' => 'Kaos Polos',
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Merah',
            'price' => 50000,
            'stock' => 2,
        ]);
    }
}
