<?php

namespace Tests\Unit\MarketData;

use App\Services\MarketData\TwelveDataProvider;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use App\Services\TwelveData\TwelveDataClient;
use Mockery;
use PHPUnit\Framework\TestCase;

class TwelveDataProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_get_quote_normalizes_the_client_response(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')
            ->once()
            ->with('AAPL', [])
            ->andReturn([
                'symbol' => 'AAPL',
                'name' => 'Apple Inc',
                'exchange' => 'NASDAQ',
                'currency' => 'USD',
                'open' => '148.50',
                'high' => '151.00',
                'low' => '148.00',
                'close' => '150.00',
                'volume' => '1000000',
                'timestamp' => '1704412800',
            ]);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc',
            'exchange' => 'NASDAQ',
            'currency' => 'USD',
            'price' => 150.0,
            'open' => 148.5,
            'high' => 151.0,
            'low' => 148.0,
            'volume' => 1_000_000,
            'timestamp' => '1704412800',
        ], $provider->getQuote('AAPL'));
    }

    public function test_get_quote_forwards_parameters_to_the_client(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')
            ->once()
            ->with('AAPL', ['exchange' => 'NASDAQ'])
            ->andReturn(['symbol' => 'AAPL']);

        $provider = new TwelveDataProvider($client);

        $this->assertSame('AAPL', $provider->getQuote('AAPL', ['exchange' => 'NASDAQ'])['symbol']);
    }

    public function test_get_quote_returns_null_for_missing_optional_fields(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')->once()->andReturn(['symbol' => 'AAPL']);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([
            'symbol' => 'AAPL',
            'name' => null,
            'exchange' => null,
            'currency' => null,
            'price' => null,
            'open' => null,
            'high' => null,
            'low' => null,
            'volume' => null,
            'timestamp' => null,
        ], $provider->getQuote('AAPL'));
    }

    public function test_get_historical_prices_normalizes_each_value(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('timeSeries')
            ->once()
            ->with('AAPL', '1day', [])
            ->andReturn([
                'meta' => ['symbol' => 'AAPL'],
                'values' => [
                    ['datetime' => '2024-01-02', 'open' => '151.00', 'high' => '152.00', 'low' => '150.50', 'close' => '151.50', 'volume' => '900000'],
                    ['datetime' => '2024-01-01', 'open' => '148.50', 'high' => '151.00', 'low' => '148.00', 'close' => '150.00', 'volume' => '1000000'],
                ],
            ]);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([
            ['datetime' => '2024-01-02', 'open' => 151.0, 'high' => 152.0, 'low' => 150.5, 'close' => 151.5, 'volume' => 900_000],
            ['datetime' => '2024-01-01', 'open' => 148.5, 'high' => 151.0, 'low' => 148.0, 'close' => 150.0, 'volume' => 1_000_000],
        ], $provider->getHistoricalPrices('AAPL'));
    }

    public function test_get_historical_prices_forwards_interval_and_parameters(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('timeSeries')
            ->once()
            ->with('AAPL', '1week', ['outputsize' => 10])
            ->andReturn(['values' => []]);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([], $provider->getHistoricalPrices('AAPL', '1week', ['outputsize' => 10]));
    }

    public function test_get_historical_prices_returns_an_empty_array_when_the_response_has_no_values(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('timeSeries')->once()->andReturn(['status' => 'ok']);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([], $provider->getHistoricalPrices('AAPL'));
    }

    public function test_get_asset_profile_normalizes_the_client_response(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('profile')
            ->once()
            ->with('AAPL', [])
            ->andReturn([
                'symbol' => 'AAPL',
                'name' => 'Apple Inc',
                'exchange' => 'NASDAQ',
                'sector' => 'Technology',
                'industry' => 'Consumer Electronics',
                'employees' => '164000',
                'website' => 'https://www.apple.com',
                'description' => 'Designs and sells consumer electronics.',
            ]);

        $provider = new TwelveDataProvider($client);

        $this->assertSame([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc',
            'exchange' => 'NASDAQ',
            'sector' => 'Technology',
            'industry' => 'Consumer Electronics',
            'employees' => 164_000,
            'website' => 'https://www.apple.com',
            'description' => 'Designs and sells consumer electronics.',
        ], $provider->getAssetProfile('AAPL'));
    }

    public function test_get_asset_profile_defaults_symbol_to_the_requested_symbol_when_missing(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('profile')->once()->andReturn(['name' => 'Apple Inc']);

        $provider = new TwelveDataProvider($client);

        $this->assertSame('AAPL', $provider->getAssetProfile('AAPL')['symbol']);
    }

    public function test_get_quote_propagates_client_exceptions(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')
            ->once()
            ->andThrow(new TwelveDataException('symbol parameter is missing or incorrect', 400));

        $provider = new TwelveDataProvider($client);

        $this->expectException(TwelveDataException::class);
        $this->expectExceptionMessage('symbol parameter is missing or incorrect');

        $provider->getQuote('INVALID');
    }
}
