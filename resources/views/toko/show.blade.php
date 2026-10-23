@extends('layouts.toko-shell', [
    'title' => $product->name,
    'activeNav' => 'katalog',
    'heading' => 'Detail Produk',
    'subheading' => 'Lihat deskripsi lengkap sebelum menambahkan produk ke favorit.',
])

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
                @if($isPelanggan)
                    <form action="{{ route('toko.cart.add', $product) }}" method="POST" style="display:inline-flex;gap:10px;align-items:center;flex-wrap:wrap">
                        @csrf
                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" style="width:90px;padding:10px 12px;border-radius:12px;border:1px solid var(--lm-line)">
                        <button class="btn orange" type="submit" @if($product->stock < 1) disabled @endif>Tambah ke Keranjang</button>
                    </form>
                @elseif(! Auth::check())
                    <form action="{{ route('toko.cart.intent', $product->id) }}" method="POST" style="display:inline-flex;gap:10px;align-items:center;flex-wrap:wrap">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="return_to" value="{{ url()->current() }}">
                        <button class="btn orange" type="submit" @if($product->stock < 1) disabled @endif>Tambah ke Keranjang</button>
                    </form>
                @endif
                <button type="button" class="fav lg" data-fav="{{ route('toko.favorit.toggle', $product->id) }}" aria-pressed="{{ $sudahFavorit ? 'true' : 'false' }}" aria-label="{{ $sudahFavorit ? 'Hapus dari favorit' : 'Tambahkan ke favorit' }}">
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
                <div class="modal-head">
                    <h2 id="buyTitle">Konfirmasi Pembelian</h2>
                    <button type="button" class="x" id="buyCancel" aria-label="Tutup">✕</button>
                </div>
                <div class="rowline"><span>Produk</span><b id="mName">-</b></div>
                <div class="rowline"><span>Harga Satuan</span><b id="mPrice">-</b></div>
                <label for="mQty">Jumlah</label>
                <input type="number" id="mQty" min="1" value="1">

                <label class="pay-label">Metode Pembayaran</label>
                @include('toko._payment-accordion', ['paymentGroups' => $paymentGroups, 'prefix' => 'detail-'])

                <div class="rowline pay-total"><span>Total Bayar</span><b id="mTotal">-</b></div>
                <form action="" method="POST" id="buyForm">
                    @csrf
                    <input type="hidden" name="quantity" id="mQtyHidden" value="1">
                    <input type="hidden" name="payment_method" id="mPayHidden" value="">
                    <input type="hidden" name="phone" id="mPhoneHidden" value="">
                </form>
                <div class="modal-act">
                    <button type="button" class="btn ghost" id="buyBtnCancel">Batal</button>
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

            // Akordeon: satu kategori terbuka setiap saat.
            const acc = buyModal.querySelector('[data-pay-acc]');
            const groups = Array.from(acc.querySelectorAll('[data-pay-group]'));
            groups.forEach((group) => {
                const toggle = group.querySelector('[data-pay-toggle]');
                const body = document.getElementById(toggle.dataset.payToggle);
                toggle.addEventListener('click', () => {
                    const willOpen = body.hasAttribute('hidden');
                    groups.forEach((other) => {
                        const otherToggle = other.querySelector('[data-pay-toggle]');
                        const otherBody = document.getElementById(otherToggle.dataset.payToggle);
                        other.classList.remove('open');
                        otherBody.setAttribute('hidden', '');
                        otherToggle.setAttribute('aria-expanded', 'false');
                    });
                    if (willOpen) {
                        group.classList.add('open');
                        body.removeAttribute('hidden');
                        toggle.setAttribute('aria-expanded', 'true');
                        const firstRadio = body.querySelector('input[type="radio"]');
                        if (firstRadio && !group.querySelector('input[type="radio"]:checked')) firstRadio.checked = true;
                    }
                });
            });

            const phoneField = buyModal.querySelector('[data-pay-phone]');
            const phoneGroup = phoneField ? phoneField.closest('.pay-group') : null;

            function selectedMethod() {
                return buyModal.querySelector('input[name="detail-payment_method"]:checked');
            }

            function updateTotal() {
                const qty = Math.max(1, Math.min(+$('#mQty').value || 1, stock || 1));
                $('#mTotal').textContent = rupiah(unit * qty);
                $('#mQtyHidden').value = qty;

                const method = selectedMethod();
                $('#mPayHidden').value = method ? method.value : '';
                const needsPhone = method && method.dataset.needsPhone === '1';
                if (phoneGroup) phoneGroup.hidden = !needsPhone;
                $('#mPhoneHidden').value = needsPhone && phoneField ? phoneField.value.trim() : '';
            }

            detailBuy.addEventListener('click', () => {
                $('#mName').textContent = detailBuy.dataset.name;
                $('#mPrice').textContent = rupiah(unit);
                $('#mQty').value = 1;
                $('#mQty').max = stock;

                buyForm.action = @json(route('toko.buy', ['product_id' => $product->id]));
                buyModal.classList.add('show');
                updateTotal();
            });

            $('#mQty').addEventListener('input', updateTotal);
            buyModal.addEventListener('change', (e) => {
                if (e.target.matches('input[type="radio"][name="detail-payment_method"]')) updateTotal();
            });
            buyModal.addEventListener('input', (e) => {
                if (e.target.matches('[data-pay-phone]')) updateTotal();
            });
            $('#buyCancel').addEventListener('click', () => buyModal.classList.remove('show'));
            $('#buyBtnCancel').addEventListener('click', () => buyModal.classList.remove('show'));
            buyModal.addEventListener('click', (e) => { if (e.target.id === 'buyModal') buyModal.classList.remove('show'); });
            $('#buyConfirm').addEventListener('click', (e) => {
                const method = selectedMethod();
                if (!method) { alert('Pilih metode pembayaran terlebih dahulu.'); return; }
                if (method.dataset.needsPhone === '1' && !$('#mPhoneHidden').value) {
                    alert('Masukkan nomor HP untuk metode e-money.');
                    return;
                }
                e.currentTarget.disabled = true;
                updateTotal();
                buyForm.submit();
            });
        })();
    </script>
@endpush
