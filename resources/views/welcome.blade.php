<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TPS Pakaian Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar dengan Single Login Portal -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">TPS Pakaian Store</a>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Login</a>
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Daftar</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<div class="container py-5 text-center">
    <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
        <h1 class="display-5 fw-bold text-dark">Sistem Informasi TPS & OAS Pakaian</h1>
        <p class="fs-5 text-muted col-md-8 mx-auto mt-3">
            Platform katalog pakaian terintegrasi dengan sistem otomatisasi stok internal.
        </p>
        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg me-2">Masuk ke Aplikasi</a>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg">Daftar Akun Baru</a>
        </div>
    </div>
</div>

<!-- Footer Section -->
<footer class="footer bg-dark text-white text-center py-3">
    <div class="container">
        <small>&copy; 2026 TPS Pakaian Store. All rights reserved.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>