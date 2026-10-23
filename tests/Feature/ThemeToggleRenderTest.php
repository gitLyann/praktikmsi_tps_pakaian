<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tema global (Phase 36): setiap layout punya tombol ganti tema yang
 * diproses modul bersama layouts.theme, dan stylesheet design system
 * memuat jembatan token + mode gelap.
 */
class ThemeToggleRenderTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(): User
    {
        return User::factory()->create(['role' => 'pelanggan']);
    }

    public function test_landing_dan_auth_memuat_tombol_dan_modul_tema(): void
    {
        foreach ([route('landing'), route('login'), route('register')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('css/la-mamora.css', $html);
            $this->assertStringContainsString('data-theme-toggle', $html);
            $this->assertStringContainsString('lm-theme', $html);
            $this->assertStringContainsString('JetBrains+Mono', $html);
        }
    }

    public function test_logo_inline_svg_memakai_token_warna(): void
    {
        foreach ([route('landing'), route('login')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('lm-logo-svg', $html);
            $this->assertStringContainsString('fill="var(--lm-logo-ring, currentColor)"', $html);
            $this->assertStringContainsString('fill="var(--lm-logo-letter, currentColor)"', $html);
            $this->assertStringContainsString('fill="var(--lm-logo-bg, transparent)"', $html);
        }
    }

    public function test_dashboard_pelanggan_memuat_tombol_dan_modul_tema(): void
    {
        $html = $this->actingAs($this->pelanggan())->get(route('toko.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-theme-toggle', $html);
        $this->assertStringContainsString('lm-theme', $html);
        $this->assertStringContainsString('data-bs-theme', $html);
    }

    public function test_stylesheet_memuat_token_tema_dan_mode_gelap(): void
    {
        $css = file_get_contents(public_path('css/la-mamora.css'));

        $this->assertStringContainsString('--lm-primary: #aff33e', $css);
        $this->assertStringContainsString('--lm-accent-foreground: #166534', $css);
        $this->assertStringContainsString('--lm-logo-ring: var(--lm-primary)', $css);
        $this->assertStringContainsString('--lm-logo-letter: var(--lm-foreground)', $css);
        $this->assertStringContainsString('--lm-logo-bg: transparent', $css);
        $this->assertStringContainsString('--lm-sidebar-primary', $css);
        $this->assertStringContainsString(':root.dark', $css);
        $this->assertStringContainsString('data-bs-theme="dark"', $css);
        $this->assertStringContainsString('--bs-border-radius: var(--lm-radius-base)', $css);
    }
}
