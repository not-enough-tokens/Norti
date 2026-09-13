<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;

class PortfolioService
{
    public function __construct(
        private RiskAnalysisService $riskAnalysisService
    ) {}

    /**
     * Crea un portafolio para el usuario y arma sus holdings de acuerdo
     * a la distribución sugerida para su perfil de riesgo.
     *
     * Nota: como el catálogo de Assets no trae un "precio" de mercado,
     * simplificamos usando quantity = monto asignado en pesos y
     * average_cost = 1, para que la simulación sea consistente sin
     * necesitar precios reales de cada instrumento.
     */
    public function createForUser(User $user, string $name, FinancialProfile $profile, float $totalAmount): Portfolio
    {
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => "Portafolio generado según perfil {$profile->risk_tolerance}",
        ]);

        // Vía suggestRiskProfile() y no con $profile->risk_tolerance crudo:
        // la columna es un string libre y un valor no reconocido reventaría aquí.
        $allocation = $this->riskAnalysisService->assetAllocation(
            $this->riskAnalysisService->suggestRiskProfile($profile)
        );

        foreach ($allocation as $assetType => $percentage) {
            $asset = Asset::where('asset_type', $assetType)->first();

            if (! $asset) {
                continue; // no hay un instrumento de ese tipo en el catálogo todavía
            }

            $montoAsignado = $totalAmount * ($percentage / 100);

            Holding::create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'quantity' => $montoAsignado,
                'average_cost' => 1,
            ]);
        }

        return $portfolio->load('holdings.asset');
    }

    /**
     * Monto total invertido en el portafolio (suma de quantity * average_cost
     * de todos sus holdings).
     */
    public function totalInvested(Portfolio $portfolio): float
    {
        return $portfolio->holdings->sum(fn (Holding $h) => $h->quantity * $h->average_cost);
    }

    /**
     * Distribución actual del portafolio en porcentajes, por tipo de activo.
     * Útil para mostrarlo en la UI (ej. gráfica de pastel).
     */
    public function currentDistribution(Portfolio $portfolio): array
    {
        $total = $this->totalInvested($portfolio);

        if ($total <= 0) {
            return [];
        }

        return $portfolio->holdings
            ->loadMissing('asset')
            ->groupBy(fn (Holding $h) => $h->asset->asset_type)
            ->map(function ($holdings) use ($total) {
                $subtotal = $holdings->sum(fn (Holding $h) => $h->quantity * $h->average_cost);

                return round(($subtotal / $total) * 100, 2);
            })
            ->toArray();
    }
}
