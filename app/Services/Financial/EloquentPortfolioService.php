<?php

namespace App\Services\Financial;

use App\Models\Holding;
use App\Models\User;
use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\Contracts\PortfolioServiceContract;

class EloquentPortfolioService implements PortfolioServiceContract
{
    public function __construct(
        private readonly MarketDataProviderContract $marketData,
    ) {}

    public function getPortfolio(User $user): array
    {
        $portfolios = $user->portfolios()->with('holdings.asset')->get();

        return [
            'portfolios' => $portfolios->map(fn ($portfolio): array => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'description' => $portfolio->description,
                'holdings' => $portfolio->holdings->map(
                    fn (Holding $holding): array => $this->holdingToArray($holding)
                )->all(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function holdingToArray(Holding $holding): array
    {
        $asset = $holding->asset;
        $quantity = (float) $holding->quantity;
        $averageCost = (float) $holding->average_cost;
        $costBasis = $quantity * $averageCost;

        // El efectivo no cotiza: pedirle precio al proveedor siempre falla,
        // gasta una llamada de la cuota y lo deja marcado como no valuado. Se
        // valúa a valor facial, que para efectivo es exacto por definición.
        if ($this->isQuotable($asset->asset_type)) {
            $currentPrice = $this->currentPrice($asset->symbol);
            $marketValue = $currentPrice !== null ? $quantity * $currentPrice : null;
            $unrealizedGain = $marketValue !== null ? $marketValue - $costBasis : null;
            $valuationSource = $marketValue !== null ? 'market' : null;
        } else {
            $currentPrice = $averageCost;
            $marketValue = $costBasis;
            $unrealizedGain = 0.0;
            $valuationSource = 'face_value';
        }

        return [
            'symbol' => $asset->symbol,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'currency' => $asset->currency,
            'quantity' => $quantity,
            'average_cost' => $averageCost,
            'cost_basis' => $costBasis,
            'current_price' => $currentPrice,
            'market_value' => $marketValue,
            'unrealized_gain' => $unrealizedGain,
            // 'market' | 'face_value' | null (no se pudo valuar) -- para que el
            // consumidor no confunda "valuado a valor facial" con "cotizado".
            'valuation_source' => $valuationSource,
        ];
    }

    private function isQuotable(string $assetType): bool
    {
        return ! in_array($assetType, config('investment_rules.non_quotable_asset_types', []), true);
    }

    private function currentPrice(string $symbol): ?float
    {
        try {
            $quote = $this->marketData->quote($symbol);
        } catch (MarketDataUnavailableException) {
            return null;
        }

        return isset($quote['close']) ? (float) $quote['close'] : null;
    }
}
