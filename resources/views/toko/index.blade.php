<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Pakaian - TPS Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">TPS Pakaian Store</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">Halo, {{ Auth::user()->name }} (Pelanggan)</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4">Katalog Pakaian Terbaru</h3>
        <div class="row">
            @forelse($products as $product)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">{{ $product->name }}</h5>
                            <p class="card-text text-muted small">
                                Category ID: {{ $product->category_id ?? '-' }}
                            </p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-primary fw-bold fs-5">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </span>
                                    <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                        Stok: {{ $product->stock }}
                                    </span>
                                </div>
                                <button class="btn btn-primary w-100" {{ $product->stock <= 0 ? 'disabled' : '' }}>
                                    {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Belum ada produk yang tersedia di katalog saat ini.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></script>
</body>
</html>
