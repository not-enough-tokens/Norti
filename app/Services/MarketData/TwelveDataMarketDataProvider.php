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

    public function quote(string $symbol): array
    {
        return $this->cached('quote', $symbol, fn (): array => $this->call(fn (): array => $this->client->quote($symbol)));
    }

    public function profile(string $symbol): array
    {
        return $this->cached('profile', $symbol, fn (): array => $this->call(fn (): array => $this->client->profile($symbol)));
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

    /**
     * Bypassed during tests: tests use Http::fake() with a different response
     * per test, and a real cache store would leak a stale fake response from
     * one test into the next.
     *
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function cached(string $method, string $symbol, callable $callback): array
    {
        if (app()->runningUnitTests()) {
            return $callback();
        }

        return Cache::remember("twelvedata:{$method}:{$symbol}", self::CACHE_TTL_SECONDS, $callback);
    }
}
