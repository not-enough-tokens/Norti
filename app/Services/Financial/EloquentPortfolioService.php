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
        $costBasis = $quantity * (float) $holding->average_cost;
        $currentPrice = $this->currentPrice($asset->symbol);
        $marketValue = $currentPrice !== null ? $quantity * $currentPrice : null;

        return [
            'symbol' => $asset->symbol,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'currency' => $asset->currency,
            'quantity' => $quantity,
            'average_cost' => (float) $holding->average_cost,
            'cost_basis' => $costBasis,
            'current_price' => $currentPrice,
            'market_value' => $marketValue,
            'unrealized_gain' => $marketValue !== null ? $marketValue - $costBasis : null,
        ];
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
