<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa apakah pengguna sudah login DAN adalah seorang admin.
        if (!Auth::check() || !Auth::user()->is_admin) {
            // 2. Jika tidak, tolak akses.
            abort(403, 'ANDA TIDAK MEMILIKI AKSES KE HALAMAN INI.');
        }

        // 3. Jika ya, izinkan untuk melanjutkan.
        return $next($request);
    }
}
