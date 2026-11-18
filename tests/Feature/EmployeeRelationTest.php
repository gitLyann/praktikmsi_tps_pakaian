<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_user_employee_dan_restock_requests_terhubung(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $employee = Employee::create([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Staff',
        ]);

        $product = Product::factory()->create();

        $request = RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'jumlah_restock' => 5,
            'status' => 'pending',
        ]);

        $this->assertTrue($staff->employee->is($employee));
        $this->assertTrue($employee->user->is($staff));
        $this->assertTrue($employee->restockRequests->first()->is($request));
    }

    public function test_staff_tanpa_data_karyawan_tidak_bisa_mengajukan_restock(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $product = Product::factory()->create();

        $response = $this->actingAs($staff)->post(route('staff.restock.store'), [
            'product_id' => $product->id,
            'jumlah_restock' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('restock_requests', ['product_id' => $product->id]);
    }

    public function test_pengajuan_restock_staff_memakai_employee_akun_login(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $employee = Employee::create([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Staff',
        ]);

        DB::table('employees')->insert([
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'name' => 'Karyawan Lain',
            'position' => 'Staff',
        ]);

        $product = Product::factory()->create();

        $this->actingAs($staff)->post(route('staff.restock.store'), [
            'product_id' => $product->id,
            'jumlah_restock' => 7,
        ])->assertRedirect();

        $this->assertDatabaseHas('restock_requests', [
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'jumlah_restock' => 7,
        ]);
    }
}
