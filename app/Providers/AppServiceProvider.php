<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;  // ← TAMBAHKAN INI!

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
        // ← TAMBAHKAN INI!
        // Set default string length untuk compatibility dengan MySQL versi lama
        Schema::defaultStringLength(191);
    }
}