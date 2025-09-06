<?php

namespace App\Http\Middleware;

use App\Models\SiteVisitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LogSiteVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Dapatkan IP address pengunjung
        $ipAddress = $request->ip();
        $today = now()->format('Y-m-d');

        // Buat kunci cache yang unik untuk IP ini & HARI INI
        $cacheKey = "visitor_" . $ipAddress . "_" . $today;

        // Cek apakah IP ini belum tercatat di cache HARI INI
        if (!Cache::has($cacheKey)) {
            // Jika belum, catat kunjungannya ke database
            SiteVisitor::create([
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
            ]);

            // Simpan jejaknya ke cache, berlaku sampai tengah malam
            Cache::put($cacheKey, true, now()->endOfDay());
        }

        // Lanjutkan permintaan ke halaman yang dituju
        return $next($request);
    }
}
