@extends('layouts.toko-shell', [
    'title' => 'Keranjang Belanja',
    'activeNav' => 'keranjang',
    'heading' => 'Keranjang Belanja',
    'subheading' => 'Produk yang sudah kamu pilih sebelum checkout.',
])

@section('content')
    @php
        // Emoji fallback per kategori, dipakai hanya bila produk tidak punya foto
        $emojiKategori = [
            'kaos' => '👕', 'kemeja' => '👔', 'baju' => '👕', 'celana' => '👖',
            'outerwear' => '🧥', 'jaket' => '🧥', 'hoodie' => '🧥', 'sweater' => '🧶',
            'atasan' => '👕', 'bawahan' => '👖', 'aksesoris' => '🧣', 'sepatu' => '👟',
            'topi' => '🧢', 'tas' => '🎒', 'kacamata' => '🕶️',
        ];
    @endphp

    <a href="{{ route('toko.index') }}" class="back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Kembali ke Katalog
    </a>

    @if($items->isEmpty())
        <div class="list">
            <div class="empty ico">
                <div class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                </div>
                <h2>Keranjang masih kosong</h2>
                <p>Produk yang kamu tambahkan akan muncul di sini dan tersimpan pada akunmu.</p>
                <a href="{{ route('toko.index') }}" class="btn">Mulai Belanja</a>
            </div>
        </div>
    @else
        <div class="summary">
            <div class="sum">
                <small>Jenis Produk</small>
                <b>{{ $items->count() }}</b>
            </div>
            <div class="sum">
                <small>Total Item</small>
                <b>{{ $items->sum('quantity') }}</b>
            </div>
            <div class="sum accent">
                <small>Total Belanja</small>
                <b>Rp {{ number_format($total, 0, ',', '.') }}</b>
            </div>
        </div>

        <div class="list">
            @foreach($items as $item)
                @php
                    $produk = $item->product;
                    $kategoriNama = strtolower((string) ($produk?->category?->name ?? ''));
                    $emoji = $emojiKategori[$kategoriNama] ?? '👕';
                @endphp
                <article class="item cart-item">
                    <div class="thumb">
                        @if($produk?->image)
                            <img src="{{ $produk->image_url }}" alt="{{ $produk->name }}" loading="lazy">
                        @else
                            {{ $emoji }}
                        @endif
                    </div>
                    <div>
                        <h3>{{ $produk?->name ?? 'Produk tidak tersedia' }}</h3>
                        <div class="sub">
                            {{ $produk?->type ?? '-' }} · {{ $produk?->size ?? '-' }} · {{ $produk?->color ?? '-' }}
                        </div>
                        <div class="sub">Rp {{ number_format($produk?->price ?? 0, 0, ',', '.') }} / pcs · Stok {{ $produk?->stock ?? 0 }}</div>
                        <div class="controls">
                            <form action="{{ route('toko.cart.update', $item) }}" method="POST">
                                @csrf
                                <label class="sub" for="qty-{{ $item->id }}">Jumlah</label>
                                <input id="qty-{{ $item->id }}" class="cart-qty" type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $produk?->stock ?? 1 }}">
                                <button class="btn" type="submit">Perbarui</button>
                            </form>
                            <form action="{{ route('toko.cart.remove', $item) }}" method="POST">
                                @csrf
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <div class="subtotal">
                        <small>Subtotal</small>
                        <b>Rp {{ number_format(($produk?->price ?? 0) * $item->quantity, 0, ',', '.') }}</b>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="panel">
            <div class="panel-head">
                <h2>Ringkasan Pembelian</h2>
                <span class="count">{{ $items->sum('quantity') }} item</span>
            </div>
            <div class="item-body">
                <form action="{{ route('toko.cart.checkout') }}" method="POST" class="actions" id="checkoutForm">
                    @csrf
                    <label class="pay-label">Metode Pembayaran</label>
                    @include('toko._payment-accordion', ['paymentGroups' => $paymentGroups, 'prefix' => 'cart-'])
                    <input type="hidden" name="payment_method" id="cartPayHidden" value="">
                    <input type="hidden" name="phone" id="cartPhoneHidden" value="">
                    @error('payment_method')<div class="flash err"><span>{{ $message }}</span></div>@enderror
                    @error('phone')<div class="flash err"><span>{{ $message }}</span></div>@enderror
                    <button class="btn orange" type="submit" id="checkoutBtn">Checkout Sekarang</button>
                </form>
                <form action="{{ route('toko.cart.clear') }}" method="POST">
                    @csrf
                    <button class="btn ghost" type="submit">Kosongkan Keranjang</button>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('checkoutForm');
            if (!form) return;

            const acc = form.querySelector('[data-pay-acc]');
            const groups = Array.from(acc.querySelectorAll('[data-pay-group]'));

            // Akordeon: satu kategori terbuka setiap saat.
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

            const hiddenMethod = document.getElementById('cartPayHidden');
            const hiddenPhone = document.getElementById('cartPhoneHidden');
            const phoneField = form.querySelector('[data-pay-phone]');

            function selectedMethod() {
                return form.querySelector('input[name="cart-payment_method"]:checked');
            }

            function sync() {
                const method = selectedMethod();
                hiddenMethod.value = method ? method.value : '';
                const needsPhone = method && method.dataset.needsPhone === '1';
                const group = phoneField ? phoneField.closest('.pay-group') : null;
                if (group) group.hidden = !needsPhone;
                hiddenPhone.value = needsPhone && phoneField ? phoneField.value.trim() : '';
            }

            form.addEventListener('change', (e) => {
                if (e.target.matches('input[type="radio"][name="cart-payment_method"]')) sync();
            });
            form.addEventListener('input', (e) => {
                if (e.target.matches('[data-pay-phone]')) sync();
            });
            form.addEventListener('submit', (e) => {
                sync();
                if (!hiddenMethod.value) {
                    e.preventDefault();
                    alert('Pilih metode pembayaran terlebih dahulu.');
                    return;
                }
                if (selectedMethod().dataset.needsPhone === '1' && !hiddenPhone.value) {
                    e.preventDefault();
                    alert('Masukkan nomor HP untuk metode e-money.');
                    return;
                }
                document.getElementById('checkoutBtn').disabled = true;
            });

            sync();
        })();
    </script>
@endpush
