<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RestockRequest;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Master Supplier.
 *
 * Halaman master data pemasok untuk admin dan staff. Admin punya hak penuh
 * (tambah, ubah, hapus), sedangkan staff hanya boleh menambah dan mengubah
 * data. Data di sini dipakai bersama oleh modul DSS (penilaian VIKOR) dan
 * form pengajuan restock.
 */
class SupplierMasterController extends Controller
{
    /**
     * Daftar pemasok + pencarian dan filter status.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $query = Supplier::query()
            ->withCount('productSuppliers')
            ->orderBy('name');

        if ($search !== '') {
            $like = '%'.mb_strtolower($search).'%';

            $query->where(function ($builder) use ($like) {
                $builder->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(contact_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(city) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(supplied_categories) LIKE ?', [$like]);
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $suppliers = $query->get();

        // Form yang sedang diedit (kalau ada), supaya admin/staff bisa
        // memperbarui data tanpa kehilangan isi filter pencarian.
        $editing = null;
        $editId = $request->query('edit');
        if ($editId) {
            $editing = Supplier::find($editId);
        }

        // Saran kategori bahan/produk dari master kategori produk.
        $categories = Category::orderBy('name')->pluck('name');

        return view('supplier.master', compact(
            'suppliers',
            'search',
            'status',
            'editing',
            'categories'
        ));
    }

    /**
     * Simpan pemasok baru. Staff dan admin sama-sama boleh menambah.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoles();

        $data = $this->validated($request);

        $data['code'] = $data['code'] ?? $this->generateCode();
        $data['is_active'] = $request->boolean('is_active');

        $supplier = Supplier::create($data);

        return redirect()
            ->route('supplier.master')
            ->with('success', 'Pemasok '.$supplier->name.' ('.$supplier->code.') berhasil ditambahkan.');
    }

    /**
     * Perbarui data pemasok. Staff dan admin sama-sama boleh mengubah.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeRoles();

        $data = $this->validated($request, $supplier);

        if (($data['code'] ?? '') === '') {
            $data['code'] = $supplier->code;
        }

        $data['is_active'] = $request->boolean('is_active');

        $supplier->update($data);

        return redirect()
            ->route('supplier.master')
            ->with('success', 'Data pemasok '.$supplier->name.' berhasil diperbarui.');
    }

    /**
     * Hapus pemasok. Hanya admin yang boleh, dan hanya kalau pemasok
     * belum dipakai data lain (skor, penawaran produk, rekomendasi, atau
     * pengajuan restock).
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $usage = $this->usageOf($supplier);

        if ($usage !== []) {
            return redirect()
                ->route('supplier.master')
                ->with('error', 'Pemasok '.$supplier->name.' tidak bisa dihapus karena sudah dipakai: '
                    .implode(', ', $usage).'. Nonaktifkan saja agar tidak lagi dipilih.');
        }

        $supplier->productSuppliers()->delete();
        $nama = $supplier->name;
        $supplier->delete();

        return redirect()
            ->route('supplier.master')
            ->with('success', 'Pemasok '.$nama.' berhasil dihapus.');
    }

    /**
     * Hanya staff dan admin yang boleh mengelola master supplier.
     */
    private function authorizeRoles(): void
    {
        abort_unless(in_array(Auth::user()->role, ['staff', 'admin'], true), 403);
    }

    /**
     * Aturan validasi data pemasok.
     *
     * @return array<string, array<int, string>>
     */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $unique = 'unique:suppliers,code';

        if ($supplier !== null) {
            $unique .= ','.$supplier->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', $unique],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:50'],
            'supplied_categories' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:60'],
            'min_order_qty' => ['required', 'integer', 'min:1'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Kode pemasok otomatis SUP-xxx bila form dikosongkan.
     */
    private function generateCode(): string
    {
        $number = ((int) Supplier::max('id')) + 1;

        do {
            $code = 'SUP-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $number++;
        } while (Supplier::where('code', $code)->exists());

        return $code;
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
