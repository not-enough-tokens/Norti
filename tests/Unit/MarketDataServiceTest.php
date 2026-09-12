<?php

namespace Tests\Unit;

use App\Services\MarketData\MarketDataProvider;
use App\Services\MarketDataService;
use Mockery;
use PHPUnit\Framework\TestCase;

class MarketDataServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_get_quote_delegates_to_the_provider(): void
    {
        $provider = Mockery::mock(MarketDataProvider::class);
        $provider->shouldReceive('getQuote')
            ->once()
            ->with('AAPL', ['exchange' => 'NASDAQ'])
            ->andReturn(['symbol' => 'AAPL']);

        $service = new MarketDataService($provider);

        $this->assertSame(['symbol' => 'AAPL'], $service->getQuote('AAPL', ['exchange' => 'NASDAQ']));
    }

    public function test_get_historical_prices_delegates_to_the_provider(): void
    {
        $provider = Mockery::mock(MarketDataProvider::class);
        $provider->shouldReceive('getHistoricalPrices')
            ->once()
            ->with('AAPL', '1week', [])
            ->andReturn([['datetime' => '2024-01-01']]);

        $service = new MarketDataService($provider);

        $this->assertSame(
            [['datetime' => '2024-01-01']],
            $service->getHistoricalPrices('AAPL', '1week'),
        );
    }

    public function test_get_asset_profile_delegates_to_the_provider(): void
    {
        $provider = Mockery::mock(MarketDataProvider::class);
        $provider->shouldReceive('getAssetProfile')
            ->once()
            ->with('AAPL', [])
            ->andReturn(['symbol' => 'AAPL', 'sector' => 'Technology']);

        $service = new MarketDataService($provider);

        $this->assertSame(
            ['symbol' => 'AAPL', 'sector' => 'Technology'],
            $service->getAssetProfile('AAPL'),
        );
    }
}
