<?php

namespace Tests\Feature\MarketData;

use App\Services\Contracts\MarketDataProviderContract;
use App\Services\MarketData\MockMarketDataProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketDataControllerMockProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(MarketDataProviderContract::class, MockMarketDataProvider::class);

        Http::preventStrayRequests();
    }

    public function test_quote_endpoint_works_without_calling_twelvedata(): void
    {
        $response = $this->getJson('/api/market-data/quote?symbol=AAPL');

        $response->assertOk()->assertJson(['symbol' => 'AAPL']);
    }

    public function test_time_series_endpoint_works_without_calling_twelvedata(): void
    {
        $response = $this->getJson('/api/market-data/time-series?symbol=AAPL&interval=1day');

        $response->assertOk()->assertJsonPath('0.datetime', '2024-01-03');
    }

    public function test_profile_endpoint_works_without_calling_twelvedata(): void
    {
        $response = $this->getJson('/api/market-data/profile?symbol=AAPL');

        $response->assertOk()->assertJson(['symbol' => 'AAPL', 'sector' => 'Technology']);
    }
}
