<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_tidak_bisa_mengakses_halaman_dss_admin_dan_staff(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($pelanggan)->get('/admin/dss')->assertStatus(403);
        $this->actingAs($pelanggan)->post('/admin/dss/analisis')->assertStatus(403);
        $this->actingAs($pelanggan)->get('/staff/dss')->assertStatus(403);
        $this->actingAs($pelanggan)->post('/staff/dss/skor')->assertStatus(403);
    }

    public function test_staff_bisa_mengakses_halaman_penilaian_dss_tapi_tidak_analisis_admin(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/staff/dss')->assertOk();
        $this->actingAs($staff)->get('/admin/dss')->assertStatus(403);
        $this->actingAs($staff)->post('/admin/dss/analisis')->assertStatus(403);
    }

    public function test_admin_bisa_mengakses_analisis_dss_dan_penilaian_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/dss')->assertOk();
        $this->actingAs($admin)->get('/staff/dss')->assertOk();
    }

    public function test_staff_harus_login_untuk_mengakses_dss(): void
    {
        $this->get('/staff/dss')->assertRedirect(route('login'));
    }
}
