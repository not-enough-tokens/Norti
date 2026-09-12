<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use Carbon\Carbon;

class InvestmentSimulationService
{
    public function __construct(
        private RiskAnalysisService $riskAnalysisService,
        private ProfileService $profileService
    ) {}

    /**
     * Genera una proyección mes a mes de cómo crecería el dinero,
     * usando interés compuesto mensual.
     *
     * @return array<int, array{month: int, amount: float}>
     */
    public function project(float $initialAmount, float $monthlyContribution, float $annualReturn, int $months): array
    {
        $monthlyRate = $annualReturn / 12;
        $amount = $initialAmount;
        $projection = [];

        for ($month = 1; $month <= $months; $month++) {
            $amount = ($amount * (1 + $monthlyRate)) + $monthlyContribution;
            $projection[] = [
                'month' => $month,
                'amount' => round($amount, 2),
            ];
        }

        return $projection;
    }

    /**
     * Arma la proyección completa para una meta específica, usando los
     * datos del perfil financiero del usuario (capacidad de ahorro,
     * perfil de riesgo, monto inicial ya ahorrado).
     */
    public function projectForGoal(FinancialGoal $goal, FinancialProfile $profile): array
    {
        $monthlyContribution = $this->profileService->monthlySavingsCapacity($profile);
        $annualReturn = $this->riskAnalysisService->expectedAnnualReturn(
            $this->riskAnalysisService->suggestRiskProfile($profile)
        );
        $months = max(1, (int) round(Carbon::now()->diffInMonths(Carbon::parse($goal->target_day))));

        $projection = $this->project(
            initialAmount: (float) $goal->current_amount,
            monthlyContribution: $monthlyContribution,
            annualReturn: $annualReturn,
            months: $months
        );

        return [
            'projection' => $projection,
            'months' => $months,
            'monthly_contribution' => $monthlyContribution,
            'annual_return' => $annualReturn,
            'final_amount' => end($projection)['amount'] ?? (float) $goal->current_amount,
        ];
    }

    /**
     * Compara el resultado final de una proyección contra la meta,
     * y regresa si se alcanza o cuánto falta.
     */
    public function evaluateGoal(FinancialGoal $goal, array $simulation): array
    {
        $finalAmount = $simulation['final_amount'];
        $reachesGoal = $finalAmount >= (float) $goal->target_amount;

        return [
            'reaches_goal' => $reachesGoal,
            'shortfall' => $reachesGoal ? 0 : round((float) $goal->target_amount - $finalAmount, 2),
        ];
    }
}
