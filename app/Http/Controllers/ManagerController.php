<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\TransactionDetail;
use App\Support\HandlesProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    use HandlesProductImage;

    // Menampilkan daftar pengajuan restock untuk disetujui
    public function index()
    {
        $restockRequests = RestockRequest::with(['product', 'employee'])->latest()->get();

        // Master produk ikut dimuat karena Manager boleh mengubah dan menghapus produk.
        $products = Product::with('category')->orderBy('id')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.dashboard', compact('restockRequests', 'products', 'categories'));
    }

    // Update status pengajuan (Approve / Reject) & update stok otomatis
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $restock = RestockRequest::findOrFail($id);
        $restock->status = $request->status;
        $restock->save();

        // Jika disetujui, otomatis tambahkan stok di tabel products
        if ($request->status === 'approved') {
            $product = Product::findOrFail($restock->product_id);
            $product->stock += $restock->jumlah_restock;
            $product->save();
        }

        return redirect()->back()->with('success', 'Status pengajuan restock berhasil diperbarui!');
    }

    // Catatan: tidak ada storeProduct() di sini. Penambahan master produk
    // hanya dilakukan lewat dashboard Staff (StaffController::storeProduct),
    // supaya Manager fokus pada RUD + Approval OAS.

    // Manager mengubah master produk yang sudah ada (KMS Produk)
    public function updateProduct(Request $request, Product $product)
    {
        abort_unless(in_array(Auth::user()->role, ['staff', 'admin'], true), 403);

        $data = $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'price' => 'required|numeric|min:0',
        ]);

        $category = Category::firstOrCreate(['name' => trim($data['category'])]);

        $product->update([
            'category_id' => $category->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $this->syncProductImage(
                $request->file('image'),
                $product->image,
                $request->boolean('remove_image')
            ),
            'price' => $data['price'],
        ]);

        return redirect()
            ->to(route('admin.dashboard') . '#kelola-produk')
            ->with('success', 'Produk berhasil diperbarui: ' . $product->name . '.');
    }

    // Manager menghapus produk. Produk yang sudah pernah terjual tidak boleh dihapus
    // karena transaction_details memakai foreign key tanpa cascade, dan riwayat
    // penjualan tidak boleh ikut terhapus.
    public function destroyProduct(Product $product)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $sudahTerjual = TransactionDetail::where('product_id', $product->id)->exists();

        if ($sudahTerjual) {
            return redirect()
                ->to(route('admin.dashboard') . '#kelola-produk')
                ->with('error', 'Produk "' . $product->name . '" sudah pernah terjual '
                    . 'sehingga tidak bisa dihapus agar riwayat penjualan tetap utuh.');
        }

        // Baris restock_requests terhapus otomatis lewat cascade di foreign key.
        // Baris favorites juga terhapus otomatis lewat cascade.
        $image = $product->image;
        $namaProduk = $product->name;
        $product->delete();

        $this->deleteProductImage($image);

        return redirect()
            ->to(route('admin.dashboard') . '#kelola-produk')
            ->with('success', 'Produk berhasil dihapus: ' . $namaProduk . '.');
    }
}
