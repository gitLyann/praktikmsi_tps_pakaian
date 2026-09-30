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