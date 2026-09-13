<?php

namespace App\Services\Contracts;

interface InvestmentSimulationServiceContract
{
    /**
     * @param  string  $riskProfile  "conservative" | "moderate" | "aggressive"
     * @return array<string, mixed>
     */
    public function simulate(float $amount, int $months, string $riskProfile): array;
}
