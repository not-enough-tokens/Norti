<?php

namespace App\Services\MarketData;

use App\Services\Contracts\MarketDataProviderContract;

/**
 * Deterministic MarketDataProviderContract implementation for tests.
 *
 * Returns fixed, symbol-independent data so tests can exercise the
 * MarketDataProviderContract without calling the Twelve Data API.
 */
class MockMarketDataProvider implements MarketDataProviderContract
{
    public function quote(string $symbol, array $parameters = []): array
    {
        return [
            'symbol' => $symbol,
            'name' => "{$symbol} Mock Company",
            'exchange' => 'MOCK',
            'currency' => 'USD',
            'open' => '99.00',
            'high' => '101.00',
            'low' => '98.50',
            'close' => '100.00',
            'volume' => '1000000',
            'percent_change' => 1.0,
        ];
    }

    public function profile(string $symbol, array $parameters = []): array
    {
        return [
            'symbol' => $symbol,
            'name' => "{$symbol} Mock Company",
            'exchange' => 'MOCK',
            'sector' => 'Technology',
            'industry' => 'Software',
            'employees' => 10_000,
            'website' => 'https://example.com',
            'description' => 'A mock company profile used for deterministic testing.',
        ];
    }

    public function timeSeries(string $symbol, string $interval, array $parameters = []): array
    {
        return [
            ['datetime' => '2024-01-03', 'open' => 101.0, 'high' => 103.0, 'low' => 100.5, 'close' => 102.5, 'volume' => 900_000],
            ['datetime' => '2024-01-02', 'open' => 99.5, 'high' => 101.5, 'low' => 99.0, 'close' => 101.0, 'volume' => 950_000],
            ['datetime' => '2024-01-01', 'open' => 99.0, 'high' => 100.0, 'low' => 98.5, 'close' => 99.5, 'volume' => 1_000_000],
        ];
    }
}
