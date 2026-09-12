<?php

namespace App\Services\MarketData;

use App\Services\TwelveData\TwelveDataClient;

class TwelveDataProvider implements MarketDataProvider
{
    public function __construct(
        protected TwelveDataClient $client,
    ) {}

    public function getQuote(string $symbol, array $parameters = []): array
    {
        $data = $this->client->quote($symbol, $parameters);

        return [
            'symbol' => $data['symbol'],
            'name' => $data['name'] ?? null,
            'exchange' => $data['exchange'] ?? null,
            'currency' => $data['currency'] ?? null,
            'price' => isset($data['close'])
                ? (float) $data['close']
                : null,
            'open' => isset($data['open'])
                ? (float) $data['open']
                : null,
            'high' => isset($data['high'])
                ? (float) $data['high']
                : null,
            'low' => isset($data['low'])
                ? (float) $data['low']
                : null,
            'volume' => isset($data['volume'])
                ? (int) $data['volume']
                : null,
            'timestamp' => $data['timestamp'] ?? null,
        ];
    }

    public function getHistoricalPrices(
        string $symbol,
        string $interval = '1day',
        array $parameters = [],
    ): array {
        $data = $this->client->timeSeries($symbol, $interval, $parameters);

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

    public function getAssetProfile(string $symbol, array $parameters = []): array
    {
        $data = $this->client->profile($symbol, $parameters);

        return [
            'symbol' => $data['symbol'] ?? $symbol,
            'name' => $data['name'] ?? null,
            'exchange' => $data['exchange'] ?? null,
            'sector' => $data['sector'] ?? null,
            'industry' => $data['industry'] ?? null,
            'employees' => isset($data['employees']) ? (int) $data['employees'] : null,
            'website' => $data['website'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }
}
