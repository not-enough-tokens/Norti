<?php

namespace Tests\Feature\MarketData;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketDataControllerTest extends TestCase
{
    public function test_quote_returns_twelvedata_payload(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response([
                'symbol' => 'AAPL',
                'name' => 'Apple Inc',
                'close' => '150.00',
            ]),
        ]);

        $response = $this->getJson('/api/market-data/quote?symbol=AAPL');

        $response->assertOk()->assertJson(['symbol' => 'AAPL']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.twelvedata.com/quote?symbol=AAPL&apikey='
            .config('services.twelvedata.key'));
    }

    public function test_quote_requires_a_symbol(): void
    {
        $response = $this->getJson('/api/market-data/quote');

        $response->assertUnprocessable()->assertJsonValidationErrors('symbol');
    }

    public function test_time_series_returns_normalized_price_bars(): void
    {
        Http::fake([
            'api.twelvedata.com/time_series*' => Http::response([
                'meta' => ['symbol' => 'AAPL', 'interval' => '1day'],
                'values' => [['datetime' => '2024-01-01', 'close' => '150.00']],
                'status' => 'ok',
            ]),
        ]);

        $response = $this->getJson('/api/market-data/time-series?symbol=AAPL&interval=1day');

        $response->assertOk()
            ->assertJsonPath('0.datetime', '2024-01-01')
            ->assertJsonPath('0.close', 150);
    }

    public function test_time_series_requires_a_valid_interval(): void
    {
        $response = $this->getJson('/api/market-data/time-series?symbol=AAPL&interval=1year');

        $response->assertUnprocessable()->assertJsonValidationErrors('interval');
    }

    public function test_profile_returns_twelvedata_payload(): void
    {
        Http::fake([
            'api.twelvedata.com/profile*' => Http::response([
                'symbol' => 'AAPL',
                'name' => 'Apple Inc',
                'sector' => 'Technology',
            ]),
        ]);

        $response = $this->getJson('/api/market-data/profile?symbol=AAPL');

        $response->assertOk()->assertJson(['sector' => 'Technology']);
    }

    public function test_twelvedata_error_payload_is_translated_to_an_error_response(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response([
                'code' => 400,
                'message' => 'symbol parameter is missing or incorrect',
                'status' => 'error',
            ]),
        ]);

        $response = $this->getJson('/api/market-data/quote?symbol=INVALID');

        $response->assertStatus(400)->assertJson(['message' => 'symbol parameter is missing or incorrect']);
    }
}
