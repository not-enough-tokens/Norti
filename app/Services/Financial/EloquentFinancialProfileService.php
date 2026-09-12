<?php

namespace App\Services\Financial;

use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\Contracts\FinancialProfileServiceContract;
use App\Services\ProfileService;

class EloquentFinancialProfileService implements FinancialProfileServiceContract
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function getProfile(User $user, string $detail = 'summary'): array
    {
        $profile = $user->financialProfile;

        if (! $profile) {
            return ['has_profile' => false];
        }

        if ($detail === 'exact') {
            return [
                'has_profile' => true,
                'detail' => 'exact',
                'monthly_income' => (float) $profile->monthly_income,
                'monthly_expenses' => (float) $profile->monthly_expenses,
                'savings' => (float) $profile->savings,
                'risk_tolerance' => $profile->risk_tolerance,
                'investment_horizon_months' => $profile->investment_horizon_months,
            ];
        }

        return [
            'has_profile' => true,
            'detail' => 'summary',
            'risk_tolerance' => $profile->risk_tolerance,
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
