<?php

namespace Tests\Feature\Services;

use App\Models\Asset;
use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\PortfolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * assetAllocation() devolvía llaves en inglés (bond/fund/stock) mientras
     * que assets.asset_type usa español, así que el Asset::where() de
     * createForUser() nunca empataba y el portafolio salía vacío en silencio.
     */
    public function test_creates_holdings_matching_the_recommended_allocation(): void
    {
        $user = User::factory()->create();
        $profile = FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'moderate']);

        $bono = Asset::factory()->create(['asset_type' => 'bono']);
        $fondo = Asset::factory()->create(['asset_type' => 'fondo']);
        $accion = Asset::factory()->create(['asset_type' => 'accion']);

        $portfolio = app(PortfolioService::class)->createForUser($user, 'Mi portafolio', $profile, 10_000);

        $this->assertCount(3, $portfolio->holdings);

        $byAsset = $portfolio->holdings->keyBy('asset_id');
        $this->assertEqualsWithDelta(5_000, $byAsset[$bono->id]->quantity, 0.01); // 50%
        $this->assertEqualsWithDelta(3_000, $byAsset[$fondo->id]->quantity, 0.01); // 30%
        $this->assertEqualsWithDelta(2_000, $byAsset[$accion->id]->quantity, 0.01); // 20%
    }

    public function test_an_unrecognized_risk_tolerance_still_produces_a_portfolio(): void
    {
        $user = User::factory()->create();
        $profile = FinancialProfile::factory()->for($user)->create([
            'risk_tolerance' => 'muy agresivo',
            'investment_horizon_months' => 6, // cae a conservative
        ]);

        Asset::factory()->create(['asset_type' => 'bono']);
        Asset::factory()->create(['asset_type' => 'fondo']);

        $portfolio = app(PortfolioService::class)->createForUser($user, 'Fallback', $profile, 1_000);

        $this->assertCount(2, $portfolio->holdings);
        $this->assertEqualsWithDelta(1_000, app(PortfolioService::class)->totalInvested($portfolio), 0.01);
    }
}
