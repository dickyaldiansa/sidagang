<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // The application uses Bootstrap; avoid the default Tailwind paginator
        // whose SVG utility classes are not loaded on these pages.
        Paginator::useBootstrapFive();
    }
}
