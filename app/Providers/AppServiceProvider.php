<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Laravel detects the scheme as HTTP in production, probably because
        // it's behind a reverse proxy.
        if (config('app.env') === 'production' || config('app.env') === 'staging') {
            URL::forceScheme('https');
        }
    }
}
