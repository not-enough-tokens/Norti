<?php

namespace App\Services\Contracts;

use App\Models\User;

interface FinancialProfileServiceContract
{
    /**
     * @param  string  $detail  "summary" (default, no exact amounts) or "exact".
     * @return array<string, mixed>
     */
    public function getProfile(User $user, string $detail = 'summary'): array;
}
