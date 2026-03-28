<?php

namespace App\Providers;

use Carbon\Carbon;
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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        URL::forceRootUrl(config('app.url'));

        // Locale pt_BR para Carbon (nomes de meses em português)
        Carbon::setLocale('pt_BR');
    }
}
