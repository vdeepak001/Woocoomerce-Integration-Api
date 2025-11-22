<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (env('WOOCOMMERCE_MOCK_MODE', false)) {
            $this->app->bind(\App\Services\WooCommerceService::class, \App\Services\MockWooCommerceService::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
