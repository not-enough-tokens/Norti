<?php

namespace App\Providers;

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

        Passport::tokensExpireIn(now()->addDay());
        Passport::personalAccessTokensExpireIn(now()->addDay());

        RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute(
            (int) config('mcp.rate_limit_per_minute')
        )->by($request->user()?->id ?: $request->ip()));

        // /api/market-data/* es un proxy directo a una API de terceros de cuota
        // limitada, así que necesita un límite mucho más estricto que el de MCP.
        RateLimiter::for('market-data', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.twelvedata.rate_limit_per_minute')
        )->by($request->user()?->id ?: $request->ip()));
    }
}
