<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\AnalyzePortfolio;
use App\Models\Asset;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AnalyzePortfolioToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_allocation_and_concentration_across_asset_types(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['symbol'] ?? null) {
                'AAPL' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
                'CETES28' => Http::response(['symbol' => 'CETES28', 'close' => '10.00']),
                default => Http::response(['status' => 'error', 'message' => 'unknown symbol'], 400),
            };
        });

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 100]);

        $bond = Asset::factory()->create(['symbol' => 'CETES28', 'asset_type' => 'bono']);
        Holding::factory()->for($portfolio)->for($bond)->create(['quantity' => 100, 'average_cost' => 10]);

        Passport::actingAs($user, ['mcp:read']);

        // Market values: AAPL 10*150=1500, CETES28 100*10=1000, total 2500 -> 60%/40%.
        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'risk_analysis_panel')
                ->where('props.has_holdings', true)
                ->where('props.allocation_by_asset_type.accion', 60)
                ->where('props.allocation_by_asset_type.bono', 40)
                ->where('props.concentration_warning', true)
                ->etc());
    }

    public function test_reports_no_holdings_when_portfolio_is_empty(): void
    {
        $user = User::factory()->create();
        Portfolio::factory()->for($user)->create();

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent([
                'component' => 'risk_analysis_panel',
                'props' => [
                    'has_holdings' => false,
                    'allocation_by_asset_type' => [],
                    'diversification_score' => null,
                    'concentration_warning' => false,
                    'risk_tolerance' => null,
                    'recommended_allocation_by_asset_type' => null,
                ],
            ]);
    }

    public function test_includes_the_recommended_allocation_when_the_user_has_a_financial_profile(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
        ]);

        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'moderate']);

        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 100]);

        Passport::actingAs($user, ['mcp:read']);

        // RiskAnalysisService::assetAllocation('moderate') = ['bond'=>50,'fund'=>30,'stock'=>20],
        // translated to Spanish asset_type keys: bono/fondo/accion.
        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.risk_tolerance', 'moderate')
                ->where('props.recommended_allocation_by_asset_type.accion', 20)
                ->where('props.recommended_allocation_by_asset_type.bono', 50)
                ->where('props.recommended_allocation_by_asset_type.fondo', 30)
                ->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
