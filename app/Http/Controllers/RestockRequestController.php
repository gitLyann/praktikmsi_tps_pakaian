<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use Illuminate\Http\Request;

class RestockRequestController extends Controller
{
    // API 1: Mengambil semua daftar pengajuan OAS
    public function index()
    {
        $requests = RestockRequest::with(['product', 'employee'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Pengajuan Restock (OAS)',
            'data' => $requests
        ], 200);
    }

    // API 2: Membuat pengajuan restock baru (Input oleh Karyawan)
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'employee_id' => 'required|exists:employees,id',
            'jumlah_restock' => 'required|integer|min:1',
            'catatan' => 'nullable|string'
        ]);

        $restock = RestockRequest::create([
            'product_id' => $request->product_id,
            'employee_id' => $request->employee_id,
            'jumlah_restock' => $request->jumlah_restock,
            'status' => 'pending',
            'catatan' => $request->catatan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan restock barang berhasil dibuat',
            'data' => $restock
        ], 201);
    }

    // API 3: Otomasi Approval (Persetujuan Manager/OAS)
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $restock = RestockRequest::findOrFail($id);
        $restock->status = $request->status;
        $restock->save();

        // Jika disetujui (Approved), otomatis tambahkan stok ke tabel products (Otomasi OAS)
        if ($request->status === 'approved') {
            $product = $restock->product;
            $product->stock += $restock->jumlah_restock;
            $product->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diperbarui secara otomatis',
            'data' => $restock
        ], 200);
    }
}
