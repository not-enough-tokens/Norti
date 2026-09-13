<?php

namespace App\Services\Contracts;

interface MarketDataProviderContract
{
    /**
     * @return array<string, mixed>
     */
    public function quote(string $symbol): array;

    /**
     * @return array<string, mixed>
     */
    public function profile(string $symbol): array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function timeSeries(string $symbol, string $interval, array $parameters = []): array;
}
