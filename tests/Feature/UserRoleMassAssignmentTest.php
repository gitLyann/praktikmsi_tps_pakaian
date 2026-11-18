<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_tidak_termasuk_fillable_user(): void
    {
        $this->assertNotContains('role', (new User)->getFillable());
    }

    public function test_mass_assignment_tidak_dapat_mengubah_role(): void
    {
        $user = User::create([
            'name' => 'Pelanggan Baru',
            'email' => 'pelanggan@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $user->refresh();

        $this->assertSame('pelanggan', $user->role);
        $this->assertDatabaseHas('users', [
            'email' => 'pelanggan@example.com',
            'role' => 'pelanggan',
        ]);
    }

    public function test_factory_role_tetap_terisi_melalui_force_fill(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->assertSame('admin', $admin->role);
        $this->assertSame('staff', $staff->role);
        $this->assertSame('pelanggan', $pelanggan->role);
    }

    public function test_registrasi_pelanggan_tetap_menghasilkan_role_pelanggan(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pelanggan Register',
            'email' => 'register@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/toko');
        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
            'role' => 'pelanggan',
        ]);
    }
}
