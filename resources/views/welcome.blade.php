<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPS Pakaian Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .hero-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hero-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 1.5rem 3rem rgba(13, 110, 253, 0.15) !important;
        }
        .btn-primary, .btn-outline-primary, .btn-warning, .btn-outline-light {
            transition: all 0.25s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1.25rem rgba(13, 110, 253, 0.4);
        }
        .btn-outline-primary:hover {
            transform: translateY(-2px);
            background-color: #0d6efd;
            color: #fff;
            box-shadow: 0 0.5rem 1.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-warning:hover, .btn-outline-light:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">

<!-- Layout Full Screen: Flexbox Vertikal -->
<div class="d-flex flex-column min-vh-100">

    <!-- Navbar Sticky Top -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/">
                <i class="bi bi-bag-heart fs-4 text-warning"></i>
                <span>TPS Pakaian Store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </a>
                <a href="{{ route('register') }}" class="btn btn-warning btn-sm text-dark fw-bold">
                    <i class="bi bi-person-plus me-1"></i>Daftar
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section: Flex-grow agar mengambil seluruh sisa area -->
    <div class="flex-grow-1 d-flex align-items-center justify-content-center py-5"
         style="background: linear-gradient(135deg, #e9f2ff 0%, #f8f9fa 50%, #e3effc 100%);">
        <div class="container">
            <div class="card hero-card shadow-lg border-0 rounded-4 p-4 p-md-5 text-center mx-auto" style="max-width: 780px;">
                <div class="mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold rounded-pill px-3 py-2">
                        <i class="bi bi-stars me-1"></i>Sistem Terintegrasi TPS & OAS
                    </span>
                </div>

                <h1 class="display-5 fw-bold text-dark mb-3">Sistem Informasi TPS & OAS Pakaian</h1>

                <p class="fs-5 text-muted mx-auto mb-4" style="max-width: 640px;">
                    Platform katalog pakaian terintegrasi dengan sistem otomatisasi stok internal (OAS).
                    Kelola belanja, restock, dan persetujuan dalam satu pintu.
                </p>

                <div class="d-flex flex-column flex-md-row justify-content-center gap-2 mb-4">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-semibold d-flex align-items-center justify-content-center gap-2 px-4">
                        <i class="bi bi-box-arrow-in-right fs-5"></i>Masuk ke Aplikasi
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg fw-semibold d-flex align-items-center justify-content-center gap-2 px-4">
                        <i class="bi bi-person-plus fs-5"></i>Daftar Akun Baru
                    </a>
                </div>

                <hr class="my-4 mx-auto" style="max-width: 520px;">

                <div class="row g-3 text-start">
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-bag-check text-primary fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Katalog Pakaian</h6>
                                <small class="text-muted">Belanja produk terbaru.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-box-seam text-success fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0">OAS Restock</h6>
                                <small class="text-muted">Otomasi stok barang.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Monitoring Realtime</h6>
                                <small class="text-muted">Pantau stok realtime.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer: mt-auto agar menempel di bawah -->
    <footer class="footer bg-dark text-white text-center py-3 mt-auto">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small class="d-flex align-items-center gap-1">
                <i class="bi bi-shop"></i>&copy; 2026 TPS Pakaian Store. All rights reserved.
            </small>
            <div class="d-flex align-items-center gap-3">
                <a href="#" class="text-white-50 text-decoration-none"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-white-50 text-decoration-none"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-white-50 text-decoration-none"><i class="bi bi-whatsapp"></i></a>
            </div>
        </div>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>