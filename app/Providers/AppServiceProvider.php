<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan arahan @admin di sini
        Blade::if('admin', function () {
            // Periksa apakah pengguna sudah login DAN memiliki status is_admin
            return Auth::check() && Auth::user()->is_admin;
        });
    }
}
