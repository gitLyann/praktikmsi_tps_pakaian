<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\TransactionCustomerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct(private readonly TransactionCustomerResolver $customerResolver)
    {
    }

    /**
     * Menyimpan niat "tambah ke keranjang" untuk tamu, lalu mengarahkan ke
     * login. Setelah login, item ditambahkan otomatis oleh AuthController.
     */
    public function intent(Request $request, Product $product)
    {
        $request->session()->put('cart.intent', [
            'product_id' => $product->id,
            'quantity' => max(1, (int) $request->input('quantity', 1)),
            'return_to' => $this->safeReturnTo($request->input('return_to')),
        ]);

        if (Auth::check()) {
            // Keranjang hanya milik pelanggan, jadi staff/admin dibembalikan
            // ke katalog tanpa menyentuh tabel carts.
            if (Auth::user()->role !== 'pelanggan') {
                $request->session()->forget('cart.intent');

                return redirect()->route('toko.index')
                    ->with('error', 'Keranjang hanya tersedia untuk pelanggan.');
            }

            return $this->addFromIntent($request);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Hanya alamat internal aplikasi yang boleh dipakai sebagai tujuan kembali,
     * supaya tidak bisa diarahkan ke situs luar.
     */
    private function safeReturnTo(?string $url): string
    {
        $fallback = route('toko.index');

        if (! $url) {
            return $fallback;
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $fallback;
        }

        return $url;
    }

    /**
     * Menjalankan niat keranjang yang tertunda (dipakai setelah login tamu).
     */
    public function addFromIntent(Request $request)
    {
        $intent = $request->session()->pull('cart.intent');

        if (!$intent) {
            return redirect()->route('toko.index');
        }

        // Keranjang hanya untuk pelanggan, siapa pun yang lain tidak boleh
        // membuat baris carts.
        if (Auth::user()?->role !== 'pelanggan') {
            return redirect()->route('toko.index')
                ->with('error', 'Keranjang hanya tersedia untuk pelanggan.');
        }

        $product = Product::find($intent['product_id']);

        if (!$product) {
            return redirect()->route('toko.index')->with('error', 'Produk tidak tersedia.');
        }

        $qty = (int) $intent['quantity'];
        $cart = Cart::firstOrNew([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        $newQty = $cart->exists ? ($cart->quantity + $qty) : $qty;
        if ($newQty < 1) {
            $newQty = 1;
        }
        if ($product->stock < $newQty) {
            return redirect()->route('toko.index')->with('error', 'Stok produk tidak mencukupi.');
        }

        $cart->quantity = $newQty;
        $cart->user_id = Auth::id();
        $cart->product_id = $product->id;
        $cart->save();

        return redirect()
            ->to($this->safeReturnTo($intent['return_to'] ?? null))
            ->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function index()
    {
        $items = Auth::user()->carts()
            ->with('product.category')
            ->get();

        $total = $items->sum(function ($item) {
            return ($item->product->price ?? 0) * $item->quantity;
        });

        return view('toko.cart', compact('items', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        $qty = (int) ($request->input('quantity', 1));
        if ($qty < 1) {
            $qty = 1;
        }
        if ($product->stock < $qty) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }

        $cart = Cart::firstOrNew([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        $newQty = $cart->exists ? ($cart->quantity + $qty) : $qty;
        if ($newQty < 1) {
            $newQty = 1;
        }
        if ($product->stock < $newQty) {
            return back()->with('error', 'Stok produk tidak mencukupi untuk jumlah di keranjang.');
        }

        $cart->quantity = $newQty;
        $cart->user_id = Auth::id();
        $cart->product_id = $product->id;
        $cart->save();

        if ($request->expectsJson()) {
            $count = Auth::user()->carts()->sum('quantity');
            return response()->json([
                'success' => true,
                'message' => 'Produk ditambahkan ke keranjang.',
                'count' => $count,
            ]);
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, Cart $cart)
    {
        // Route binding sudah membatasi keranjang milik user yang login.
        if ($cart->user_id !== Auth::id()) {
            abort(404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->input('quantity');
        $product = $cart->product;

        if (!$product) {
            abort(404);
        }
        if ($product->stock < $qty) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }

        $cart->quantity = $qty;
        $cart->save();

        return back()->with('success', 'Jumlah keranjang diperbarui.');
    }

    public function remove(Cart $cart)
    {
        // Route binding sudah membatasi keranjang milik user yang login.
        if ($cart->user_id !== Auth::id()) {
            abort(404);
        }

        $cart->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    public function clear()
    {
        Auth::user()->carts()->delete();

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $items = $user->carts()->with('product')->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Keranjang kosong.');
        }

        $paymentMethod = $request->input('payment_method', 'online');

        $transaction = DB::transaction(function () use ($items, $user, $paymentMethod) {
            $total = 0;

            foreach ($items as $item) {
                $product = $item->product()->lockForUpdate()->first();

                if (!$product) {
                    abort(404);
                }
                if ($product->stock < $item->quantity) {
                    abort(409, 'Stok tidak mencukupi untuk salah satu produk di keranjang.');
                }

                $total += $product->price * $item->quantity;
            }

            $transaction = Transaction::create([
                'customer_id' => $this->customerResolver->customerIdFor($user),
                'employee_id' => null,
                'date' => now(),
                'total' => $total,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
            ]);

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->stock < $item->quantity) {
                    abort(409, 'Stok tidak mencukupi saat checkout.');
                }

                $product->stock -= $item->quantity;
                $product->save();

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'subtotal' => $product->price * $item->quantity,
                ]);
            }

            $user->carts()->delete();

            return $transaction;
        });

        return redirect()->route('toko.pesanan')->with('success', 'Pembelian berhasil dari keranjang. Total: Rp ' . number_format($transaction->total, 0, ',', '.'));
    }
}
