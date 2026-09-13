<?php

namespace App\Services\Contracts;

interface MarketDataProviderContract
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function quote(string $symbol, array $parameters = []): array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function profile(string $symbol, array $parameters = []): array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function timeSeries(string $symbol, string $interval, array $parameters = []): array;
}
