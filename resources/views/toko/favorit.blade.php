@extends('layouts.toko-shell', [
    'title' => 'Produk Favorit',
    'activeNav' => 'favorit',
    'heading' => 'Produk Favorit',
    'subheading' => 'Semua produk yang pernah kamu tandai dengan hati.',
])

@section('content')
    @php
        $emojiKategori = [
            'kaos' => '👕', 'kemeja' => '👔', 'celana' => '👖', 'jaket' => '🧥',
            'hoodie' => '🧥', 'atasan' => '👕', 'bawahan' => '👖', 'aksesoris' => '🧢',
            'sepatu' => '👟', 'topi' => '🧢', 'tas' => '🎒', 'kacamata' => '🕶️',
        ];
    @endphp

    <div class="panel">
        <div class="panel-head">
            <h2>Daftar Favorit Saya</h2>
            <span class="count">{{ $products->count() }} produk tersimpan</span>
        </div>

        <div class="grid">
            @forelse($products as $product)
                @php
                    $kategoriNama = $product->category->name ?? '';
                    $emoji = $emojiKategori[Str::lower($kategoriNama)] ?? '👕';
                @endphp
                <article class="card">
                    <div class="thumb">
                        {{-- Di halaman ini semua produk sudah pasti favorit, jadi aria-pressed selalu true --}}
                        <button type="button" class="fav" data-fav="{{ route('toko.favorit.toggle', $product->id) }}"
                                aria-pressed="true" aria-label="Hapus {{ $product->name }} dari favorit">
                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        </button>
                        @if($product->image)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            {{ $emoji }}
                        @endif
                    </div>
                    <h3><a href="{{ route('toko.show', $product->id) }}">{{ $product->name }}</a></h3>
                    <p>{{ $kategoriNama ?: 'Tanpa kategori' }}{{ $product->type ? ' · ' . $product->type : '' }}{{ $product->color ? ' · ' . $product->color : '' }}</p>
                    <div class="foot">
                        <b>Rp {{ number_format($product->price, 0, ',', '.') }}</b>
                        <span class="mini{{ $product->stock > 0 ? '' : ' out' }}">{{ $product->stock }} Pcs</span>
                    </div>
                    <div class="actions">
                        @if($isPelanggan)
                            <form action="{{ route('toko.cart.add', $product) }}" method="POST" style="display:inline">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button class="buy" type="submit" @if($product->stock < 1) disabled @endif>+ Keranjang</button>
                            </form>
                        @endif
                        <a href="{{ route('toko.show', $product->id) }}" class="buy" style="display:inline-block;text-align:center;text-decoration:none">Lihat Detail</a>
                    </div>
                </article>
            @empty
                <div class="empty">
                    <div class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    </div>
                    <h2>Belum ada produk favorit</h2>
                    <p>Tekan ikon hati pada produk di katalog untuk menyimpannya di sini.</p>
                    <a href="{{ route('toko.index') }}" class="btn" style="display:inline-block">Jelajahi Katalog</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
