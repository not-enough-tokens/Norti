<?php

namespace Tests\Unit\MarketData;

use App\Services\MarketData\MockMarketDataProvider;
use PHPUnit\Framework\TestCase;

class MockMarketDataProviderTest extends TestCase
{
    public function test_get_quote_returns_deterministic_data_for_the_requested_symbol(): void
    {
        $provider = new MockMarketDataProvider;

        $quote = $provider->getQuote('AAPL');

        $this->assertSame('AAPL', $quote['symbol']);
        $this->assertSame($quote, $provider->getQuote('AAPL'));
    }

    public function test_get_historical_prices_returns_a_list_of_price_bars(): void
    {
        $provider = new MockMarketDataProvider;

        $bars = $provider->getHistoricalPrices('AAPL');

        $this->assertNotEmpty($bars);
        $this->assertSame(
            ['datetime', 'open', 'high', 'low', 'close', 'volume'],
            array_keys($bars[0]),
        );
    }

    public function test_get_asset_profile_returns_deterministic_data_for_the_requested_symbol(): void
    {
        $provider = new MockMarketDataProvider;

        $profile = $provider->getAssetProfile('AAPL');

        $this->assertSame('AAPL', $profile['symbol']);
        $this->assertSame($profile, $provider->getAssetProfile('AAPL'));
    }
}
