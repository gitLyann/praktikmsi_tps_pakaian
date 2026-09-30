<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TPS & OAS Pakaian' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .sidebar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #212529;
            text-decoration: none;
        }
        .sidebar-nav {
            padding: 1rem 0;
        }
        .sidebar-nav .nav-section {
            padding: 0 1rem;
            margin-bottom: 1rem;
        }
        .sidebar-nav .nav-section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
            padding: 0.5rem 0;
        }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.625rem 1rem;
            color: #6c757d;
            text-decoration: none;
            border-radius: 0.5rem;
            margin: 0.125rem 0.5rem;
            transition: all 0.2s ease;
        }
        .sidebar-nav .nav-link:hover {
            background-color: #f8f9fa;
            color: #212529;
        }
        .sidebar-nav .nav-link.active {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-weight: 600;
        }
        .sidebar-nav .nav-link i {
            font-size: 1.1rem;
            width: 1.5rem;
            text-align: center;
            margin-right: 0.75rem;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1040;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Overlay kustom (bukan Bootstrap Modal) untuk form edit produk.
           Sengaja memakai position:fixed + hidden attribute supaya tidak
           ikut tersembunyi bersama section dashboard yang memakai d-none. */
        .produk-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
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
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 720px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }
        .produk-overlay-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 18px 22px;
            border-bottom: 1px solid #e9ecef;
        }
        .produk-overlay-body {
            padding: 22px;
        }
        .produk-overlay-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 22px;
            border-top: 1px solid #e9ecef;
        }
        .produk-preview {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #e9ecef;
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
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
        }
        .produk-detail-fallback {
            width: 100%;
            max-width: 320px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 12px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        /* white-space: pre-wrap supaya baris baru di deskripsi tidak collapsed */
        .produk-detail-deskripsi {
            white-space: pre-wrap;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 14px;
            color: #495057;
        }
        .produk-detail-kosong {
            color: #6c757d;
            font-style: italic;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        @if(isset($sidebar) && $sidebar)
            <aside class="sidebar d-flex flex-column" id="sidebar">
                @include('layouts.sidebar', ['activeRoute' => $activeRoute ?? ''])
            </aside>
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        @endif

        <main class="main-content">
            @yield('content')
        </main>
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
    </script>
    @stack('scripts')
</body>
</html>