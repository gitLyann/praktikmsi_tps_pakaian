<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Selamat Datang - TPS Pakaian Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar dengan Navigasi Kanan Atas -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">TPS Pakaian Store</a>
            <div class="d-flex gap-2">
                @auth
                    @if(Auth::user()->role === 'pelanggan')
                        <a href="/toko" class="btn btn-primary btn-sm">Masuk Katalog</a>
                    @elseif(Auth::user()->role === 'kasir')
                        <a href="/kasir/dashboard" class="btn btn-warning btn-sm">Dashboard Staff</a>
                    @else
                        <a href="/admin/dashboard" class="btn btn-success btn-sm">Dashboard Manager</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign In / Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container py-5 text-center">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
            <h1 class="display-5 fw-bold text-dark">Sistem Informasi TPS & OAS Pakaian</h1>
            <p class="fs-5 text-muted col-md-8 mx-auto mt-3">
                Platform belanja pakaian modern terintegrasi dengan otomatisasi manajemen stok internal perusahaan.
            </p>
            <div class="mt-4">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg me-2">Daftar Akun Pembeli</a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Masuk Kebagian Internal</a>
            </div>
        </div>
    </div>
</body>
</html>
