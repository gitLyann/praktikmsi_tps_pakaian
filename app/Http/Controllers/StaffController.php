<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\RestockRequest;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    // Menampilkan halaman dashboard staff beserta data produk dan riwayat restock
    public function index()
    {
        $products = Product::all();
        $restockRequests = RestockRequest::with(['product', 'employee'])->latest()->get();

        return view('staff.dashboard', compact('products', 'restockRequests'));
    }

    // Memproses pengajuan restock dari formulir web (OAS Function - Staff)
    public function storeRestock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'jumlah_restock' => 'required|integer|min:1',
        ]);

        // Mencari employee_id dari user yang sedang login
        // Jika belum ada relasi employee, kita set default ID 1 untuk simulasi
        $employeeId = Auth::user()->employee->id ?? 1;

        RestockRequest::create([
            'product_id' => $request->product_id,
            'employee_id' => $employeeId,
            'jumlah_restock' => $request->input('jumlah_restock'),
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan restock barang berhasil dikirim ke Manager!');
    }
}