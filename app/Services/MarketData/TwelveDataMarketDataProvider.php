<?php

namespace App\Services\MarketData;

use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use App\Services\TwelveData\TwelveDataClient;

class TwelveDataMarketDataProvider implements MarketDataProviderContract
{
    public function __construct(
        private readonly TwelveDataClient $client,
    ) {}

    public function quote(string $symbol): array
    {
        return $this->call(fn (): array => $this->client->quote($symbol));
    }

    public function profile(string $symbol): array
    {
        return $this->call(fn (): array => $this->client->profile($symbol));
    }

    public function timeSeries(string $symbol, string $interval, array $parameters = []): array
    {
        return $this->call(fn (): array => $this->client->timeSeries($symbol, $interval, $parameters));
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function call(callable $callback): array
    {
        try {
            return $callback();
        } catch (TwelveDataException $exception) {
            throw new MarketDataUnavailableException($exception->getMessage(), previous: $exception);
        }
    }
}
