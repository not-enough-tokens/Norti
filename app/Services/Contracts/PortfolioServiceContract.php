<?php

namespace App\Services\Contracts;

use App\Models\User;

interface PortfolioServiceContract
{
    /**
     * @return array<string, mixed>
     */
    public function getPortfolio(User $user): array;
}
