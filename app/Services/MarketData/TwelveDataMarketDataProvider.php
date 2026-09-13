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

    public function quote(string $symbol, array $parameters = []): array
    {
        return $this->call(fn (): array => $this->client->quote($symbol, $parameters));
    }

    public function profile(string $symbol, array $parameters = []): array
    {
        return $this->call(fn (): array => $this->client->profile($symbol, $parameters));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeSeries(string $symbol, string $interval, array $parameters = []): array
    {
        $data = $this->call(fn (): array => $this->client->timeSeries($symbol, $interval, $parameters));

        return array_map(
            fn (array $value): array => [
                'datetime' => $value['datetime'] ?? null,
                'open' => isset($value['open']) ? (float) $value['open'] : null,
                'high' => isset($value['high']) ? (float) $value['high'] : null,
                'low' => isset($value['low']) ? (float) $value['low'] : null,
                'close' => isset($value['close']) ? (float) $value['close'] : null,
                'volume' => isset($value['volume']) ? (int) $value['volume'] : null,
            ],
            $data['values'] ?? [],
        );
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
