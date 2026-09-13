<?php

namespace App\Services\Financial;

use App\Mcp\Support\ChartData;
use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\Contracts\FinancialProfileServiceContract;
use App\Services\ProfileService;
use App\Services\RiskAnalysisService;

class EloquentFinancialProfileService implements FinancialProfileServiceContract
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly RiskAnalysisService $riskAnalysis,
    ) {}

    public function getProfile(User $user, string $detail = 'summary'): array
    {
        $profile = $user->financialProfile;

        if (! $profile) {
            return ['has_profile' => false];
        }

        // A2UI contract gap 4: financial_profile_card recibía risk_tolerance
        // crudo (string libre, sin enum ni check en la BD); normalizarlo aquí
        // -- una sola vez -- evita que cada consumidor (esta tool, las
        // actions que arma) tenga que repetir la normalización por su cuenta.
        $riskTolerance = $this->riskAnalysis->suggestRiskProfile($profile);

        if ($detail === 'exact') {
            $monthlyIncome = (float) $profile->monthly_income;
            $monthlyExpenses = (float) $profile->monthly_expenses;
            $savingsCapacity = $this->profileService->monthlySavingsCapacity($profile);

            return [
                'has_profile' => true,
                'detail' => 'exact',
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'savings' => (float) $profile->savings,
                // Gap 18: antes ausente en detail=exact aunque ProfileService
                // ya la calculaba -- el resumen se queda en categorías, este
                // monto exacto solo se expone cuando el usuario pidió exact.
                'monthly_savings_capacity' => $savingsCapacity,
                'risk_tolerance' => $riskTolerance,
                'investment_horizon_months' => $profile->investment_horizon_months,
                // Cascada ingresos -> gastos -> capacidad de ahorro (A2UI
                // contract gap 13, aplicada a financial_profile_card). Solo
                // aparece en detail=exact, igual que los montos que grafica.
                'chart' => $this->savingsWaterfallChart($monthlyIncome, $monthlyExpenses, $savingsCapacity),
            ];
        }

        return [
            'has_profile' => true,
            'detail' => 'summary',
            'risk_tolerance' => $riskTolerance,
            'investment_horizon_months' => $profile->investment_horizon_months,
            'savings_rate_category' => $this->savingsRateCategory($profile),
        ];
    }

    private function savingsWaterfallChart(float $monthlyIncome, float $monthlyExpenses, float $savingsCapacity): array
    {
        $yMax = max(1.0, $monthlyIncome);

        return ChartData::make(
            type: 'waterfall',
            data: [
                ['key' => 'monthly_income', 'kind' => 'total', 'value' => $monthlyIncome, 'start' => 0, 'end' => $monthlyIncome],
                ['key' => 'monthly_expenses', 'kind' => 'decrease', 'value' => -$monthlyExpenses, 'start' => $monthlyIncome, 'end' => $monthlyIncome - $monthlyExpenses],
                ['key' => 'monthly_savings_capacity', 'kind' => 'total', 'value' => $savingsCapacity, 'start' => 0, 'end' => $savingsCapacity],
            ],
            unit: 'currency',
            currency: 'MXN',
            yAxis: ['domain' => [0, $yMax], 'ticks' => [0, round($yMax / 2, 2), $yMax]],
        );
    }

    /**
     * Bucketed savings rate so the LLM sees a category, never the exact income/expense figures.
     * Uses ProfileService::monthlySavingsCapacity() (Integrante A/M2) for the underlying figure.
     */
    private function savingsRateCategory(FinancialProfile $profile): string
    {
        $income = (float) $profile->monthly_income;

        if ($income <= 0.0) {
            return 'unknown';
        }

        $savingsRate = $this->profileService->monthlySavingsCapacity($profile) / $income;

        return match (true) {
            $savingsRate >= 0.2 => 'high',
            $savingsRate >= 0.05 => 'moderate',
            default => 'low',
        };
    }
}
