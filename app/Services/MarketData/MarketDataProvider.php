<?php

namespace App\Services\MarketData;

interface MarketDataProvider
{
    /**
     * Get the latest market quote for an asset.
     *
     * @return array<string, mixed>
     */
    public function getQuote(string $symbol): array;

    /**
     * Get historical market prices for an asset.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistoricalPrices(
        string $symbol,
        string $interval = '1day',
    ): array;

    /**
     * Get basic information about an asset.
     *
     * @return array<string, mixed>
     */
    public function getAssetProfile(string $symbol): array;
}
