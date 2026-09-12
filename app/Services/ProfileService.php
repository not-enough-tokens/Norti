<?php

namespace App\Services;

use App\Models\FinancialProfile;
use App\Models\User;

class ProfileService
{
    /**
     * Crea el perfil financiero de un usuario si no existe, o lo actualiza
     * si ya existe (un usuario solo tiene un FinancialProfile).
     */
    public function createOrUpdate(User $user, array $data): FinancialProfile
    {
        return FinancialProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'monthly_income' => $data['monthly_income'] ?? 0,
                'monthly_expenses' => $data['monthly_expenses'] ?? 0,
                'savings' => $data['savings'] ?? 0,
                'risk_tolerance' => $data['risk_tolerance'] ?? 'moderate',
                'investment_horizon_months' => $data['investment_horizon_months'] ?? 36,
            ]
        );
    }

    /**
     * Cuánto puede destinar el usuario a inversión cada mes,
     * según lo que reporta en su perfil (ingreso menos gasto).
     */
    public function monthlySavingsCapacity(FinancialProfile $profile): float
    {
        return max(0, (float) $profile->monthly_income - (float) $profile->monthly_expenses);
    }
}
