@extends('layouts.toko-shell', [
    'title' => 'Katalog Produk',
    'activeNav' => 'katalog',
    'heading' => 'Katalog Produk',
    'subheading' => 'Temukan pakaian sesuai kebutuhan dan kebutuhanmu.',
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

    <div class="content-grid">
        <!-- FILTER -->
        <aside class="filters">
            <button class="acc" type="button" data-acc="accKategori">Kategori <span>⌄</span></button>
            <div class="checks" id="accKategori">
                <label><input type="checkbox" data-kategori="__all__" checked> Semua Kategori</label>
                @foreach($categories as $categoryName)
                    <label><input type="checkbox" data-kategori="{{ $categoryName }}"> {{ $categoryName }}</label>
                @endforeach
            </div>

            <button class="acc" type="button" data-acc="accHarga">Harga Produk <span>⌄</span></button>
            <div class="checks" id="accHarga">
                <label><input type="checkbox" value="all" data-range="all" checked> Semua Harga</label>
                <label><input type="checkbox" value="0-100000" data-range="0-100000"> Di bawah Rp 100.000</label>
                <label><input type="checkbox" value="100000-250000" data-range="100000-250000"> Rp 100.000 - 250.000</label>
                <label><input type="checkbox" value="250000-500000" data-range="250000-500000"> Rp 250.000 - 500.000</label>
                <label><input type="checkbox" value="500000-1000000" data-range="500000-1000000"> Rp 500.000 - 1.000.000</label>
            </div>
            <div class="range-title">Custom Price Range :</div>
            <div class="slider">
                <span class="bubble" id="bubble">Rp 1.000.000</span>
                <div class="track"></div>
                <div class="fill" id="fill"></div>
                <input type="range" id="rMin" min="0" max="1000000" value="0" aria-label="Harga minimum">
                <input type="range" id="rMax" min="0" max="1000000" value="1000000" aria-label="Harga maksimum">
            </div>
            <div class="minmax">
                <input id="iMin" value="Rp 0" aria-label="Harga minimum">
                <span>—</span>
                <input id="iMax" value="Rp 1.000.000" aria-label="Harga maksimum">
            </div>

            <button class="btn block" id="apply" type="button">Terapkan</button>
            <button class="btn block ghost" id="reset" type="button">Reset</button>
        </aside>

        <!-- LIST -->
        <section class="list">
            <div class="head">
                <div class="crumb">
                    Katalog › <b id="crumbFilter">Semua Produk</b>
                    <span class="count">Menampilkan <b id="count">{{ $products->count() }}</b> produk</span>
                </div>
                <label class="search">🔍 <input id="search" placeholder="Cari..." aria-label="Cari produk"></label>
            </div>

            <div class="grid" id="grid">
                @forelse($products as $product)
                    @php
                        $kategoriNama = $product->category->name ?? '';
                        $emoji = $emojiKategori[Str::lower($kategoriNama)] ?? '👕';
                    @endphp
                    <article class="card"
                             data-harga="{{ (int) $product->price }}"
                             data-nama="{{ Str::lower($product->name . ' ' . $kategoriNama . ' ' . ($product->type ?? '') . ' ' . ($product->color ?? '')) }}"
                             data-kategori="{{ $kategoriNama }}">
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
                            @if($isPelanggan)
                                <form action="{{ route('toko.cart.add', $product->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="buy" type="submit" @disabled($product->stock <= 0)>+ Keranjang</button>
                                </form>
                            @elseif(! Auth::check())
                                <form action="{{ route('toko.cart.intent', $product->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="return_to" value="{{ url()->current() }}">
                                    <button class="buy" type="submit" @disabled($product->stock <= 0)>+ Keranjang</button>
                                </form>
                            @endif
                            <button class="buy" type="button" data-buy="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ (int) $product->price }}"
                                    data-stock="{{ (int) $product->stock }}"
                                    @disabled($product->stock <= 0)>
                                {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="empty">Belum ada produk yang tersedia di katalog saat ini.</div>
                @endforelse
                <div class="empty" id="noMatch" style="display:none">Produk tidak ditemukan. Ubah filter harga atau kata kunci.</div>
            </div>
        </section>
    </div>

    <!-- MODAL PEMBELIAN -->
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
                <button class="btn ghost" type="button" id="buyCancel">Batal</button>
                <button class="btn orange" type="button" id="buyConfirm">Konfirmasi Pembelian</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
        const BUY_URL = @json(route('toko.buy', ['product_id' => 0]));
        const MAX_HARGA = 1000000;

        /* ================= FILTER KATALOG ================= */
        const state = { q: '', min: 0, max: MAX_HARGA, ranges: ['all'], kategori: ['__all__'] };
        const cards = () => $all('#grid .card');
        const noMatch = $('#noMatch');

        function applyFilter() {
            const q = state.q.toLowerCase().trim();
            let visible = 0;

            cards().forEach((card) => {
                const harga = Number(card.dataset.harga) || 0;
                const nama = card.dataset.nama || '';
                const kategori = card.dataset.kategori || '';

                const cocokTeks = !q || nama.includes(q);
                const cocokHarga = harga >= state.min && harga <= state.max
                    && (state.ranges.includes('all')
                        || state.ranges.some((r) => {
                            const [a, b] = r.split('-').map(Number);
                            return harga >= a && harga <= b;
                        }));
                const cocokKategori = state.kategori.includes('__all__') || state.kategori.includes(kategori);

                const tampil = cocokTeks && cocokHarga && cocokKategori;
                card.style.display = tampil ? '' : 'none';
                if (tampil) visible++;
            });

            $('#count').textContent = visible;
            if (noMatch) noMatch.style.display = visible === 0 ? '' : 'none';
        }

        /* Checkbox kategori */
        $all('#accKategori input').forEach((box) => {
            box.addEventListener('change', () => {
                const boxes = $all('#accKategori input');
                if (box.dataset.kategori === '__all__' && box.checked) {
                    boxes.slice(1).forEach((b) => (b.checked = false));
                } else if (box.dataset.kategori !== '__all__') {
                    boxes[0].checked = false;
                }
                if (!boxes.some((b) => b.checked)) boxes[0].checked = true;

                state.kategori = boxes.filter((b) => b.checked).map((b) => b.dataset.kategori);
                applyFilter();
            });
        });

        /* Checkbox rentang harga */
        $all('#accHarga input').forEach((box) => {
            box.addEventListener('change', () => {
                const boxes = $all('#accHarga input');
                if (box.value === 'all' && box.checked) boxes.slice(1).forEach((b) => (b.checked = false));
                else if (box.value !== 'all') boxes[0].checked = false;
                if (!boxes.some((b) => b.checked)) boxes[0].checked = true;

                state.ranges = boxes.filter((b) => b.checked).map((b) => b.value);
                applyFilter();
            });
        });

        /* Slider custom range */
        function syncSlider() {
            let a = +$('#rMin').value;
            let b = +$('#rMax').value;
            if (a > b) [a, b] = [b, a];

            const pct = (v) => (v / MAX_HARGA) * 100;
            $('#fill').style.left = pct(a) + '%';
            $('#fill').style.width = pct(b) - pct(a) + '%';
            $('#bubble').style.left = pct(b) + '%';
            $('#bubble').textContent = rupiah(b);
            $('#iMin').value = rupiah(a);
            $('#iMax').value = rupiah(b);
            return [a, b];
        }
        ['#rMin', '#rMax'].forEach((s) => $(s).addEventListener('input', syncSlider));
        ['#iMin', '#iMax'].forEach((s) => $(s).addEventListener('change', () => {
            const parse = (v) => Math.min(MAX_HARGA, Math.max(0, parseInt(String(v).replace(/\D/g, ''), 10) || 0));
            $('#rMin').value = parse($('#iMin').value);
            $('#rMax').value = parse($('#iMax').value);
            syncSlider();
        }));

        $('#apply').addEventListener('click', () => {
            const [a, b] = syncSlider();
            state.min = a;
            state.max = b;
            applyFilter();
        });

        $('#reset').addEventListener('click', () => {
            state.q = '';
            state.min = 0;
            state.max = MAX_HARGA;
            state.ranges = ['all'];
            state.kategori = ['__all__'];
            $('#rMin').value = 0;
            $('#rMax').value = MAX_HARGA;
            $('#search').value = '';
            $all('#accHarga input').forEach((b) => (b.checked = b.value === 'all'));
            $all('#accKategori input').forEach((b) => (b.checked = b.dataset.kategori === '__all__'));
            syncSlider();
            applyFilter();
        });

        /* Search katalog (satu-satunya input, di header area katalog) */
        $('#search').addEventListener('input', (e) => {
            state.q = e.target.value;
            applyFilter();
        });

        /* Accordion filter */
        $all('[data-acc]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const panel = document.getElementById(btn.dataset.acc);
                const tampil = panel.style.display !== 'none';
                panel.style.display = tampil ? 'none' : 'flex';
            });
        });

        /* ================= MODAL PEMBELIAN ================= */
        let buyId = null;
        let buyUnit = 0;
        let buyStock = 0;

        $all('#grid .buy').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                buyId = btn.dataset.buy;
                buyUnit = Number(btn.dataset.price) || 0;
                buyStock = Number(btn.dataset.stock) || 0;

                $('#mName').textContent = btn.dataset.name;
                $('#mPrice').textContent = rupiah(buyUnit);
                $('#mQty').value = 1;
                $('#mQty').max = buyStock;
                $('#mPay').value = 'E-Wallet';
                updateTotal();

                $('#buyForm').action = BUY_URL + buyId;
                $('#buyModal').classList.add('show');
            });
        });

        function updateTotal() {
            const qty = Math.max(1, Math.min(+$('#mQty').value || 1, buyStock || 1));
            $('#mTotal').textContent = rupiah(buyUnit * qty);
            $('#mQtyHidden').value = qty;
            $('#mPayHidden').value = $('#mPay').value;
        }
        $('#mQty').addEventListener('input', updateTotal);
        $('#mPay').addEventListener('change', updateTotal);
        $('#buyCancel').addEventListener('click', () => $('#buyModal').classList.remove('show'));
        $('#buyModal').addEventListener('click', (e) => { if (e.target.id === 'buyModal') $('#buyModal').classList.remove('show'); });
        $('#buyConfirm').addEventListener('click', () => { updateTotal(); $('#buyForm').submit(); });

        /* Init */
        syncSlider();
        applyFilter();
    </script>
@endpush
