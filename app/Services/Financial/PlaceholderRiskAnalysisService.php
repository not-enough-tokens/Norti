<?php

namespace App\Services\Financial;

use App\Models\User;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;

/**
 * PLACEHOLDER: la metodología de riesgo real (algoritmo, métricas de
 * diversificación) es decisión de Integrante A/M2. Esta implementación solo
 * calcula un allocation por asset_type y un índice de concentración simple
 * (Herfindahl invertido), para que analyze_portfolio tenga un shape de
 * respuesta realista mientras se entrega el algoritmo definitivo. Reemplazar
 * el binding en DomainServiceProvider cuando esté listo, sin tocar la tool.
 */
class PlaceholderRiskAnalysisService implements RiskAnalysisServiceContract
{
    public function __construct(
        private readonly PortfolioServiceContract $portfolios,
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

        if ($totalValue <= 0.0) {
            return [
                'has_holdings' => false,
                'allocation_by_asset_type' => [],
                'diversification_score' => null,
                'concentration_warning' => false,
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
            'allocation_by_asset_type' => $allocation,
            'diversification_score' => round(1 - $herfindahl, 4),
            'concentration_warning' => max($allocation) > 50.0,
            'priced_with_live_market_data' => ! $hasUnpricedHoldings,
        ];
    }
}
