<?php

namespace Tests\Feature\MarketData;

use App\Models\User;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\MarketData\MockMarketDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MarketDataControllerMockProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(MarketDataProviderContract::class, MockMarketDataProvider::class);

        Http::preventStrayRequests();

        Passport::actingAs(User::factory()->create());
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

    /**
     * El techo real de TwelveData es por cuenta, no por usuario, así que este
     * throttle no lo garantiza solo -- pero evita que un cliente lo agote.
     */
    public function test_market_data_is_rate_limited_per_caller(): void
    {
        config(['services.twelvedata.rate_limit_per_minute' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/market-data/quote?symbol=AAPL')->assertOk();
        }

        $this->getJson('/api/market-data/quote?symbol=AAPL')->assertStatus(429);
    }

    public function test_profile_endpoint_works_without_calling_twelvedata(): void
    {
        $response = $this->getJson('/api/market-data/profile?symbol=AAPL');

        $response->assertOk()->assertJson(['symbol' => 'AAPL', 'sector' => 'Technology']);
    }
}
