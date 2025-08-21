<?php

namespace App\Providers;

use App\Http\Middleware\IsAdmin;
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
        Folio::path(resource_path('views/pages'))->middleware([
            'admin*' => [
                'auth',
                'verified',
                IsAdmin::class,
            ],
        ]);
    }
}
