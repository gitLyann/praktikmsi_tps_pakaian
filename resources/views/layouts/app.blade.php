<!DOCTYPE html>
<html lang="en" class="lm-dashboard-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'La Mamora - OAS Pakaian' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Tipografi design system: Inter (teks) + JetBrains Mono (angka/kode) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    {{-- Design system La Mamora: satu sumber token visual untuk seluruh dashboard --}}
    <link href="{{ asset('css/la-mamora.css') }}" rel="stylesheet">
    <style>
        /* Overlay kustom (bukan Bootstrap Modal) untuk form edit produk.
           Sengaja memakai position:fixed + hidden attribute supaya tidak
           ikut tersembunyi bersama section dashboard yang memakai d-none. */
        .produk-overlay {
            position: fixed;
            inset: 0;
            background: var(--lm-overlay);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 24px 16px;
            z-index: 1080;
            overflow-y: auto;
        }
        .produk-overlay[hidden] {
            display: none;
        }
        .produk-overlay-box {
            background: var(--lm-card);
            color: var(--lm-card-foreground);
            border: 1px solid var(--lm-border);
            border-radius: var(--lm-radius-lg);
            width: 100%;
            max-width: 720px;
            box-shadow: var(--lm-shadow-lg);
        }
        .produk-overlay-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--lm-border);
        }
        .produk-overlay-body {
            padding: 22px;
        }
        .produk-overlay-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 22px;
            border-top: 1px solid var(--lm-border);
        }
        .produk-preview {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--lm-border);
        }

        /* Overlay read-only "Lihat Produk". Memakai .produk-overlay yang sama
           dengan form edit, hanya menambahkan blok tampilan baca-saja. */
        .produk-detail-media {
            display: flex;
            justify-content: center;
            margin-bottom: 18px;
        }
        .produk-detail-photo {
            width: 100%;
            max-width: 320px;
            height: 220px;
            object-fit: contain;
            background: var(--lm-muted-bg);
            border: 1px solid var(--lm-border);
            border-radius: 12px;
        }
        .produk-detail-fallback {
            width: 100%;
            max-width: 320px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--lm-muted-bg);
            border: 1px dashed var(--lm-border);
            border-radius: 12px;
            color: var(--lm-muted);
            font-size: 0.9rem;
        }
        /* white-space: pre-wrap supaya baris baru di deskripsi tidak collapsed */
        .produk-detail-deskripsi {
            white-space: pre-wrap;
            background: var(--lm-muted-bg);
            border: 1px solid var(--lm-border);
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--lm-muted);
        }
        .produk-detail-kosong {
            color: var(--lm-muted);
            font-style: italic;
        }
    </style>
    @stack('styles')
    {{-- Pasang tema sebelum paint supaya tidak ada kedipan terang->gelap --}}
    @include('layouts.theme')
</head>
{{-- body.lm-dashboard mengaktifkan tema La Mamora (token + override Bootstrap) --}}
<body class="lm-dashboard">
    <div class="d-flex">
        @if(isset($sidebar) && $sidebar)
            <aside class="sidebar d-flex flex-column" id="sidebar">
                @include('layouts.sidebar', ['activeRoute' => $activeRoute ?? ''])
            </aside>
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        @endif

        <div class="lm-shell">
            {{-- Topbar dashboard: pemicu sidebar mobile + judul halaman aktif + meta akun --}}
            <header class="lm-topbar">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <button type="button" class="lm-topbar-toggle" id="sidebarToggler" aria-label="Buka menu navigasi">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="min-w-0">
                        <div class="lm-topbar-eyebrow">{{ $topbarEyebrow ?? 'Sistem OAS &amp; MIS' }}</div>
                        <div class="lm-topbar-title" id="lmTopbarTitle">{{ $topbarTitle ?? ($title ?? 'Dashboard') }}</div>
                    </div>
                </div>
                <div class="lm-topbar-meta">
                    <button type="button" class="lm-theme-toggle ic" data-theme-toggle aria-label="Aktifkan mode gelap" aria-pressed="false" title="Mode gelap">
                        <i class="bi bi-moon-stars" data-theme-icon></i>
                    </button>
                    <span class="lm-chip">
                        <i class="bi bi-calendar3"></i>
                        {{ now()->translatedFormat('d M Y') }}
                    </span>
                    <span class="lm-chip">
                        <i class="bi bi-person-circle"></i>
                        {{ ucfirst(Auth::user()->role ?? 'user') }}
                    </span>
                </div>
            </header>

            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggler = document.getElementById('sidebarToggler');
            if (sidebarToggler) {
                sidebarToggler.addEventListener('click', toggleSidebar);
            }
        });

        /* ---------- Reset posisi scroll ----------
           Dashboard selalu harus dibuka dari paling atas. Tanpa ini, browser
           memulihkan posisi scroll lama (scroll restoration) atau melakukan
           anchor scroll otomatis dari hash URL, sehingga halaman tampak
           terpotong di atas dan topbar menutupi judul section. */
        (function () {
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }

            const scrollToTop = () => window.scrollTo(0, 0);

            document.addEventListener('DOMContentLoaded', scrollToTop);
            window.addEventListener('load', scrollToTop);
            // Dipanggil saat halaman dipulihkan dari cache browser (bfcache).
            window.addEventListener('pageshow', scrollToTop);
        })();

        /* Judul topbar mengikuti section dashboard yang sedang aktif.
           Setiap section memberi atribut data-lm-title; section tanpa
           atribut itu diabaikan sehingga judul tetap pada nilai default. */
        (function () {
            const titleEl = document.getElementById('lmTopbarTitle');
            if (!titleEl) return;

            const sections = () => Array.from(document.querySelectorAll('.dashboard-section'));

            const syncTitle = () => {
                const active = sections().find(section => !section.classList.contains('d-none'));
                const label = active ? active.getAttribute('data-lm-title') : null;
                if (label && titleEl.textContent.trim() !== label.trim()) {
                    titleEl.textContent = label.trim();
                }
            };

            syncTitle();

            const observer = new MutationObserver(syncTitle);
            sections().forEach(section => {
                observer.observe(section, { attributes: true, attributeFilter: ['class'] });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
