<?php

namespace App\Services\Financial;

use App\Mcp\Support\ChartData;
use App\Models\Holding;
use App\Models\User;
use App\Services\Contracts\Exceptions\MarketDataUnavailableException;
use App\Services\Contracts\MarketDataProviderContract;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Support\CurrencyConverter;

class EloquentPortfolioService implements PortfolioServiceContract
{
    public function __construct(
        private readonly MarketDataProviderContract $marketData,
        private readonly CurrencyConverter $currency,
    ) {}

    public function getPortfolio(User $user): array
    {
        $portfolios = $user->portfolios()->with('holdings.asset')->get();

        return [
            'portfolios' => $portfolios->map(function ($portfolio): array {
                $holdings = $portfolio->holdings->map(
                    fn (Holding $holding): array => $this->holdingToArray($holding)
                )->all();

                return [
                    'id' => $portfolio->id,
                    'name' => $portfolio->name,
                    'description' => $portfolio->description,
                    'holdings' => $holdings,
                    'totals' => $this->totals($holdings),
                    'chart' => $this->gainChart($holdings),
                ];
            })->all(),
        ];
    }

    /**
     * A2UI contract gap 3: `portfolio_summary` no traía un total, solo la
     * lista de holdings. Los montos se normalizan a una sola moneda antes de
     * sumar (gap 8) -- el seed mezcla AAPL/MSFT en USD con CETES28 en MXN.
     *
     * @param  array<int, array<string, mixed>>  $holdings
     * @return array<string, mixed>
     */
    private function totals(array $holdings): array
    {
        $costBasis = 0.0;
        $marketValue = 0.0;
        $unrealizedGain = 0.0;
        $hasUnpricedHoldings = false;

        foreach ($holdings as $holding) {
            if ($holding['valuation_source'] === null) {
                $hasUnpricedHoldings = true;

                continue;
            }

            $costBasis += $this->currency->toBaseCurrency($holding['cost_basis'], $holding['currency']);
            $marketValue += $this->currency->toBaseCurrency($holding['market_value'], $holding['currency']);
            $unrealizedGain += $this->currency->toBaseCurrency($holding['unrealized_gain'], $holding['currency']);
        }

        return [
            'currency' => $this->currency->baseCurrency(),
            'cost_basis' => round($costBasis, 2),
            'market_value' => round($marketValue, 2),
            'unrealized_gain' => round($unrealizedGain, 2),
            // Igual que `priced_with_live_market_data` en el análisis de
            // riesgo: significa que algún holding se quedó fuera del total,
            // no que el resto no se cotizó en vivo.
            'has_unpriced_holdings' => $hasUnpricedHoldings,
        ];
    }

    /**
     * Barras horizontales de rendimiento % por posición (A2UI contract gap
     * 17/13) -- solo las posiciones valuadas en vivo tienen un % comparable.
     *
     * @param  array<int, array<string, mixed>>  $holdings
     */
    private function gainChart(array $holdings): ?array
    {
        $priced = array_values(array_filter($holdings, fn (array $h): bool => $h['unrealized_gain_pct'] !== null));

        if ($priced === []) {
            return null;
        }

        // Barras horizontales empiezan en 0 (se lee longitud) -- si alguna
        // posición está en pérdida, el dominio se extiende hacia abajo del 0
        // en vez de asumir que todas las barras son ganancias.
        $values = array_column($priced, 'unrealized_gain_pct');
        $domainMin = min(0.0, min($values));
        $domainMax = max(1.0, max($values));

        $excluded = [];
        foreach ($holdings as $holding) {
            if ($holding['unrealized_gain_pct'] === null) {
                $excluded[] = [
                    'key' => $holding['symbol'],
                    'reason' => $holding['valuation_source'] === 'face_value' ? 'face_value' : 'unpriced',
                ];
            }
        }

        return ChartData::make(
            type: 'bar',
            data: array_map(fn (array $h): array => [
                'key' => $h['symbol'],
                'label' => $h['name'],
                'value' => $h['unrealized_gain_pct'],
            ], $priced),
            unit: 'percent',
            xAxis: ['domain' => [$domainMin, $domainMax]],
            excluded: $excluded === [] ? null : $excluded,
        );
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
            // Solo tiene sentido comparar % de rendimiento entre posiciones
            // valuadas en vivo -- el efectivo siempre "gana" 0% a valor facial,
            // y eso no es una señal de rendimiento (A2UI contract gap 17).
            'unrealized_gain_pct' => $valuationSource === 'market' && $costBasis > 0
                ? round(($unrealizedGain / $costBasis) * 100, 2)
                : null,
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
