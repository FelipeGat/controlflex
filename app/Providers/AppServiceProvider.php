<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Garante que todos os redirects e URLs usem a APP_URL correta (incluindo porta)
        URL::forceRootUrl(config('app.url'));
    }
}
