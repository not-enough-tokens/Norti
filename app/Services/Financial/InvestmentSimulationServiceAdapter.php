<?php

namespace App\Services\Financial;

use App\Mcp\Support\ChartData;
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
            'chart' => $this->buildChart($projection, $amount),
        ];
    }

    /**
     * Columnas apiladas capital/rendimiento (A2UI contract gap 14) --
     * project() ya calcula la serie mes a mes, el adapter solo la agrupaba en
     * el último valor (`end($projection)`) y descartaba el resto.
     *
     * @param  array<int, array{month: int, amount: float}>  $projection
     */
    private function buildChart(array $projection, float $initialAmount): array
    {
        $byMonth = array_column($projection, 'amount', 'month');
        $totalMonths = count($projection);

        $data = [];
        foreach ($this->periodMonths($totalMonths) as $month) {
            $total = $byMonth[$month] ?? $initialAmount;
            $data[] = [
                'key' => $month,
                'values' => ['principal' => $initialAmount, 'gain' => round($total - $initialAmount, 2)],
                'total' => round($total, 2),
            ];
        }

        $maxTotal = max(array_column($data, 'total'));
        $yMax = max(1.0, ceil($maxTotal * 1.05));

        return ChartData::make(
            type: 'stacked_column',
            data: $data,
            unit: 'currency',
            currency: 'MXN',
            yAxis: ['domain' => [0, $yMax], 'ticks' => [0, round($yMax / 2, 2), $yMax]],
            series: [
                ['key' => 'principal', 'role' => 'muted'],
                ['key' => 'gain', 'role' => 'positive'],
            ],
        );
    }

    /**
     * Máximo 10 categorías/periodos (regla de gráficas) -- una simulación de
     * hasta 600 meses (50 años) no puede mostrar un mes por barra. Se toman
     * hasta 10 meses distribuidos uniformemente, siempre incluyendo el último.
     *
     * @return list<int>
     */
    private function periodMonths(int $totalMonths, int $maxPeriods = 10): array
    {
        if ($totalMonths <= $maxPeriods) {
            return range(1, $totalMonths);
        }

        $step = $totalMonths / $maxPeriods;
        $months = [];

        for ($i = 1; $i <= $maxPeriods; $i++) {
            $months[] = (int) round($i * $step);
        }

        $months[$maxPeriods - 1] = $totalMonths;

        return array_values(array_unique($months));
    }
}
