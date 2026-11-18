<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Product;
use App\Models\RestockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestockRequestController extends Controller
{
    // API 1: Mengambil semua daftar pengajuan OAS
    public function index()
    {
        $requests = RestockRequest::with(['product', 'employee'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Pengajuan Restock (OAS)',
            'data' => $requests,
        ], 200);
    }

    // API 2: Membuat pengajuan restock baru (Input oleh Karyawan)
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'jumlah_restock' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $employeeId = Employee::where('user_id', Auth::id())->value('id');

        if ($employeeId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun yang login tidak memiliki data karyawan.',
            ], 403);
        }

        $restock = RestockRequest::create([
            'product_id' => $data['product_id'],
            'employee_id' => $employeeId,
            'jumlah_restock' => $data['jumlah_restock'],
            'status' => 'pending',
            'catatan' => $data['catatan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan restock barang berhasil dibuat',
            'data' => $restock,
        ], 201);
    }

    // API 3: Otomasi Approval (Persetujuan Manager/OAS)
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $restock = DB::transaction(function () use ($id, $data) {
            $restock = RestockRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($restock->status !== 'pending') {
                abort(409, 'Pengajuan ini sudah diproses.');
            }

            $restock->status = $data['status'];
            $restock->save();

            if ($data['status'] === 'approved') {
                Product::whereKey($restock->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->increment('stock', $restock->jumlah_restock);
            }

            return $restock;
        });

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diperbarui secara otomatis',
            'data' => $restock,
        ], 200);
    }
}
