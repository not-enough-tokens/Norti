<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\Portfolio;
use App\Models\User;

class FinancialEducationIntegrationService
{
    public function getFinancialContext(User $user): array
    {
        $profile = FinancialProfile::where('user_id', $user->id)->first();

        $goals = FinancialGoal::where('user_id', $user->id)
            ->get();

        $portfolios = Portfolio::where('user_id', $user->id)
            ->with('holdings.asset')
            ->get();

        return [
            'profile' => $profile,
            'goals' => $goals,
            'portfolios' => $portfolios,
        ];
    }
}
