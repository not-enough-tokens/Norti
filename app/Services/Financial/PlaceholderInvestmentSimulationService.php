<?php

namespace App\Services\Financial;

use App\Services\Contracts\InvestmentSimulationServiceContract;
use InvalidArgumentException;

/**
 * PLACEHOLDER: la metodología de simulación real es decisión de
 * Integrante A/M2. Esta implementación es una proyección aritmética de
 * interés compuesto mensual a tasa fija por risk_profile, sin variables de
 * mercado reales. Reemplazar el binding en DomainServiceProvider cuando
 * esté listo, sin tocar la tool.
 */
class PlaceholderInvestmentSimulationService implements InvestmentSimulationServiceContract
{
    private const ANNUAL_RATES = [
        'conservative' => 0.04,
        'moderate' => 0.07,
        'aggressive' => 0.11,
    ];

    public function simulate(float $amount, int $months, string $riskProfile): array
    {
        $annualRate = self::ANNUAL_RATES[$riskProfile]
            ?? throw new InvalidArgumentException("Unknown risk profile: {$riskProfile}");

        $monthlyRate = $annualRate / 12;
        $projectedValue = $amount * (1 + $monthlyRate) ** $months;

        return [
            'initial_amount' => $amount,
            'months' => $months,
            'risk_profile' => $riskProfile,
            'assumed_annual_rate' => $annualRate,
            'projected_value' => round($projectedValue, 2),
            'projected_gain' => round($projectedValue - $amount, 2),
            'disclaimer' => 'Proyección aritmética simplificada (interés compuesto mensual a tasa fija). No considera volatilidad de mercado ni constituye asesoría financiera.',
        ];
    }
}
