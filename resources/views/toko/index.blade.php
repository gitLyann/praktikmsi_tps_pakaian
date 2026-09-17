<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Pakaian - TPS Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
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

<!-- Hero Section -->
<div class="container py-4">
    <h3 class="mb-4">Katalog Pakaian Terbaru</h3>
    <div class="row">
        @forelse($products as $product)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">{{ $product->name }}</h5>
                        <p class="card-text text-muted small">Category ID: {{ $product->category_id ?? '-' }}</p>
                        <div class="mt-3">
                            <span class="text-primary fw-bold fs-5">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                Stok: {{ $product->stock }}
                            </span>
                        </div>
                        
                        <!-- Modal Konfirmasi Pembelian -->
                        <div class="modal fade" id="buyModal{{ $product->id }}" tabindex="-1" aria-labelledby="buyModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="buyModalLabel">Konfirmasi Pembelian</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        
                                        <div class="mb-3">
                                            <strong>Produk:</strong> {{ $product->name }}
                                        </div>
                                        <div class="mb-3">
                                            <strong>Harga Satuan:</strong> Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                        <div class="mb-3">
                                            <strong>Jumlah:</strong>
                                            <input type="number" name="quantity" class="form-control form-control-sm" min="1" value="1" style="width: 80px;">
                                        </div>
                                        <div class="mb-3">
                                            <strong>Metode Pembayaran:</strong>
                                            <select name="payment_method" class="form-select form-control-sm">
                                                <option value="E-Wallet">E-Wallet</option>
                                                <option value="M-Banking">M-Banking</option>
                                                <option value="Transfer Bank">Transfer Bank</option>
                                                <option value="Cash">Cash</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Total Bayar:</strong> Rp <span id="modalTotal">{{ number_format($product->price, 0, ',', '.') }}</span>
                                            <input type="hidden" name="total" id="modalTotalHidden" value="{{ $product->price }}">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <!-- Form submit akan di-trigger via JavaScript -->
                                        <button type="button" class="btn btn-primary" onclick="document.getElementById('buyForm{{ $product->id }}').submit()">Konfirmasi Pembelian</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Submit Modal (d-hidden di desktop, tampil di modal) -->
                        <form action="{{ route('toko.buy', $product->id) }}" method="POST" name="buyForm{{ $product->id }}" class="d-none">
                            @csrf
                        </form>
                        
                        <button class="btn btn-primary w-100" style="cursor: pointer;">
                            {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="col-12">
        <div class="alert alert-info text-center">Belum ada produk yang tersedia di katalog saat ini.</div>
    </div>
@endforelse
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>