<?php

namespace App\Providers;

use App\Services\MarketData\MarketDataProvider;
use App\Services\MarketData\TwelveDataProvider;
use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->bind(MarketDataProvider::class, TwelveDataProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'mcp:read' => 'Leer datos financieros del usuario',
            'mcp:simulate' => 'Ejecutar simulaciones de inversión',
	    'mcp:write' => 'Modificar el progreso educativo del usuario',
        ]);

        RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }
}
