<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\TransactionCustomerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TokoController extends Controller
{
    public function __construct(
        private readonly TransactionCustomerResolver $customerResolver,
        private readonly PaymentService $paymentService,
    ) {
    }

    // Menampilkan daftar produk dari database ke katalog pembeli.
    // Filter & urutan dibaca dari query string supaya bisa di-share dan
    // tombol Back browser tetap bekerja.
    public function index(Request $request)
    {
        $filters = $this->resolveCatalogFilters($request);

        $query = Product::with('category');

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('type', 'like', '%' . $q . '%')
                    ->orWhere('color', 'like', '%' . $q . '%')
                    ->orWhereHas('category', fn ($cat) => $cat->where('name', 'like', '%' . $q . '%'));
            });
        }

        if (! empty($filters['kategori'])) {
            $query->whereHas('category', fn ($cat) => $cat->whereIn('name', $filters['kategori']));
        }

        if (! empty($filters['jenis'])) {
            $query->whereIn('type', $filters['jenis']);
        }

        if (! empty($filters['ukuran'])) {
            $query->whereIn('size', $filters['ukuran']);
        }

        if (! empty($filters['warna'])) {
            $query->whereIn('color', $filters['warna']);
        }

        if ($filters['min'] !== null) {
            $query->where('price', '>=', $filters['min']);
        }

        if ($filters['max'] !== null) {
            $query->where('price', '<=', $filters['max']);
        }

        match ($filters['urut']) {
            'termurah' => $query->orderBy('price'),
            'termahal' => $query->orderByDesc('price'),
            'terlaris' => $query->withSum('transactionDetails as terjual', 'quantity')->orderByDesc('terjual'),
            default => $query->latest(),
        };

        $products = $query->get();

        // Opsi filter dibaca dinamis dari database.
        $filterOptions = [
            'kategori' => Category::withCount('products')
                ->orderBy('name')
                ->get()
                ->map(fn ($cat) => ['value' => $cat->name, 'label' => $cat->name, 'count' => $cat->products_count])
                ->all(),
            'jenis' => Product::whereNotNull('type')->orderBy('type')->distinct()->pluck('type')->all(),
            'ukuran' => Product::whereNotNull('size')->orderBy('size')->distinct()->pluck('size')->all(),
            'warna' => Product::whereNotNull('color')->orderBy('color')->distinct()->pluck('color')->all(),
            'harga_min' => (int) floor((float) Product::min('price')),
            'harga_max' => (int) ceil((float) Product::max('price')),
        ];

        // Daftar id produk yang sudah difavoritkan pengguna, supaya tombol hati
        // di katalog langsung tampil dalam keadaan aktif tanpa request tambahan.
        $favoritIds = [];

        if (Auth::check()) {
            $favoritIds = Favorite::where('user_id', Auth::id())
                ->pluck('product_id')
                ->all();
        }

        $paymentGroups = $this->paymentService->groups();

        return view('toko.index', compact('products', 'filters', 'filterOptions', 'favoritIds', 'paymentGroups'));
    }

    /**
     * Menormalkan parameter filter katalog dari query string menjadi bentuk
     * yang aman dipakai query database.
     *
     * @return array<string, mixed>
     */
    private function resolveCatalogFilters(Request $request): array
    {
        $asArray = static function ($value): array {
            if (is_array($value)) {
                return array_values(array_filter(array_map(
                    static fn ($item) => is_string($item) ? trim($item) : '',
                    $value
                ), static fn ($item) => $item !== ''));
            }

            return is_string($value) && trim($value) !== '' ? [trim($value)] : [];
        };

        $toString = static fn ($value): string => is_string($value) ? trim($value) : '';

        $toInt = static function ($value): ?int {
            if ($value === null || $value === '') {
                return null;
            }

            return is_numeric($value) ? (int) $value : null;
        };

        $validUrut = ['terbaru', 'termurah', 'termahal', 'terlaris'];
        $urut = $toString($request->query('urut'));

        return [
            'q' => $toString($request->query('q')),
            'kategori' => $asArray($request->query('kategori')),
            'jenis' => $asArray($request->query('jenis')),
            'ukuran' => $asArray($request->query('ukuran')),
            'warna' => $asArray($request->query('warna')),
            'min' => $toInt($request->query('min')),
            'max' => $toInt($request->query('max')),
            'urut' => in_array($urut, $validUrut, true) ? $urut : 'terbaru',
        ];
    }

    // Halaman "Pesanan Saya": riwayat transaksi milik pengguna yang sedang login
    public function pesanan()
    {
        // Transaksi hanya bisa dibaca berdasarkan user yang sedang login.
        // Id yang dipakai menyesuaikan skema FK `transactions.customer_id`.
        $customerIds = $this->customerResolver->customerIdsFor(Auth::user());

        $transactions = Transaction::whereIn('customer_id', $customerIds)
            ->with('payment')
            ->latest('date')
            ->get();

        return view('toko.pesanan', compact('transactions'));
    }

    // Detail produk: foto, deskripsi, harga, dan status favorit pengguna
    public function show(Product $product)
    {
        $product->load('category');

        $sudahFavorit = $this->apakahFavorit($product);

        $paymentGroups = $this->paymentService->groups();

        return view('toko.show', compact('product', 'sudahFavorit', 'paymentGroups'));
    }

    // Menambah atau menghapus produk dari daftar favorit pengguna
    public function toggleFavorite(Request $request, Product $product)
    {
        $userId = Auth::id();

        $favorit = Favorite::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($favorit) {
            $favorit->delete();
            $terfavorit = false;
            $pesan = 'Produk dihapus dari favorit.';
        } else {
            Favorite::create([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            $terfavorit = true;
            $pesan = 'Produk ditambahkan ke favorit.';
        }

        $jumlah = Favorite::where('product_id', $product->id)->count();

        // Permintaan dari tombol favorit memakai fetch() supaya tidak reload halaman,
        // sekaligus tetap ada fallback untuk submit biasa tanpa JavaScript.
        if ($request->expectsJson()) {
            return response()->json([
                'favorit' => $terfavorit,
                'jumlah' => $jumlah,
                'pesan' => $pesan,
            ]);
        }

        return back()->with('success', $pesan);
    }

    // Halaman "Produk Favorit": daftar produk yang ditandai pengguna
    public function favorit()
    {
        // Pakai join (bukan whereHas) karena urutan "paling baru difavoritkan"
        // membaca kolom favorites.created_at. Pada whereExists tabel favorites
        // tidak di-join ke query luar, sehingga MySQL menolak order by-nya.
        $products = Product::query()
            ->join('favorites', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->with('category')
            ->orderByDesc('favorites.created_at')
            ->select('products.*')
            ->get();

        return view('toko.favorit', compact('products'));
    }

    // Memproses pembelian produk (Form POST dengan Flash Message & Database Transaction)
    public function buyProduct(Request $request, $product_id)
    {
        $product = Product::findOrFail($product_id);

        // Ambil quantity dari input (default 1 jika tidak diisi), minimal 1
        $quantity = max(1, (int) $request->input('quantity', 1));

        $data = $request->validate([
            'payment_method' => 'required|string|max:30',
            'phone' => 'nullable|string|max:20',
        ]);

        // Server memvalidasi bahwa kode metode ada dan aktif.
        $method = PaymentMethod::query()->active()->where('code', $data['payment_method'])->first();

        if (! $method) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran tidak valid atau tidak aktif.',
            ]);
        }

        $phone = $data['phone'] ?? null;
        if ($method->group_code === PaymentService::GRUP_EMONEY) {
            if (! $phone || ! preg_match('/^08[0-9]{8,11}$/', preg_replace('/\s+/', '', $phone))) {
                throw ValidationException::withMessages([
                    'phone' => 'Nomor HP harus diawali 08 dan panjang 10-13 digit.',
                ]);
            }
        }

        // Validasi stok produk cukup untuk jumlah yang diminta
        if ($product->stock < $quantity) {
            return back()->with('error', 'Stok produk tidak mencukupi untuk jumlah yang diminta. Stok tersisa: ' . $product->stock . '.');
        }

        $total = $product->price * $quantity;

        // Stok, transaksi, dan detail disimpan atomik supaya tidak ada
        // transaksi tanpa detail (atau stok berkurang tanpa transaksi).
        DB::transaction(function () use ($product, $quantity, $method, $phone, $total) {
            // Kurangi stok produk
            $product->stock -= $quantity;
            $product->save();

            // Simpan record transaksi ke database
            $transaction = Transaction::create([
                'customer_id' => $this->customerResolver->customerIdFor(Auth::user()),
                'employee_id' => null,
                'date' => now(),
                'total' => $total,
                'payment_method' => $method->code,
                'status' => 'completed',
            ]);

            // Simpan item belanja ke transaction_details
            $transaction->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'subtotal' => $total,
            ]);

            // Simpan detail pembayaran simulasi (VA/kode gerai/4 digit HP)
            $this->paymentService->createFor($transaction, $method->code, $phone);
        });

        return redirect()->route('toko.pesanan')->with('success', 'Selamat! Pembelian berhasil. Total bayar: Rp ' . number_format($total, 0, ',', '.'));
    }

    // Helper kecil untuk mengecek apakah produk sudah ada di favorit pengguna
    private function apakahFavorit(Product $product): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->exists();
    }
}
