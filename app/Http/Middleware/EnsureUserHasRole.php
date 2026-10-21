<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan role user.
 *
 * Contoh pemakaian pada route:
 *   ->middleware('role:admin')              // khusus admin (manager)
 *   ->middleware('role:staff,admin')        // staff (kasir) dan admin
 *
 * Role yang dipakai mengikuti nilai pada tabel `users.role`:
 * `pelanggan`, `staff` (kasir), dan `admin` (manager).
 */
class EnsureUserHasRole
{
    /**
     * Menjalankan middleware.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  Role yang boleh mengakses route.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login -> arahkan ke halaman login.
        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        // Tanpa daftar role, route hanya boleh untuk user yang sudah login.
        if ($roles === [] || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        return $next($request);
    }
}
