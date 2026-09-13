<?php

namespace App\Services\MarketData;

use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\TwelveData\Exceptions\TwelveDataException;
use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Support\Facades\Cache;

class TwelveDataMarketDataProvider implements MarketDataProviderContract
{
    /**
     * TwelveData's free tier caps at 8 requests/minute -- this cache keeps
     * repeated agent calls for the same symbol from burning that quota.
     */
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly TwelveDataClient $client,
    ) {}

    public function quote(string $symbol, array $parameters = []): array
    {
        $quote = $this->cached('quote', $symbol, $parameters, fn (): array => $this->call(fn (): array => $this->client->quote($symbol, $parameters)));

        // Twelve Data sends `percent_change` as a numeric string; the column
        // chart in `market_snapshot_grid` needs a real float to plot (A2UI
        // contract gap 16).
        if (isset($quote['percent_change'])) {
            $quote['percent_change'] = (float) $quote['percent_change'];
        }

        return $quote;
    }

    public function profile(string $symbol, array $parameters = []): array
    {
        return $this->cached('profile', $symbol, $parameters, fn (): array => $this->call(fn (): array => $this->client->profile($symbol, $parameters)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeSeries(string $symbol, string $interval, array $parameters = []): array
    {
        // Was uncached, unlike quote()/profile() -- the line chart in
        // `get_asset_information` (A2UI contract gap 15) calls this on every
        // request, which would burn the 8 req/min free-tier quota fast.
        return $this->cached('timeSeries', $symbol, [...$parameters, 'interval' => $interval], function () use ($symbol, $interval, $parameters): array {
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
        });
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

    /**
     * Bypassed during tests: tests use Http::fake() with a different response
     * per test, and a real cache store would leak a stale fake response from
     * one test into the next.
     *
     * @param  array<string, mixed>  $parameters
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function cached(string $method, string $symbol, array $parameters, callable $callback): array
    {
        if (app()->runningUnitTests()) {
            return $callback();
        }

        $cacheKey = $parameters === []
            ? "twelvedata:{$method}:{$symbol}"
            : "twelvedata:{$method}:{$symbol}:".md5(serialize($parameters));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, $callback);
    }
}
