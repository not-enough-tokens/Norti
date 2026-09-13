<?php

namespace App\Services\Financial;

use App\Models\User;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;
use App\Services\RiskAnalysisService;

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
    ) {}

    public function analyze(User $user): array
    {
        $valueByType = [];
        $totalValue = 0.0;
        $hasUnpricedHoldings = false;

        foreach ($this->portfolios->getPortfolio($user)['portfolios'] as $portfolio) {
            foreach ($portfolio['holdings'] as $holding) {
                $value = $holding['market_value'] ?? $holding['cost_basis'];

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

        return [
            'has_holdings' => true,
            'allocation_by_asset_type' => $this->withEveryAssetType($allocation),
            'diversification_score' => round(1 - $herfindahl, 4),
            'concentration_warning' => max($allocation) > 50.0,
            'priced_with_live_market_data' => ! $hasUnpricedHoldings,
            'risk_tolerance' => $riskTolerance,
            'recommended_allocation_by_asset_type' => $recommendedAllocation,
        ];
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
