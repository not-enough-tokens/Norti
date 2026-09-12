<?php

namespace App\Services\Contracts;

use App\Models\User;

interface RiskAnalysisServiceContract
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(User $user): array;
}
