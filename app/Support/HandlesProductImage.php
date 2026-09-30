<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Helper upload foto produk.
 *
 * Foto disimpan di public/images/produk supaya bisa langsung diakses lewat
 * asset() tanpa perlu menjalankan "php artisan storage:link".
 */
trait HandlesProductImage
{
    /**
     * Simpan file baru ke disk dan kembalikan path relatifnya.
     * Mengembalikan null bila tidak ada file yang diunggah.
     */
    protected function storeProductImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $folder = public_path('images/produk');

        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $namaFile = time() . '_' . Str::random(8) . '.' . $extension;
        $file->move($folder, $namaFile);

        return 'images/produk/' . $namaFile;
    }

    /**
     * Tentukan path gambar produk setelah form edit dikirim.
     *
     * - Ada file baru   : simpan file baru, file lama dihapus dari disk.
     * - Tidak ada file baru dan tidak dicentang "hapus foto": foto lama dipertahankan.
     * - Dicentang "hapus foto": foto dikosongkan dan file lama dihapus dari disk.
     */
    protected function syncProductImage(?UploadedFile $file, ?string $currentPath, bool $removeCurrent = false): ?string
    {
        if ($file) {
            $newPath = $this->storeProductImage($file);

            // File lama dibuang hanya setelah file baru benar-benar tersimpan,
            // supaya produk tidak pernah berakhir tanpa gambar karena gagal simpan.
            if ($currentPath && $currentPath !== $newPath) {
                $this->deleteProductImage($currentPath);
            }

            return $newPath;
        }

        if ($removeCurrent) {
            $this->deleteProductImage($currentPath);

            return null;
        }

        return $currentPath;
    }

    /**
     * Hapus file foto dari disk. Sengaja diabaikan bila file tidak ada.
     */
    protected function deleteProductImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $absolute = public_path($path);

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
