<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use Carbon\Carbon;
use InvalidArgumentException;

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
     *
     * Si la fecha objetivo ya pasó no hay nada que proyectar: se regresa
     * `is_overdue => true` con el monto actual. Antes el `max(1, ...)`
     * convertía una meta vencida en una proyección de un mes hacia el futuro,
     * indistinguible de una meta vigente.
     */
    public function projectForGoal(FinancialGoal $goal, FinancialProfile $profile): array
    {
        if ($goal->user_id !== $profile->user_id) {
            throw new InvalidArgumentException(
                'La meta y el perfil financiero pertenecen a usuarios distintos.'
            );
        }

        $monthlyContribution = $this->profileService->monthlySavingsCapacity($profile);
        $annualReturn = $this->riskAnalysisService->expectedAnnualReturn(
            $this->riskAnalysisService->suggestRiskProfile($profile)
        );

        $months = (int) floor(
            Carbon::now()->startOfDay()->diffInMonths(Carbon::parse($goal->target_day)->startOfDay())
        );

        if ($months <= 0) {
            return [
                'projection' => [],
                'months' => 0,
                'is_overdue' => true,
                'monthly_contribution' => $monthlyContribution,
                'annual_return' => $annualReturn,
                'final_amount' => (float) $goal->current_amount,
            ];
        }

        $projection = $this->project(
            initialAmount: (float) $goal->current_amount,
            monthlyContribution: $monthlyContribution,
            annualReturn: $annualReturn,
            months: $months
        );

        return [
            'projection' => $projection,
            'months' => $months,
            'is_overdue' => false,
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
        $finalAmount = (float) ($simulation['final_amount'] ?? $goal->current_amount);
        $reachesGoal = $finalAmount >= (float) $goal->target_amount;

        return [
            'reaches_goal' => $reachesGoal,
            'shortfall' => $reachesGoal ? 0.0 : round((float) $goal->target_amount - $finalAmount, 2),
            // Una meta vencida que no se alcanzó ya no es "te falta ahorrar":
            // el consumidor necesita distinguirlo para no sugerir un plan.
            'is_overdue' => (bool) ($simulation['is_overdue'] ?? false),
        ];
    }
}
