<?php

namespace App\Services\Financial;

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
            return [
                'has_profile' => true,
                'detail' => 'exact',
                'monthly_income' => (float) $profile->monthly_income,
                'monthly_expenses' => (float) $profile->monthly_expenses,
                'savings' => (float) $profile->savings,
                // Gap 18: antes ausente en detail=exact aunque ProfileService
                // ya la calculaba -- el resumen se queda en categorías, este
                // monto exacto solo se expone cuando el usuario pidió exact.
                'monthly_savings_capacity' => $this->profileService->monthlySavingsCapacity($profile),
                'risk_tolerance' => $riskTolerance,
                'investment_horizon_months' => $profile->investment_horizon_months,
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
