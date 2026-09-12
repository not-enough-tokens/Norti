<?php

namespace App\Services\MarketData;

interface MarketDataProvider
{
    /**
     * Get the latest market quote for an asset.
     *
     * @param  array<string, mixed>  $parameters  Additional provider-specific filters (e.g. exchange, country).
     * @return array<string, mixed>
     */
    public function getQuote(string $symbol, array $parameters = []): array;

    /**
     * Get historical market prices for an asset.
     *
     * @param  array<string, mixed>  $parameters  Additional provider-specific filters (e.g. start_date, outputsize).
     * @return array<int, array<string, mixed>>
     */
    public function getHistoricalPrices(
        string $symbol,
        string $interval = '1day',
        array $parameters = [],
    ): array;

    /**
     * Get basic information about an asset.
     *
     * @param  array<string, mixed>  $parameters  Additional provider-specific filters (e.g. exchange, mic_code).
     * @return array<string, mixed>
     */
    public function getAssetProfile(string $symbol, array $parameters = []): array;
}
