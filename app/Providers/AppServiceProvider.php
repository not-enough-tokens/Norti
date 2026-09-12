<?php

namespace App\Providers;

use App\Services\MarketData\MarketDataProvider;
use App\Services\MarketData\TwelveDataProvider;
use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
