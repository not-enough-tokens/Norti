<?php

namespace Tests\Unit\MarketData;

use App\Services\MarketData\MockMarketDataProvider;
use PHPUnit\Framework\TestCase;

class MockMarketDataProviderTest extends TestCase
{
    public function test_quote_returns_deterministic_data_for_the_requested_symbol(): void
    {
        $provider = new MockMarketDataProvider;

        $quote = $provider->quote('AAPL');

        $this->assertSame('AAPL', $quote['symbol']);
        $this->assertSame($quote, $provider->quote('AAPL'));
    }

    public function test_time_series_returns_a_list_of_price_bars(): void
    {
        $provider = new MockMarketDataProvider;

        $bars = $provider->timeSeries('AAPL', '1day');

        $this->assertNotEmpty($bars);
        $this->assertSame(
            ['datetime', 'open', 'high', 'low', 'close', 'volume'],
            array_keys($bars[0]),
        );
    }

    public function test_profile_returns_deterministic_data_for_the_requested_symbol(): void
    {
        $provider = new MockMarketDataProvider;

        $profile = $provider->profile('AAPL');

        $this->assertSame('AAPL', $profile['symbol']);
        $this->assertSame($profile, $provider->profile('AAPL'));
    }
}
