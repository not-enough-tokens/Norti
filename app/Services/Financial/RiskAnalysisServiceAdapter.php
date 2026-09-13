<?php

namespace App\Services\Financial;

use App\Mcp\Support\ChartData;
use App\Models\User;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;
use App\Services\RiskAnalysisService;
use App\Services\Support\CurrencyConverter;

/**
 * Adapter over Integrante A/M2's real RiskAnalysisService. The actual-holdings
 * allocation/diversification math stays here (RiskAnalysisService only knows
 * how to recommend an allocation for a risk profile, not analyze existing
 * holdings) but the recommended target allocation and risk-profile suggestion
 * now come from the real service instead of a placeholder.
 */
class RiskAnalysisServiceAdapter implements RiskAnalysisServiceContract
{
    public function __construct(
        private readonly PortfolioServiceContract $portfolios,
        private readonly RiskAnalysisService $riskAnalysis,
        private readonly CurrencyConverter $currency,
    ) {}

    public function analyze(User $user): array
    {
        $valueByType = [];
        $totalValue = 0.0;
        $hasUnpricedHoldings = false;

        foreach ($this->portfolios->getPortfolio($user)['portfolios'] as $portfolio) {
            foreach ($portfolio['holdings'] as $holding) {
                // A2UI contract gap 8: sin esto, un portafolio con AAPL/MSFT
                // en USD y CETES28 en MXN sumaba pesos y dólares como si
                // fueran la misma unidad.
                $value = $this->currency->toBaseCurrency(
                    $holding['market_value'] ?? $holding['cost_basis'],
                    $holding['currency'],
                );

                if ($holding['market_value'] === null) {
                    $hasUnpricedHoldings = true;
                }

                $valueByType[$holding['asset_type']] = ($valueByType[$holding['asset_type']] ?? 0.0) + $value;
                $totalValue += $value;
            }
        }

        [$riskTolerance, $recommendedAllocation] = $this->recommendedAllocationFor($user);

        if ($totalValue <= 0.0) {
            return [
                'has_holdings' => false,
                'allocation_by_asset_type' => [],
                'diversification_score' => null,
                'concentration_warning' => false,
                'risk_tolerance' => $riskTolerance,
                'recommended_allocation_by_asset_type' => $recommendedAllocation,
                'chart' => $this->expectedReturnChart($riskTolerance, null),
            ];
        }

        $allocation = [];
        $herfindahl = 0.0;

        foreach ($valueByType as $type => $value) {
            $share = $value / $totalValue;
            $allocation[$type] = round($share * 100, 2);
            $herfindahl += $share ** 2;
        }

        arsort($allocation);
        $allocation = $this->withEveryAssetType($allocation);

        return [
            'has_holdings' => true,
            'allocation_by_asset_type' => $allocation,
            'diversification_score' => round(1 - $herfindahl, 4),
            'concentration_warning' => max($allocation) > 50.0,
            // Significa "ningún holding se quedó sin valuar", no "todo se
            // cotizó en vivo": el efectivo se valúa a valor facial sin tocar el
            // proveedor. El detalle exacto por posición está en el
            // `valuation_source` de cada holding.
            'priced_with_live_market_data' => ! $hasUnpricedHoldings,
            'risk_tolerance' => $riskTolerance,
            'recommended_allocation_by_asset_type' => $recommendedAllocation,
            'chart' => $this->expectedReturnChart($riskTolerance, $allocation['accion'] ?? null),
        ];
    }

    /**
     * Dispersión riesgo/rendimiento de los 3 perfiles (A2UI contract gap 19).
     * El rendimiento esperado de *este* portafolio real está bloqueado en una
     * decisión de M2 (falta `expected_annual_return` por `asset_type`) -- como
     * proxy, mientras tanto, `reference_x` usa el % en `accion` del portafolio
     * contra el mismo eje que separa los 3 perfiles (ver a2ui-charts-todo.md).
     */
    private function expectedReturnChart(?string $riskTolerance, ?float $accionAllocation): array
    {
        $equityByProfile = config('investment_rules.asset_allocation', []);
        $expectedReturn = config('investment_rules.expected_annual_return', []);

        $data = [];
        foreach (config('investment_rules.risk_levels', []) as $profile) {
            $data[] = array_filter([
                'key' => $profile,
                'x' => $equityByProfile[$profile]['accion'] ?? 0,
                'y' => round(($expectedReturn[$profile] ?? 0) * 100, 2),
                'emphasis' => $profile === $riskTolerance ? 'user_profile' : null,
            ], fn (mixed $value): bool => $value !== null);
        }

        $yMax = max(1.0, ceil(max(array_column($data, 'y')) * 1.2));

        return ChartData::make(
            type: 'scatter',
            data: $data,
            xAxis: ['unit' => 'percent', 'domain' => [0, 100]],
            yAxis: ['unit' => 'percent', 'domain' => [0, $yMax], 'ticks' => [0, round($yMax / 2, 1), $yMax]],
            referenceX: $accionAllocation !== null ? ['key' => 'portfolio', 'value' => $accionAllocation] : null,
        );
    }

    /**
     * @return array{0: ?string, 1: ?array<string, float>}
     */
    private function recommendedAllocationFor(User $user): array
    {
        $profile = $user->financialProfile;

        if (! $profile) {
            return [null, null];
        }

        $riskTolerance = $this->riskAnalysis->suggestRiskProfile($profile);

        return [
            $riskTolerance,
            $this->withEveryAssetType($this->riskAnalysis->assetAllocation($riskTolerance)),
        ];
    }

    /**
     * Rellena con 0 los tipos de activo que la distribución no menciona, para
     * que la real y la recomendada siempre tengan las mismas llaves y el
     * componente A2UI pueda compararlas lado a lado. Sin esto, un usuario 100%
     * en efectivo veía un bucket `efectivo` en su distribución real que no
     * existía en la recomendada -- ver ADR 005, opción B.
     *
     * @param  array<string, float|int>  $allocation
     * @return array<string, float|int>
     */
    private function withEveryAssetType(array $allocation): array
    {
        foreach (config('investment_rules.asset_types', []) as $assetType) {
            $allocation[$assetType] ??= 0;
        }

        return $allocation;
    }
}
