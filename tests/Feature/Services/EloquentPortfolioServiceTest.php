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
        $this->assertSame(33.33, $holding['unrealized_gain_pct']);
    }

    /**
     * A2UI contract gap 17: solo una posición valuada en vivo tiene un %
     * comparable -- el efectivo "gana" 0% a valor facial por definición, y eso
     * no es una señal de rendimiento.
     */
    public function test_a_face_value_holding_has_no_gain_percentage(): void
    {
        Http::preventStrayRequests();

        $user = User::factory()->create();
        $this->cashHoldingFor($user);

        $holding = app(PortfolioServiceContract::class)
            ->getPortfolio($user->fresh())['portfolios'][0]['holdings'][0];

        $this->assertNull($holding['unrealized_gain_pct']);
    }

    /**
     * A2UI contract gap 3/8: el seed real mezcla AAPL/MSFT en USD con CETES28
     * en MXN dentro del mismo portafolio -- el total tiene que convertir antes
     * de sumar, no solo sumar los montos crudos.
     */
    public function test_totals_convert_every_holding_to_the_base_currency_before_summing(): void
    {
        Http::fake(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['symbol'] ?? null) {
                'AAPL' => Http::response(['symbol' => 'AAPL', 'close' => '200.00']),
                default => Http::response(['status' => 'error', 'message' => 'unknown'], 400),
            };
        });

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        // 10 AAPL @ $200 USD = $2,000 USD -> 2,000 * 18.5 = 37,000 MXN.
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion', 'currency' => 'USD']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 150]);

        // CETES28 no se puede cotizar en este fake -> queda sin valuar y fuera del total.
        $bond = Asset::factory()->create(['symbol' => 'CETES28', 'asset_type' => 'bono', 'currency' => 'MXN']);
        Holding::factory()->for($portfolio)->for($bond)->create(['quantity' => 1000, 'average_cost' => 10]);

        $result = app(PortfolioServiceContract::class)->getPortfolio($user->fresh());
        $totals = $result['portfolios'][0]['totals'];

        $this->assertSame('MXN', $totals['currency']);
        $this->assertSame(37_000.0, $totals['market_value']);
        $this->assertSame(27_750.0, $totals['cost_basis']); // 10 * 150 * 18.5
        $this->assertSame(9_250.0, $totals['unrealized_gain']); // 10 * 50 * 18.5
        $this->assertTrue($totals['has_unpriced_holdings']);
    }

    public function test_the_gain_chart_excludes_unpriced_holdings(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['status' => 'error', 'message' => 'unknown'], 400),
        ]);

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 150]);

        $result = app(PortfolioServiceContract::class)->getPortfolio($user->fresh());

        $this->assertNull($result['portfolios'][0]['chart']);
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
