<?php

namespace App\Services;

use App\Services\MarketData\MarketDataProvider;

class MarketDataService
{
    public function __construct(
        private MarketDataProvider $provider,
    ) {}

    /**
     * Obtiene la cotización más reciente de un activo.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getQuote(string $symbol, array $parameters = []): array
    {
        return $this->provider->getQuote($symbol, $parameters);
    }

    /**
     * Obtiene el historial de precios de un activo.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    public function getHistoricalPrices(string $symbol, string $interval = '1day', array $parameters = []): array
    {
        return $this->provider->getHistoricalPrices($symbol, $interval, $parameters);
    }

    /**
     * Obtiene información básica de un activo.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getAssetProfile(string $symbol, array $parameters = []): array
    {
        return $this->provider->getAssetProfile($symbol, $parameters);
    }
}
