<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_settings_page_with_account_info_and_logout(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'name' => 'Staf Setelan',
            'email' => 'staf.setelan@example.test',
        ]);

        $this->actingAs($staff)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Informasi Akun')
            ->assertSee('Pengaturan &amp; Keamanan Akun', false)
            ->assertSee('Staf Setelan')
            ->assertSee('staf.setelan@example.test')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Logout Akun');
    }

    public function test_admin_can_open_settings_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Setelan',
            'email' => 'admin.setelan@example.test',
        ]);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Admin Setelan')
            ->assertSee('admin.setelan@example.test')
            ->assertSee('Logout Akun');
    }

    public function test_settings_page_has_single_logout_form_and_no_legacy_modal(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $html = $this->actingAs($staff)->get(route('settings.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
        $this->assertStringNotContainsString('id="settingsModal"', $html);
        $this->assertStringNotContainsString('data-open-settings', $html);
        $this->assertStringNotContainsString('data-bs-target="#profileModal"', $html);
    }

    public function test_pelanggan_cannot_open_settings_page(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($pelanggan)->get(route('settings.index'))->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_for_settings_page(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }
}
