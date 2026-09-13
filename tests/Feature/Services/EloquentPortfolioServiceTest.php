<?php

namespace Tests\Feature\Services;

use App\Models\Asset;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\Contracts\PortfolioServiceContract;
use App\Services\Contracts\RiskAnalysisServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EloquentPortfolioServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cashHoldingFor(User $user, float $amount = 50_000): void
    {
        $portfolio = Portfolio::factory()->for($user)->create();
        $cash = Asset::factory()->create(['symbol' => 'MXNCASH', 'asset_type' => 'efectivo']);

        Holding::factory()->for($portfolio)->for($cash)->create([
            'quantity' => $amount,
            'average_cost' => 1,
        ]);
    }

    /**
     * El efectivo no es un instrumento cotizable: TwelveData responde error para
     * un símbolo así, lo que gastaba una llamada de la cuota (8 req/min) y
     * dejaba el holding con market_value => null, es decir, reportado como no
     * valuado justo el activo cuyo valor se conoce con certeza.
     */
    public function test_cash_is_valued_at_face_value_without_calling_the_provider(): void
    {
        Http::preventStrayRequests();

        $user = User::factory()->create();
        $this->cashHoldingFor($user);

        $holding = app(PortfolioServiceContract::class)
            ->getPortfolio($user->fresh())['portfolios'][0]['holdings'][0];

        $this->assertSame(50_000.0, $holding['market_value']);
        $this->assertSame(0.0, $holding['unrealized_gain']);
        $this->assertSame('face_value', $holding['valuation_source']);

        Http::assertNothingSent();
    }

    public function test_quotable_assets_are_still_priced_against_the_provider(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '200.00']),
        ]);

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 150]);

        $holding = app(PortfolioServiceContract::class)
            ->getPortfolio($user->fresh())['portfolios'][0]['holdings'][0];

        $this->assertSame(2_000.0, $holding['market_value']);
        $this->assertSame(500.0, $holding['unrealized_gain']);
        $this->assertSame('market', $holding['valuation_source']);
    }

    public function test_an_unpriceable_quotable_asset_is_reported_as_unvalued(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['status' => 'error', 'message' => 'nope'], 400),
        ]);

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 150]);

        $holding = app(PortfolioServiceContract::class)
            ->getPortfolio($user->fresh())['portfolios'][0]['holdings'][0];

        $this->assertNull($holding['market_value']);
        $this->assertNull($holding['valuation_source']);
    }

    /**
     * Antes, un portafolio solo de efectivo salía con
     * priced_with_live_market_data => false porque la cotización del efectivo
     * fallaba.
     */
    public function test_a_cash_only_portfolio_is_not_flagged_as_unpriced(): void
    {
        Http::preventStrayRequests();

        $user = User::factory()->create();
        $this->cashHoldingFor($user);

        $analysis = app(RiskAnalysisServiceContract::class)->analyze($user->fresh());

        $this->assertTrue($analysis['has_holdings']);
        $this->assertTrue($analysis['priced_with_live_market_data']);
        $this->assertSame(100.0, $analysis['allocation_by_asset_type']['efectivo']);
    }
}
