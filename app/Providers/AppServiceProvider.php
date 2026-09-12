<?php

namespace App\Providers;

use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwelveDataClient::class, fn (): TwelveDataClient => new TwelveDataClient(
            apiKey: (string) config('services.twelvedata.key'),
            baseUrl: (string) config('services.twelvedata.base_url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'mcp:read' => 'Leer datos financieros del usuario',
            'mcp:simulate' => 'Ejecutar simulaciones de inversión',
        ]);
    }
}
