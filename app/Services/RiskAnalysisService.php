<?php

namespace App\Services;

use App\Models\FinancialProfile;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tabla de políticas del motor financiero: traduce un perfil de riesgo
 * (conservative/moderate/aggressive) a los supuestos de simulación definidos
 * en config/investment_rules.php. No analiza portafolios reales -- de eso se
 * encarga RiskAnalysisServiceAdapter, que sí mira los holdings del usuario.
 */
class RiskAnalysisService
{
    /**
     * Rendimiento anual esperado (simulado) para un perfil de riesgo dado.
     */
    public function expectedAnnualReturn(string $riskTolerance): float
    {
        $level = $this->requireRiskLevel($riskTolerance);

        $return = config("investment_rules.expected_annual_return.{$level}");

        if (! is_numeric($return)) {
            throw new RuntimeException(
                "Falta expected_annual_return para el perfil '{$level}' en config/investment_rules.php."
            );
        }

        return (float) $return;
    }

    /**
     * Distribución sugerida (%) por tipo de activo para un perfil de riesgo.
     * Las llaves usan el vocabulario de `assets.asset_type`.
     * Ej: ['bono' => 50, 'fondo' => 30, 'accion' => 20]
     *
     * @return array<string, float|int>
     */
    public function assetAllocation(string $riskTolerance): array
    {
        $level = $this->requireRiskLevel($riskTolerance);

        $allocation = config("investment_rules.asset_allocation.{$level}");

        if (! is_array($allocation) || $allocation === []) {
            throw new RuntimeException(
                "Falta asset_allocation para el perfil '{$level}' en config/investment_rules.php."
            );
        }

        return $allocation;
    }

    /**
     * Perfil de riesgo a usar para un FinancialProfile. Respeta lo que el
     * usuario tenga guardado, pero solo si es un perfil que conocemos: la
     * columna `risk_tolerance` es un string libre (sin enum ni check), así que
     * puede traer '', 'Moderate' o cualquier cosa que escriba el onboarding.
     * Cuando no es reconocible, cae al horizonte de inversión en vez de
     * propagar un valor que reventaría en expectedAnnualReturn()/assetAllocation().
     */
    public function suggestRiskProfile(FinancialProfile $profile): string
    {
        $stored = $this->normalizeRiskLevel($profile->risk_tolerance);

        if ($stored !== null) {
            return $stored;
        }

        $months = $profile->investment_horizon_months ?? 0;

        return match (true) {
            $months < 24 => 'conservative',
            $months < 60 => 'moderate',
            default => 'aggressive',
        };
    }

    /**
     * Devuelve el perfil canónico, o null si no corresponde a ninguno conocido.
     * Tolera espacios y mayúsculas ('  Moderate ' -> 'moderate').
     */
    public function normalizeRiskLevel(?string $riskTolerance): ?string
    {
        if ($riskTolerance === null) {
            return null;
        }

        $normalized = strtolower(trim($riskTolerance));

        return in_array($normalized, $this->riskLevels(), true) ? $normalized : null;
    }

    /**
     * @return array<int, string>
     */
    public function riskLevels(): array
    {
        return config('investment_rules.risk_levels', []);
    }

    private function requireRiskLevel(string $riskTolerance): string
    {
        return $this->normalizeRiskLevel($riskTolerance)
            ?? throw new InvalidArgumentException("Perfil de riesgo inválido: {$riskTolerance}");
    }
}
