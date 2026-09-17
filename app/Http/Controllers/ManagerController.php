<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestockRequest;
use App\Models\Product;

class ManagerController extends Controller
{
    // Menampilkan daftar pengajuan restock untuk disetujui
    public function index()
    {
        $restockRequests = RestockRequest::with(['product', 'employee'])->latest()->get();
        return view('admin.dashboard', compact('restockRequests'));
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
}
