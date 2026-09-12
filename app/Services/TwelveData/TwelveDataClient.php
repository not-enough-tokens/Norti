<?php

namespace App\Services\TwelveData;

use App\Services\TwelveData\Exceptions\TwelveDataException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the TwelveData REST API (https://twelvedata.com/docs).
 */
class TwelveDataClient
{
    /**
     * Interval values accepted by TwelveData's quote and time_series endpoints.
     *
     * @var array<int, string>
     */
    public const INTERVALS = [
        '1min', '5min', '15min', '30min', '45min',
        '1h', '2h', '4h', '8h',
        '1day', '1week', '1month',
    ];

    public function __construct(
        protected readonly string $apiKey,
        protected readonly string $baseUrl,
    ) {}

    /**
     * Fetch the latest quote for a symbol.
     *
     * @param  array<string, mixed>  $parameters  Additional TwelveData query parameters (e.g. exchange, interval).
     * @return array<string, mixed>
     */
    public function quote(string $symbol, array $parameters = []): array
    {
        return $this->get('quote', [...$parameters, 'symbol' => $symbol]);
    }

    /**
     * Fetch historical time series data for a symbol.
     *
     * @param  array<string, mixed>  $parameters  Additional TwelveData query parameters (e.g. outputsize, start_date).
     * @return array<string, mixed>
     */
    public function timeSeries(string $symbol, string $interval, array $parameters = []): array
    {
        return $this->get('time_series', [...$parameters, 'symbol' => $symbol, 'interval' => $interval]);
    }

    /**
     * Fetch company profile information for a symbol.
     *
     * @param  array<string, mixed>  $parameters  Additional TwelveData query parameters (e.g. exchange, mic_code).
     * @return array<string, mixed>
     */
    public function profile(string $symbol, array $parameters = []): array
    {
        return $this->get('profile', [...$parameters, 'symbol' => $symbol]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function get(string $endpoint, array $query): array
    {
        $query = array_filter(
            [...$query, 'apikey' => $this->apiKey],
            fn (mixed $value): bool => $value !== null,
        );

        try {
            $response = Http::baseUrl($this->baseUrl)->acceptJson()->timeout(10)->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new TwelveDataException(
                "Unable to reach TwelveData: {$exception->getMessage()}",
                503,
                $exception,
            );
        }

        $payload = $response->json();

        if ($response->failed() || (is_array($payload) && ($payload['status'] ?? null) === 'error')) {
            $code = $payload['code'] ?? $response->status();

            throw new TwelveDataException(
                $payload['message'] ?? 'Unknown TwelveData error.',
                is_int($code) && $code >= 400 && $code <= 599 ? $code : 502,
            );
        }

        return $payload ?? [];
    }
}
