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

        // Menu staff tetap memakai URL penuh supaya bisa diklik dari halaman lain.
        foreach ([
            'dashboard',
            'monitoring-stok',
            'pengajuan-restock',
        ] as $section) {
            $this->assertStringContainsString(
                'href="'.route('staff.dashboard').'#'.$section.'"',
                $html,
                "Link sidebar staff #{$section} harus memakai URL penuh."
            );
        }
        $this->assertStringContainsString('href="'.route('staff.riwayat-stok').'"', $html);

        // Pengaturan sekarang memakai satu halaman canonical untuk staff & admin.
        $this->assertStringContainsString('href="'.route('settings.index').'"', $html);
        $this->assertStringNotContainsString('data-open-settings', $html);
        $this->assertStringNotContainsString('id="settingsModal"', $html);
        $this->assertStringNotContainsString('data-bs-target="#profileModal"', $html);
        $this->assertSame(0, substr_count($html, 'action="'.route('logout').'"'));
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

        $this->assertStringContainsString('href="'.route('admin.es').'"', $html);
        $this->assertStringContainsString('href="'.route('staff.dss').'"', $html);
        $this->assertStringContainsString('href="'.route('settings.index').'"', $html);
        $this->assertStringNotContainsString('data-open-settings', $html);
        $this->assertStringNotContainsString('id="settingsModal"', $html);
        $this->assertSame(0, substr_count($html, 'action="'.route('logout').'"'));
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

    public function test_pengaturan_memakai_link_halaman_di_dashboard_staff(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $html = $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('settings.index').'"', $html);
        $this->assertStringNotContainsString('data-bs-toggle="modal" data-bs-target="#profileModal"', $html);
        $this->assertStringNotContainsString('data-tab="pengaturan"', $html);
        $this->assertStringNotContainsString('data-open-settings', $html);
        $this->assertSame(0, substr_count($html, 'action="'.route('logout').'"'));
    }
}
