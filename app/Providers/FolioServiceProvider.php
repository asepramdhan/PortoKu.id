<?php

namespace App\Providers;

use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Auth;
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
        // buat kondisi url yang ngeredirect, jika usernya adalah admin, redirect ke dashboard admin
        // $admin = [
        //     'admin*' => 'admin.dashboard',
        // ];
        // if (Auth::check() && Auth::user()->is_admin) {
        //     $admin = [
        //         'dashboard*' => 'admin.dashboard',
        //     ];
        // }
        // dd($admin);
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
