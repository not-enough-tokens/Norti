<?php

namespace Tests\Unit\MarketData;

use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\MarketData\TwelveDataMarketDataProvider;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use App\Services\TwelveData\TwelveDataClient;
use Mockery;
use Tests\TestCase;

class TwelveDataMarketDataProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_quote_forwards_the_symbol_and_parameters_to_the_client(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')
            ->once()
            ->with('AAPL', ['exchange' => 'NASDAQ'])
            ->andReturn(['symbol' => 'AAPL', 'close' => '150.00']);

        $provider = new TwelveDataMarketDataProvider($client);

        $this->assertSame(
            ['symbol' => 'AAPL', 'close' => '150.00'],
            $provider->quote('AAPL', ['exchange' => 'NASDAQ']),
        );
    }

    public function test_profile_forwards_the_symbol_and_parameters_to_the_client(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('profile')
            ->once()
            ->with('AAPL', [])
            ->andReturn(['symbol' => 'AAPL', 'sector' => 'Technology']);

        $provider = new TwelveDataMarketDataProvider($client);

        $this->assertSame(
            ['symbol' => 'AAPL', 'sector' => 'Technology'],
            $provider->profile('AAPL'),
        );
    }

    public function test_time_series_normalizes_each_value_into_a_price_bar(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('timeSeries')
            ->once()
            ->with('AAPL', '1day', [])
            ->andReturn([
                'meta' => ['symbol' => 'AAPL'],
                'values' => [
                    ['datetime' => '2024-01-02', 'open' => '151.00', 'high' => '152.00', 'low' => '150.50', 'close' => '151.50', 'volume' => '900000'],
                ],
            ]);

        $provider = new TwelveDataMarketDataProvider($client);

        $this->assertSame([
            ['datetime' => '2024-01-02', 'open' => 151.0, 'high' => 152.0, 'low' => 150.5, 'close' => 151.5, 'volume' => 900_000],
        ], $provider->timeSeries('AAPL', '1day'));
    }

    public function test_time_series_returns_an_empty_array_when_the_response_has_no_values(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('timeSeries')->once()->andReturn(['status' => 'ok']);

        $provider = new TwelveDataMarketDataProvider($client);

        $this->assertSame([], $provider->timeSeries('AAPL', '1day'));
    }

    public function test_quote_wraps_a_twelvedata_exception_into_a_market_data_unavailable_exception(): void
    {
        $client = Mockery::mock(TwelveDataClient::class);
        $client->shouldReceive('quote')
            ->once()
            ->andThrow(new TwelveDataException('symbol parameter is missing or incorrect', 400));

        $provider = new TwelveDataMarketDataProvider($client);

        try {
            $provider->quote('INVALID');
            $this->fail('Expected a MarketDataUnavailableException to be thrown.');
        } catch (MarketDataUnavailableException $exception) {
            $this->assertSame('symbol parameter is missing or incorrect', $exception->getMessage());
            $this->assertInstanceOf(TwelveDataException::class, $exception->getPrevious());
        }
    }
}
