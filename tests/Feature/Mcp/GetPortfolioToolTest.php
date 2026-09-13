<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetPortfolio;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetPortfolioToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_holdings_enriched_with_live_market_value(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
        ]);

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($asset)->create([
            'quantity' => 10,
            'average_cost' => 100,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetPortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'portfolio_summary')
                ->where('props.portfolios.0.holdings.0.symbol', 'AAPL')
                ->where('props.portfolios.0.holdings.0.cost_basis', 1000)
                ->where('props.portfolios.0.holdings.0.current_price', 150)
                ->where('props.portfolios.0.holdings.0.market_value', 1500)
                ->where('props.portfolios.0.holdings.0.unrealized_gain', 500)
                ->where('props.portfolios.0.holdings.0.actions', [
                    [
                        'id' => 'view_asset_AAPL',
                        'label' => 'Ver información de AAPL',
                        'tool' => 'get_asset_information',
                        'params' => ['symbol' => 'AAPL'],
                    ],
                ])
                ->where('props.actions', [
                    [
                        'id' => 'analyze_risk',
                        'label' => 'Analizar riesgo',
                        'tool' => 'analyze_portfolio',
                        'params' => [],
                    ],
                ])
                ->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetPortfolio::class, [])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
