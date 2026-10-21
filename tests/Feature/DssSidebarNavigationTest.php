<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test navigasi dashboard.
 *
 * Dua keluhan yang diperbaiki di Phase 29:
 * 1. Halaman dashboard terpotong di atas (scroll position/anchor hash).
 * 2. Menu sidebar, pengaturan, dan logout tidak bisa diklik dari halaman DSS
 *    karena link staff semula hanya berisi hash (#dashboard) yang butuh JS
 *    dashboard staff, dan form logout hanya ada di section #pengaturan.
 */
class DssSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_dss_staff_memuat_link_sidebar_lengkap(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $html = $this->actingAs($staff)->get(route('staff.dss'))->assertOk()->getContent();

        // Semua menu staff harus menuju URL penuh supaya tetap bisa diklik
        // dari halaman lain (DSS / Master Supplier).
        foreach ([
            'dashboard',
            'monitoring-stok',
            'pengajuan-restock',
            'riwayat-stok',
        ] as $section) {
            $this->assertStringContainsString(
                'href="'.route('staff.dashboard').'#'.$section.'"',
                $html,
                "Link sidebar staff #{$section} harus memakai URL penuh."
            );
        }

        // Pengaturan dan logout harus tetap bisa diakses dari halaman DSS.
        $this->assertStringContainsString('data-bs-target="#settingsModal"', $html);
        $this->assertStringContainsString('data-bs-target="#profileModal"', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
    }

    public function test_halaman_dss_admin_memuat_link_sidebar_lengkap(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)->get(route('admin.dss'))->assertOk()->getContent();

        foreach ([
            'dashboard',
            'analisis-mis',
            'stok-realtime',
            'kelola-produk',
            'persetujuan-restock',
            'riwayat-stok',
        ] as $section) {
            $this->assertStringContainsString(
                'href="'.route('admin.dashboard').'#'.$section.'"',
                $html,
                "Link sidebar admin #{$section} harus memakai URL penuh."
            );
        }

        $this->assertStringContainsString('href="'.route('staff.dss').'"', $html);
        $this->assertStringContainsString('data-bs-target="#settingsModal"', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
    }

    public function test_dashboard_admin_dan_staff_selalu_dibuka_dari_paling_atas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        foreach (['admin.dashboard', 'staff.dashboard', 'admin.dss', 'staff.dss'] as $routeName) {
            $html = $this->actingAs($admin)->get(route($routeName))->assertOk()->getContent();

            $this->assertStringContainsString('scrollRestoration', $html);
            $this->assertStringContainsString('scrollTo(0, 0)', $html);
            $this->assertStringContainsString('class="lm-dashboard-root"', $html);
        }

        // Halaman staff DSS memakai layout yang sama.
        $html = $this->actingAs($staff)->get(route('staff.dss'))->assertOk()->getContent();
        $this->assertStringContainsString('scrollRestoration', $html);
    }

    public function test_modal_profil_tidak_ditahan_js_section_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $html = $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk()->getContent();

        // Link modal harus tetap punya trigger Bootstrap (tidak di-preventDefault).
        $this->assertStringContainsString('data-bs-toggle="modal" data-bs-target="#profileModal"', $html);

        // Di dashboard staff, Pengaturan memakai section in-page supaya hanya
        // ada satu form logout di halaman itu.
        $this->assertStringContainsString('data-bs-target="#pengaturan"', $html);
        $this->assertStringNotContainsString('data-bs-toggle="modal" data-bs-target="#settingsModal"', $html);
        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
    }
}
