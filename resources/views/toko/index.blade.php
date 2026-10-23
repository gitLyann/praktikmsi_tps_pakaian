@extends('layouts.toko-shell', [
    'title' => 'Katalog Produk',
    'activeNav' => 'katalog',
    'heading' => 'Katalog Produk',
    'subheading' => 'Temukan pakaian sesuai kebutuhan dan kebutuhanmu.',
])

@php
    // Label & jumlah filter aktif untuk chip ringkasan.
    $chipLabels = [
        'kategori' => 'Kategori',
        'jenis' => 'Jenis',
        'ukuran' => 'Ukuran',
        'warna' => 'Warna',
    ];
    $activeCount = count($filters['kategori']) + count($filters['jenis'])
        + count($filters['ukuran']) + count($filters['warna'])
        + ($filters['q'] !== '' ? 1 : 0)
        + ($filters['min'] !== null ? 1 : 0)
        + ($filters['max'] !== null ? 1 : 0)
        + ($filters['urut'] !== 'terbaru' ? 1 : 0);

    // Bangun URL saat satu nilai filter dilepas dari chip.
    $removeFilterUrl = function (string $key, ?string $value = null) use ($filters) {
        $params = array_filter([
            'q' => $filters['q'],
            'kategori' => $filters['kategori'],
            'jenis' => $filters['jenis'],
            'ukuran' => $filters['ukuran'],
            'warna' => $filters['warna'],
            'min' => $filters['min'],
            'max' => $filters['max'],
            'urut' => $filters['urut'] === 'terbaru' ? null : $filters['urut'],
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        if ($value !== null && isset($params[$key]) && is_array($params[$key])) {
            $params[$key] = array_values(array_diff($params[$key], [$value]));
            if ($params[$key] === []) {
                unset($params[$key]);
            }
        } else {
            unset($params[$key]);
        }

        return route('toko.index', $params);
    };
@endphp

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

    <div class="content-grid">
        <!-- Lapisan gelap penutup panel filter -->
        <div class="filter-overlay" id="filterOverlay" hidden></div>

        <!-- FILTER (panel lipat, ditangani server lewat query string) -->
        <aside class="filters" id="filterPanel" hidden>
            <form action="{{ route('toko.index') }}" method="GET" id="filterForm">
                <div class="filter-head">
                    <h2>Filter Produk</h2>
                    <button type="button" class="x" id="filterClose" aria-label="Tutup filter">✕</button>
                </div>

                <label class="filter-search" for="search">
                    <span aria-hidden="true">🔍</span>
                    <input type="search" id="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari produk..." autocomplete="off">
                </label>

                <button class="acc" type="button" data-acc="accKategori">Kategori <span>⌄</span></button>
                <div class="checks" id="accKategori">
                    @foreach($filterOptions['kategori'] as $opt)
                        <label>
                            <input type="checkbox" name="kategori[]" value="{{ $opt['value'] }}"
                                   @checked(in_array($opt['value'], $filters['kategori'], true))>
                            {{ $opt['label'] }} <span class="opt-count">{{ $opt['count'] }}</span>
                        </label>
                    @endforeach
                </div>

                @if(! empty($filterOptions['jenis']))
                    <button class="acc" type="button" data-acc="accJenis">Jenis <span>⌄</span></button>
                    <div class="checks" id="accJenis">
                        @foreach($filterOptions['jenis'] as $jenis)
                            <label>
                                <input type="checkbox" name="jenis[]" value="{{ $jenis }}"
                                       @checked(in_array($jenis, $filters['jenis'], true))>
                                {{ $jenis }}
                            </label>
                        @endforeach
                    </div>
                @endif

                @if(! empty($filterOptions['ukuran']))
                    <button class="acc" type="button" data-acc="accUkuran">Ukuran <span>⌄</span></button>
                    <div class="checks" id="accUkuran">
                        @foreach($filterOptions['ukuran'] as $ukuran)
                            <label>
                                <input type="checkbox" name="ukuran[]" value="{{ $ukuran }}"
                                       @checked(in_array($ukuran, $filters['ukuran'], true))>
                                {{ $ukuran }}
                            </label>
                        @endforeach
                    </div>
                @endif

                @if(! empty($filterOptions['warna']))
                    <button class="acc" type="button" data-acc="accWarna">Warna <span>⌄</span></button>
                    <div class="checks" id="accWarna">
                        @foreach($filterOptions['warna'] as $warna)
                            <label>
                                <input type="checkbox" name="warna[]" value="{{ $warna }}"
                                       @checked(in_array($warna, $filters['warna'], true))>
                                {{ $warna }}
                            </label>
                        @endforeach
                    </div>
                @endif

                <button class="acc" type="button" data-acc="accHarga">Harga Produk <span>⌄</span></button>
                <div class="checks" id="accHarga">
                    <div class="minmax">
                        <input id="rMin" name="min" type="number" inputmode="numeric" min="0"
                               value="{{ $filters['min'] ?? $filterOptions['harga_min'] }}"
                               placeholder="{{ $filterOptions['harga_min'] }}" aria-label="Harga minimum">
                        <span>—</span>
                        <input id="rMax" name="max" type="number" inputmode="numeric" min="0"
                               value="{{ $filters['max'] ?? $filterOptions['harga_max'] }}"
                               placeholder="{{ $filterOptions['harga_max'] }}" aria-label="Harga maksimum">
                    </div>
                </div>

                <label class="filter-sort" for="urut">Urutkan</label>
                <select id="urut" name="urut" class="filter-select">
                    <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru</option>
                    <option value="termurah" @selected($filters['urut'] === 'termurah')>Harga terendah</option>
                    <option value="termahal" @selected($filters['urut'] === 'termahal')>Harga tertinggi</option>
                    <option value="terlaris" @selected($filters['urut'] === 'terlaris')>Terlaris</option>
                </select>

                <button class="btn block" id="apply" type="submit">Terapkan</button>
                <a class="btn block ghost" id="reset" href="{{ route('toko.index') }}">Reset</a>
            </form>
        </aside>

        <!-- LIST -->
        <section class="list">
            <div class="head">
                <div class="crumb">
                    <div class="crumb-top">
                        <button type="button" class="btn-filter" id="filterOpen" aria-expanded="false" aria-controls="filterPanel">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="6" y2="6"/><line x1="7" x2="17" y1="12" y2="12"/><line x1="10" x2="14" y1="18" y2="18"/></svg>
                            Filter @if($activeCount > 0)<span class="filter-badge">{{ $activeCount }}</span>@endif
                        </button>
                        <span class="crumb-text">Katalog › <b id="crumbFilter">Semua Produk</b></span>
                    </div>
                    <span class="count">Menampilkan <b id="count">{{ $products->count() }}</b> produk</span>
                </div>
            </div>

            @if($activeCount > 0)
                <div class="active-chips" id="activeChips">
                    <span class="chips-label">Filter aktif:</span>
                    @if($filters['q'] !== '')
                        <a class="chip" href="{{ $removeFilterUrl('q') }}">Cari: "{{ $filters['q'] }}" <span aria-hidden="true">✕</span></a>
                    @endif
                    @foreach($chipLabels as $key => $label)
                        @foreach($filters[$key] as $value)
                            <a class="chip" href="{{ $removeFilterUrl($key, $value) }}">{{ $label }}: {{ $value }} <span aria-hidden="true">✕</span></a>
                        @endforeach
                    @endforeach
                    @if($filters['min'] !== null)
                        <a class="chip" href="{{ $removeFilterUrl('min') }}">Min: Rp {{ number_format($filters['min'], 0, ',', '.') }} <span aria-hidden="true">✕</span></a>
                    @endif
                    @if($filters['max'] !== null)
                        <a class="chip" href="{{ $removeFilterUrl('max') }}">Max: Rp {{ number_format($filters['max'], 0, ',', '.') }} <span aria-hidden="true">✕</span></a>
                    @endif
                    @if($filters['urut'] !== 'terbaru')
                        <a class="chip" href="{{ $removeFilterUrl('urut') }}">Urut: {{ ucfirst($filters['urut']) }} <span aria-hidden="true">✕</span></a>
                    @endif
                    <a class="chip reset" id="resetChips" href="{{ route('toko.index') }}">Reset semua</a>
                </div>
            @endif

            <div class="grid" id="grid">
                @forelse($products as $product)
                    @php
                        $kategoriNama = $product->category->name ?? '';
                        $emoji = $emojiKategori[Str::lower($kategoriNama)] ?? '👕';
                    @endphp
                    <article class="card" data-buy="{{ $product->id }}">
                        <div class="thumb">
                            {{-- Tombol favorit memakai fetch() supaya tidak me-reload katalog --}}
                            <button type="button" class="fav" data-fav="{{ route('toko.favorit.toggle', $product->id) }}"
                                    aria-pressed="{{ in_array($product->id, $favoritIds) ? 'true' : 'false' }}"
                                    aria-label="Tambahkan {{ $product->name }} ke favorit">
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
                            {{-- Tombol "+ Keranjang" HANYA membuka modal; penyimpanan terjadi
                                 setelah pengguna menekan "Masukkan keranjang" di dalam modal. --}}
                            <button class="buy" type="button" data-cart-open="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ (int) $product->price }}"
                                    data-stock="{{ (int) $product->stock }}"
                                    data-add-url="{{ $isPelanggan ? route('toko.cart.add', $product->id) : '' }}"
                                    data-intent-url="{{ ! Auth::check() ? route('toko.cart.intent', $product->id) : '' }}"
                                    data-return="{{ url()->current() }}"
                                    @disabled($product->stock <= 0)>+ Keranjang</button>
                            <button class="buy" type="button" data-buy-open="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ (int) $product->price }}"
                                    data-stock="{{ (int) $product->stock }}"
                                    @disabled($product->stock <= 0)>
                                {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="empty">Belum ada produk yang cocok dengan filter. Coba ubah atau reset filter.</div>
                @endforelse
            </div>
        </section>
    </div>

    <!-- MODAL KONFIRMASI KERANJANG -->
    <div class="modal-b" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
        <div class="modal-box">
            <div class="modal-head">
                <h2 id="cartTitle">Tambah ke Keranjang</h2>
                <button type="button" class="x" id="cartCancel" aria-label="Tutup">✕</button>
            </div>
            <div class="rowline"><span>Produk</span><b id="cName">-</b></div>
            <div class="rowline"><span>Harga Satuan</span><b id="cPrice">-</b></div>
            <label for="cQty">Jumlah</label>
            <input type="number" id="cQty" min="1" value="1">
            <form action="" method="POST" id="cartForm">
                @csrf
                <input type="hidden" name="quantity" id="cQtyHidden" value="1">
            </form>
            <div class="modal-act">
                <button class="btn ghost" type="button" id="cartBtnCancel">Batal</button>
                <button class="btn orange" type="button" id="cartConfirm">Masukkan keranjang</button>
            </div>
        </div>
    </div>

    <!-- MODAL PEMBELIAN -->
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
            @include('toko._payment-accordion', ['paymentGroups' => $paymentGroups, 'prefix' => 'buy-'])

            <div class="rowline pay-total"><span>Total Bayar</span><b id="mTotal">-</b></div>
            <form action="" method="POST" id="buyForm">
                @csrf
                <input type="hidden" name="quantity" id="mQtyHidden" value="1">
                <input type="hidden" name="payment_method" id="mPayHidden" value="">
                <input type="hidden" name="phone" id="mPhoneHidden" value="">
            </form>
            <div class="modal-act">
                <button class="btn ghost" type="button" id="buyBtnCancel">Batal</button>
                <button class="btn orange" type="button" id="buyConfirm">Konfirmasi Pembelian</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
            const BUY_URL = @json(route('toko.buy', ['product_id' => 0]));
            const IS_PELANGGAN = @json($isPelanggan);
            const IS_GUEST = @json(! Auth::check());

            /* ================= PANEL FILTER (server-side) ================= */
            const filterPanel = $('#filterPanel');
            const filterOpen = $('#filterOpen');
            const filterClose = $('#filterClose');
            const filterOverlay = $('#filterOverlay');

            function setFilterPanel(open) {
                if (!filterPanel || !filterOpen) return;
                filterPanel.hidden = !open;
                if (filterOverlay) filterOverlay.hidden = !open;
                filterOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('filter-open', open);
                if (open) {
                    const first = $('#search');
                    if (first) first.focus();
                }
            }

            if (filterOpen) filterOpen.addEventListener('click', () => setFilterPanel(filterPanel.hidden));
            if (filterClose) filterClose.addEventListener('click', () => setFilterPanel(false));
            if (filterOverlay) filterOverlay.addEventListener('click', () => setFilterPanel(false));

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && filterPanel && !filterPanel.hidden) setFilterPanel(false);
            });

            /* Accordion di dalam panel filter */
            $all('[data-acc]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const panel = document.getElementById(btn.dataset.acc);
                    const open = panel.hasAttribute('hidden');
                    if (open) { panel.removeAttribute('hidden'); } else { panel.setAttribute('hidden', ''); }
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            });

            /* ================= MODAL KERANJANG ================= */
            const cartModal = $('#cartModal');
            const cartForm = $('#cartForm');
            let cartSubmitter = null;

            function closeCart() {
                cartModal.classList.remove('show');
                if (cartSubmitter) cartSubmitter.disabled = false;
            }

            $all('[data-cart-open]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    cartSubmitter = btn;
                    btn.disabled = true;

                    $('#cName').textContent = btn.dataset.name;
                    $('#cPrice').textContent = rupiah(btn.dataset.price);
                    $('#cQty').value = 1;
                    $('#cQty').max = btn.dataset.stock;

                    cartForm.action = IS_PELANGGAN
                        ? btn.dataset.addUrl
                        : btn.dataset.intentUrl;

                    const existing = cartForm.querySelector('input[name="return_to"]');
                    if (IS_GUEST && !existing) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'return_to';
                        input.value = btn.dataset.return;
                        cartForm.appendChild(input);
                    }

                    cartModal.classList.add('show');
                });
            });

            function syncCartQty() {
                const max = Number($('#cQty').max) || 1;
                const qty = Math.max(1, Math.min(+$('#cQty').value || 1, max));
                $('#cQtyHidden').value = qty;
            }
            $('#cQty').addEventListener('input', syncCartQty);
            $('#cQty').addEventListener('change', syncCartQty);

            $('#cartCancel').addEventListener('click', closeCart);
            $('#cartBtnCancel').addEventListener('click', closeCart);
            cartModal.addEventListener('click', (e) => { if (e.target.id === 'cartModal') closeCart(); });
            $('#cartConfirm').addEventListener('click', (e) => {
                e.currentTarget.disabled = true;
                syncCartQty();
                cartForm.submit();
            });

            /* ================= MODAL PEMBELIAN ================= */
            const buyModal = $('#buyModal');
            const buyForm = $('#buyForm');
            let buyUnit = 0;
            let buyStock = 0;
            let buySubmitter = null;

            function closeBuy() {
                buyModal.classList.remove('show');
                if (buySubmitter) buySubmitter.disabled = false;
            }

            $all('[data-buy-open]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    buySubmitter = btn;
                    btn.disabled = true;

                    buyUnit = Number(btn.dataset.price) || 0;
                    buyStock = Number(btn.dataset.stock) || 0;

                    $('#mName').textContent = btn.dataset.name;
                    $('#mPrice').textContent = rupiah(buyUnit);
                    $('#mQty').value = 1;
                    $('#mQty').max = buyStock;

                    buyForm.action = BUY_URL + btn.dataset.buyOpen;
                    buyModal.classList.add('show');
                    syncPay();
                });
            });

            /* Akordeon metode pembayaran: satu kategori terbuka setiap saat. */
            function initAccordion(root) {
                if (!root) return;
                const groups = Array.from(root.querySelectorAll('[data-pay-group]'));
                groups.forEach((group) => {
                    const toggle = group.querySelector('[data-pay-toggle]');
                    const bodyId = toggle.dataset.payToggle;
                    const body = document.getElementById(bodyId);
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
                            if (firstRadio && !body.querySelector('input[type="radio"]:checked')) {
                                firstRadio.checked = true;
                            }
                        }
                    });
                });
            }
            initAccordion(document.querySelector('#buyModal [data-pay-acc]'));

            /* Sinkronkan qty & metode terpilih ke input tersembunyi. */
            function selectedMethod() {
                return buyModal.querySelector('input[name="buy-payment_method"]:checked');
            }

            function syncPay() {
                const max = buyStock || 1;
                const qty = Math.max(1, Math.min(+$('#mQty').value || 1, max));
                $('#mTotal').textContent = rupiah(buyUnit * qty);
                $('#mQtyHidden').value = qty;

                const method = selectedMethod();
                $('#mPayHidden').value = method ? method.value : '';

                const phoneField = buyModal.querySelector('[data-pay-phone]');
                const needsPhone = method && method.dataset.needsPhone === '1';
                const group = phoneField ? phoneField.closest('.pay-group') : null;
                $('#mPhoneHidden').value = needsPhone && phoneField ? phoneField.value.trim() : '';
                if (group) group.hidden = !needsPhone;
            }

            $('#mQty').addEventListener('input', syncPay);
            buyModal.addEventListener('change', (e) => {
                if (e.target.matches('input[type="radio"][name="buy-payment_method"]')) syncPay();
            });
            buyModal.addEventListener('input', (e) => {
                if (e.target.matches('[data-pay-phone]')) syncPay();
            });

            $('#buyCancel').addEventListener('click', closeBuy);
            $('#buyBtnCancel').addEventListener('click', closeBuy);
            buyModal.addEventListener('click', (e) => { if (e.target.id === 'buyModal') closeBuy(); });

            $('#buyConfirm').addEventListener('click', (e) => {
                const method = selectedMethod();
                if (!method) { alert('Pilih metode pembayaran terlebih dahulu.'); return; }
                if (method.dataset.needsPhone === '1' && !$('#mPhoneHidden').value) {
                    alert('Masukkan nomor HP untuk metode e-money.');
                    return;
                }
                e.currentTarget.disabled = true;
                syncPay();
                buyForm.submit();
            });
        })();
    </script>
@endpush
