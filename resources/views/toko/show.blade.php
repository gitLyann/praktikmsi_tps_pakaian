@extends('layouts.toko-shell', [
    'title' => $product->name,
    'activeNav' => 'katalog',
    'heading' => 'Detail Produk',
    'subheading' => 'Lihat deskripsi lengkap sebelum menambahkan produk ke favorit.',
])

@push('styles')
    <style>
        .back{display:inline-flex;align-items:center;gap:8px;text-decoration:none;color:var(--accent);font-weight:600;font-size:14.5px;padding:9px 16px;border-radius:99px;background:var(--accent-soft);align-self:flex-start}
        .back:hover{filter:brightness(.96)}
        .back svg{width:17px;height:17px;stroke-width:2.1}

        .detail{background:var(--card);border-radius:var(--radius);border:1px solid var(--line);display:grid;grid-template-columns:minmax(0,420px) 1fr;gap:0;overflow:hidden}
        @media(max-width:860px){.detail{grid-template-columns:1fr}}

        .gallery{background:var(--tile);aspect-ratio:1/0.9;display:grid;place-items:center;font-size:96px;overflow:hidden}
        .gallery img{width:100%;height:100%;object-fit:cover}

        .info{padding:26px 28px}
        .crumbs{font-size:13px;color:var(--muted);margin-bottom:9px}
        .crumbs a{color:var(--muted)}
        .info h2{font-size:25px;font-weight:800;letter-spacing:-.02em;line-height:1.25}
        .tags{display:flex;flex-wrap:wrap;gap:7px;margin:13px 0 18px}
        .tag{font-size:12.5px;font-weight:600;padding:5px 12px;border-radius:99px;background:var(--field);color:var(--muted)}
        .tag.cat{background:var(--accent-soft);color:var(--accent)}

        .price{font-size:29px;font-weight:800;letter-spacing:-.02em}
        .stockline{margin-top:8px;font-size:14px;font-weight:600}
        .stockline.ok{color:#1a7f43}
        .stockline.out{color:var(--danger)}

        .desc{margin-top:22px;padding-top:20px;border-top:1px solid var(--line)}
        .desc h3{font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px}
        .desc p{font-size:14.5px;line-height:1.75;white-space:pre-line;word-wrap:break-word}
        .desc p.fallback{color:var(--muted);font-style:italic}

        .specs{margin-top:22px;padding-top:20px;border-top:1px solid var(--line);display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:14px}
        .spec small{display:block;font-size:12px;color:var(--muted);font-weight:600;margin-bottom:3px}
        .spec b{font-size:14.5px;font-weight:600}

        .actions{margin-top:26px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .actions .btn{padding:14px 26px}
        .btn.ghost{background:var(--field);color:var(--ink)}
    </style>
@endpush

@section('content')
    <a href="{{ route('toko.index') }}" class="back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Kembali ke Katalog
    </a>

    @php
        $emojiKategori = [
            'kaos' => '👕', 'kemeja' => '👔', 'celana' => '👖', 'jaket' => '🧥',
            'hoodie' => '🧥', 'atasan' => '👕', 'bawahan' => '👖', 'aksesoris' => '🧢',
            'sepatu' => '👟', 'topi' => '🧢', 'tas' => '🎒', 'kacamata' => '🕶️',
        ];
        $kategoriNama = $product->category->name ?? '';
        $emoji = $emojiKategori[Str::lower($kategoriNama)] ?? '👕';
    @endphp

    <div class="detail">
        <div class="gallery">
            @if($product->image)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @else
                {{ $emoji }}
            @endif
        </div>

        <div class="info">
            <div class="crumbs">
                Katalog › <a href="{{ route('toko.index') }}">Semua Produk</a> › <b>{{ $product->name }}</b>
            </div>

            <h2>{{ $product->name }}</h2>

            <div class="tags">
                @if($kategoriNama)
                    <span class="tag cat">{{ $kategoriNama }}</span>
                @endif
                @if($product->type)
                    <span class="tag">{{ $product->type }}</span>
                @endif
                @if($product->color)
                    <span class="tag">{{ $product->color }}</span>
                @endif
                @if($product->size)
                    <span class="tag">Ukuran {{ $product->size }}</span>
                @endif
            </div>

            <div class="price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
            <div class="stockline {{ $product->stock > 0 ? 'ok' : 'out' }}">
                @if($product->stock > 0)
                    Stok tersedia: {{ $product->stock }} pcs
                @else
                    Stok habis
                @endif
            </div>

            <div class="desc">
                <h3>Deskripsi Produk</h3>
                @if($product->description)
                    <p>{{ $product->description }}</p>
                @else
                    <p class="fallback">Deskripsi produk ini belum diisi. Silakan hubungi kasir untuk informasi lebih lanjut.</p>
                @endif
            </div>

            <div class="specs">
                <div class="spec"><small>Kategori</small><b>{{ $kategoriNama ?: '-' }}</b></div>
                <div class="spec"><small>Type</small><b>{{ $product->type ?? '-' }}</b></div>
                <div class="spec"><small>Ukuran</small><b>{{ $product->size ?? '-' }}</b></div>
                <div class="spec"><small>Warna</small><b>{{ $product->color ?? '-' }}</b></div>
            </div>

            <div class="actions">
                <button type="button" class="fav lg" data-fav="{{ route('toko.favorit.toggle', $product->id) }}"
                        aria-pressed="{{ $sudahFavorit ? 'true' : 'false' }}"
                        aria-label="{{ $sudahFavorit ? 'Hapus dari favorit' : 'Tambahkan ke favorit' }}">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                </button>

                <button type="button" class="btn" id="detailBuy" @disabled($product->stock <= 0)
                        data-name="{{ $product->name }}"
                        data-price="{{ (int) $product->price }}"
                        data-stock="{{ (int) $product->stock }}">
                    {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                </button>

                <a href="{{ route('toko.favorit') }}" class="btn ghost">Lihat Favorit</a>
            </div>
        </div>
    </div>
@endsection

{{-- Modal pembelian memakai pola yang sama dengan katalog --}}
@push('scripts')
    @if($product->stock > 0)
        <div class="modal-b" id="buyModal" role="dialog" aria-modal="true" aria-labelledby="buyTitle">
            <div class="modal-box">
                <h2 id="buyTitle">Konfirmasi Pembelian</h2>
                <div class="rowline"><span>Produk</span><b id="mName">-</b></div>
                <div class="rowline"><span>Harga Satuan</span><b id="mPrice">-</b></div>
                <label for="mQty">Jumlah</label>
                <input type="number" id="mQty" min="1" value="1">
                <label for="mPay">Metode Pembayaran</label>
                <select id="mPay">
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="M-Banking">M-Banking</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="Cash">Cash</option>
                </select>
                <div class="rowline" style="margin-top:14px;border-bottom:0"><span>Total Bayar</span><b id="mTotal">-</b></div>
                <form action="" method="POST" id="buyForm" style="display:none">
                    @csrf
                    <input type="hidden" name="quantity" id="mQtyHidden" value="1">
                    <input type="hidden" name="payment_method" id="mPayHidden" value="E-Wallet">
                </form>
                <div class="modal-act">
                    <button type="button" class="btn ghost" id="buyCancel">Batal</button>
                    <button type="button" class="btn" id="buyConfirm">Konfirmasi Pembelian</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const detailBuy = document.getElementById('detailBuy');
            if (!detailBuy || detailBuy.disabled) return;

            const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
            const buyModal = document.getElementById('buyModal');
            const buyForm = document.getElementById('buyForm');
            let unit = Number(detailBuy.dataset.price) || 0;
            let stock = Number(detailBuy.dataset.stock) || 0;

            function updateTotal() {
                const qty = Math.max(1, Math.min(+$('#mQty').value || 1, stock || 1));
                $('#mTotal').textContent = rupiah(unit * qty);
                $('#mQtyHidden').value = qty;
                $('#mPayHidden').value = $('#mPay').value;
            }

            detailBuy.addEventListener('click', () => {
                $('#mName').textContent = detailBuy.dataset.name;
                $('#mPrice').textContent = rupiah(unit);
                $('#mQty').value = 1;
                $('#mQty').max = stock;
                $('#mPay').value = 'E-Wallet';
                updateTotal();

                buyForm.action = @json(route('toko.buy', ['product_id' => $product->id]));
                buyModal.classList.add('show');
            });

            $('#mQty').addEventListener('input', updateTotal);
            $('#mPay').addEventListener('change', updateTotal);
            $('#buyCancel').addEventListener('click', () => buyModal.classList.remove('show'));
            buyModal.addEventListener('click', (e) => { if (e.target.id === 'buyModal') buyModal.classList.remove('show'); });
            $('#buyConfirm').addEventListener('click', () => { updateTotal(); buyForm.submit(); });
        })();
    </script>
@endpush
