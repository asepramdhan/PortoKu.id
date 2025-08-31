<?php

namespace App\Providers;

use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Daftarkan rute API Anda di sini
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Folio::path(resource_path('views/pages'))->middleware([
            'admin*' => [
                'auth',
                'verified',
                IsAdmin::class,
            ],
            'dashboard*' => [
                'auth',
                'verified',
                // $admin,
            ],
            'portofolio*' => [
                'auth',
                'verified',
            ],
            'reports*' => [
                'auth',
                'verified',
            ],
            'settings*' => [
                'auth',
                'verified',
            ],
            'transactions*' => [
                'auth',
                'verified',
            ],
        ]);
    }
}
