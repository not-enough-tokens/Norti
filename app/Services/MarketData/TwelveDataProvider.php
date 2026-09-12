<?php

namespace App\Services\MarketData;

use App\Services\TwelveData\TwelveDataClient;

class TwelveDataProvider implements MarketDataProvider
{
    public function __construct(
        protected TwelveDataClient $client,
    ) {}

    public function getQuote(string $symbol): array
    {
        $data = $this->client->quote($symbol);

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
    ): array {
        // Lo implementaremos después.
        return [];
    }

    public function getAssetProfile(string $symbol): array
    {
        // Lo implementaremos después.
        return [];
    }
}
