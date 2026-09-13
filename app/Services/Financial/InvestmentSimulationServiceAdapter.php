<?php

namespace App\Services\Financial;

use App\Services\Contracts\InvestmentSimulationServiceContract;
use App\Services\InvestmentSimulationService;
use App\Services\RiskAnalysisService;

/**
 * Adapter over Integrante A/M2's real InvestmentSimulationService +
 * RiskAnalysisService. Uses the real calibrated rates from
 * config/investment_rules.php instead of a hardcoded placeholder, and reuses
 * InvestmentSimulationService::project() (with monthlyContribution = 0, since
 * this contract only takes a lump sum) for the month-by-month compounding.
 */
class InvestmentSimulationServiceAdapter implements InvestmentSimulationServiceContract
{
    public function __construct(
        private readonly InvestmentSimulationService $simulation,
        private readonly RiskAnalysisService $riskAnalysis,
    ) {}

    public function simulate(float $amount, int $months, string $riskProfile): array
    {
        $annualRate = $this->riskAnalysis->expectedAnnualReturn($riskProfile);

        $projection = $this->simulation->project(
            initialAmount: $amount,
            monthlyContribution: 0,
            annualReturn: $annualRate,
            months: $months,
        );

        $projectedValue = end($projection)['amount'] ?? $amount;

        return [
            'initial_amount' => $amount,
            'months' => $months,
            'risk_profile' => $riskProfile,
            'assumed_annual_rate' => $annualRate,
            'projected_value' => $projectedValue,
            'projected_gain' => round($projectedValue - $amount, 2),
            'disclaimer' => 'Proyección aritmética simplificada (interés compuesto mensual a tasa fija). No considera volatilidad de mercado ni constituye asesoría financiera.',
        ];
    }
}
