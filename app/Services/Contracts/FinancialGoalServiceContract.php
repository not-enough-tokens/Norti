<?php

namespace App\Services\Contracts;

use App\Models\User;

interface FinancialGoalServiceContract
{
    /**
     * @param  string  $detail  "summary" (default, no exact amounts) or "exact".
     * @return array<string, mixed>
     */
    public function getGoals(User $user, string $detail = 'summary'): array;
}
