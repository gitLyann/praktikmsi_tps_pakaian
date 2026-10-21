<?php

namespace App\Http\Controllers;

use App\Models\ProductSupplier;
use App\Models\RestockRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kelola data pemasok (DSS).
 *
 * Admin menambah, mengubah, menonaktifkan, dan menghapus pemasok beserta
 * penawaran produknya (harga beli, minimum order, lead time). Data pemasok
 * dipakai sebagai alternatif dalam VIKOR dan sebagai pemilik rekomendasi
 * restock, jadi pemasok yang sudah dipakai tidak bisa dihapus sembarangan.
 */
class DssSupplierController extends Controller
{
    /**
     * Simpan pemasok baru.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $supplier = Supplier::create($validated + ['is_active' => true]);

        if ($request->expectsJson()) {
            return response()->json(['supplier' => $supplier], 201);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Pemasok '.$supplier->name.' berhasil ditambahkan.');
    }

    /**
     * Ubah data pemasok.
     */
    public function update(Request $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules($supplier));

        $supplier->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['supplier' => $supplier->fresh()]);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Data pemasok '.$supplier->name.' berhasil diperbarui.');
    }

    /**
     * Hapus pemasok yang belum dipakai data lain.
     */
    public function destroy(Request $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $usedBy = $this->usageOf($supplier);

        if ($usedBy !== []) {
            $message = 'Pemasok tidak bisa dihapus karena sudah dipakai: '.implode(', ', $usedBy).'.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('admin.dss')->withFragment('dss-pemasok')
                ->with('error', $message.' Nonaktifkan saja agar tidak lagi dipilih analisis.');
        }

        $supplier->productSuppliers()->delete();
        $supplier->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Pemasok berhasil dihapus.');
    }

    /**
     * Aktifkan/nonaktifkan pemasok.
     */
    public function toggle(Request $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        if ($request->expectsJson()) {
            return response()->json(['supplier' => $supplier->fresh()]);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Status aktif pemasok '.$supplier->name.' diperbarui.');
    }

    /**
     * Tambah atau perbarui penawaran produk milik pemasok.
     */
    public function storeOffer(Request $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_order_qty' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:60'],
            'is_preferred' => ['nullable', 'boolean'],
        ]);

        $offer = ProductSupplier::updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'product_id' => (int) $validated['product_id'],
            ],
            [
                'price' => (float) $validated['price'],
                'min_order_qty' => (int) $validated['min_order_qty'],
                'lead_time_days' => (int) $validated['lead_time_days'],
                'is_preferred' => (bool) ($validated['is_preferred'] ?? false),
                'notes' => null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['offer' => $offer], 201);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Penawaran produk untuk '.$supplier->name.' tersimpan.');
    }

    /**
     * Hapus penawaran produk milik pemasok.
     */
    public function destroyOffer(Request $request, Supplier $supplier, ProductSupplier $offer): JsonResponse|RedirectResponse
    {
        if ((int) $offer->supplier_id !== (int) $supplier->id) {
            abort(404);
        }

        $offer->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-pemasok')
            ->with('success', 'Penawaran produk dihapus dari '.$supplier->name.'.');
    }

    /**
     * Aturan validasi data pemasok.
     *
     * @return array<string, array<int, string>>
     */
    private function rules(?Supplier $supplier = null): array
    {
        $unique = 'unique:suppliers,code';

        if ($supplier !== null) {
            $unique .= ','.$supplier->id;
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', $unique],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:50'],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:60'],
            'min_order_qty' => ['required', 'integer', 'min:1'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Daftar data lain yang memakai pemasok ini.
     *
     * @return array<int, string>
     */
    private function usageOf(Supplier $supplier): array
    {
        $usage = [];

        if ($supplier->scores()->exists()) {
            $usage[] = 'skor penilaian';
        }

        if ($supplier->productSuppliers()->exists()) {
            $usage[] = 'penawaran produk';
        }

        if ($supplier->restockRecommendations()->exists()) {
            $usage[] = 'rekomendasi restock';
        }

        if (RestockRequest::query()->where('supplier_id', $supplier->id)->exists()) {
            $usage[] = 'pengajuan restock';
        }

        return $usage;
    }
}
