<?php

namespace App\Services;

use App\Models\FinancialProfile;
use InvalidArgumentException;

class RiskAnalysisService
{
    /**
     * Rendimiento anual esperado (simulado) para un perfil de riesgo dado.
     */
    public function expectedAnnualReturn(string $riskTolerance): float
    {
        $this->validateRiskLevel($riskTolerance);

        return config("investment_rules.expected_annual_return.{$riskTolerance}");
    }

    /**
     * Distribución sugerida (%) por tipo de activo para un perfil de riesgo.
     * Ej: ['bond' => 50, 'fund' => 30, 'stock' => 20]
     */
    public function assetAllocation(string $riskTolerance): array
    {
        $this->validateRiskLevel($riskTolerance);

        return config("investment_rules.asset_allocation.{$riskTolerance}");
    }

    /**
     * Sugiere un perfil de riesgo cuando el usuario no lo tiene definido,
     * basándose en su horizonte de inversión. Esto es solo un fallback:
     * si el perfil ya tiene risk_tolerance, se respeta ese valor.
     */
    public function suggestRiskProfile(FinancialProfile $profile): string
    {
        if (! empty($profile->risk_tolerance)) {
            return $profile->risk_tolerance;
        }

        $months = $profile->investment_horizon_months ?? 0;

        return match (true) {
            $months < 24 => 'conservative',
            $months < 60 => 'moderate',
            default => 'aggressive',
        };
    }

    private function validateRiskLevel(string $riskTolerance): void
    {
        if (! in_array($riskTolerance, config('investment_rules.risk_levels'), true)) {
            throw new InvalidArgumentException("Perfil de riesgo inválido: {$riskTolerance}");
        }
    }
}
