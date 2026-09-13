<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\AnalyzePortfolio;
use App\Models\Asset;
use App\Models\EducationalTopic;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\Contracts\RiskAnalysisServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use RuntimeException;
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

    /**
     * A2UI contract gap 8: el seed real mezcla AAPL en USD con CETES28 en MXN
     * dentro del mismo portafolio -- sin convertir, la distribución sumaba
     * pesos y dólares como si fueran la misma unidad.
     */
    public function test_normalizes_currencies_before_computing_the_allocation(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['symbol'] ?? null) {
                'AAPL' => Http::response(['symbol' => 'AAPL', 'close' => '200.00']),
                'CETES28' => Http::response(['symbol' => 'CETES28', 'close' => '10.00']),
                default => Http::response(['status' => 'error'], 400),
            };
        });

        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        // 10 AAPL @ $200 USD -> 2,000 USD * 18.5 = 37,000 MXN.
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion', 'currency' => 'USD']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 150]);

        // 1000 CETES28 @ $10 MXN -> 10,000 MXN.
        $bond = Asset::factory()->create(['symbol' => 'CETES28', 'asset_type' => 'bono', 'currency' => 'MXN']);
        Holding::factory()->for($portfolio)->for($bond)->create(['quantity' => 1000, 'average_cost' => 10]);

        Passport::actingAs($user, ['mcp:read']);

        // Total = 37,000 + 10,000 = 47,000 MXN -> accion 78.72%, bono 21.28%.
        // Sin convertir (sumando 2,000 + 10,000 = 12,000 crudo) hubiera dado
        // accion 16.67% / bono 83.33% -- justo al revés.
        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.allocation_by_asset_type.accion', 78.72)
                ->where('props.allocation_by_asset_type.bono', 21.28)
                ->etc());
    }

    public function test_reports_no_holdings_when_portfolio_is_empty(): void
    {
        $user = User::factory()->create();
        Portfolio::factory()->for($user)->create();

        Passport::actingAs($user, ['mcp:read']);

        // Los 3 puntos del scatter (brecha 19) salen de investment_rules.php:
        // accion% por perfil (0/20/60) y expected_annual_return*100 (6.5/9/12.5).
        // Sin holdings no hay `reference_x` -- no hay portafolio que ubicar.
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
                    'chart' => [
                        'type' => 'scatter',
                        'x_axis' => ['unit' => 'percent', 'domain' => [0, 100]],
                        'y_axis' => ['unit' => 'percent', 'domain' => [0, 15], 'ticks' => [0, 7.5, 15]],
                        'data' => [
                            ['key' => 'conservative', 'x' => 0, 'y' => 6.5],
                            ['key' => 'moderate', 'x' => 20, 'y' => 9],
                            ['key' => 'aggressive', 'x' => 60, 'y' => 12.5],
                        ],
                    ],
                    'actions' => [],
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

        // RiskAnalysisService::assetAllocation('moderate') = ['bono'=>50,'fondo'=>30,'accion'=>20].
        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.risk_tolerance', 'moderate')
                ->where('props.recommended_allocation_by_asset_type.accion', 20)
                ->where('props.recommended_allocation_by_asset_type.bono', 50)
                ->where('props.recommended_allocation_by_asset_type.fondo', 30)
                ->etc());
    }

    /**
     * ADR 005 opción B: la distribución real y la recomendada deben traer
     * siempre las mismas llaves para poder compararse. Antes, un usuario 100%
     * en efectivo veía un bucket `efectivo` que no existía del otro lado.
     */
    public function test_both_allocations_expose_the_same_asset_type_keys(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'MXNCASH', 'close' => '1.00']),
        ]);

        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'conservative']);

        $portfolio = Portfolio::factory()->for($user)->create();
        $cash = Asset::factory()->create(['symbol' => 'MXNCASH', 'asset_type' => 'efectivo']);
        Holding::factory()->for($portfolio)->for($cash)->create(['quantity' => 50_000, 'average_cost' => 1]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('props.allocation_by_asset_type.efectivo', 100)
                ->where('props.allocation_by_asset_type.bono', 0)
                ->where('props.allocation_by_asset_type.fondo', 0)
                ->where('props.allocation_by_asset_type.accion', 0)
                // La calibración de Integrante A se respeta: conservative sigue
                // siendo 80/20 bono/fondo, solo se hace explícito el 0% efectivo.
                ->where('props.recommended_allocation_by_asset_type.bono', 80)
                ->where('props.recommended_allocation_by_asset_type.fondo', 20)
                ->where('props.recommended_allocation_by_asset_type.efectivo', 0)
                ->where('props.recommended_allocation_by_asset_type.accion', 0)
                ->etc());
    }

    /**
     * risk_tolerance es un string libre en la BD (sin enum ni check), así que
     * un valor que no esté en investment_rules.risk_levels es alcanzable --
     * antes hacía que la tool devolviera un error con la excepción interna.
     */
    public function test_survives_an_unrecognized_risk_tolerance_in_the_database(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create([
            'risk_tolerance' => 'Moderate',
            'investment_horizon_months' => 12,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.risk_tolerance', 'moderate')->etc());
    }

    /**
     * logToolCall(success: true) corría antes que el servicio (la llamada
     * vivía dentro del argumento de Response::structured()), así que una
     * excepción del servicio dejaba un audit log diciendo 'ok'.
     */
    public function test_does_not_audit_a_success_when_the_service_throws(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        $this->app->bind(RiskAnalysisServiceContract::class, fn () => new class implements RiskAnalysisServiceContract
        {
            public function analyze(User $user): array
            {
                throw new RuntimeException('boom');
            }
        });

        BanorteServer::tool(AnalyzePortfolio::class, []);

        $this->assertDatabaseMissing('audit_logs', [
            'tool_name' => 'analyze_portfolio',
            'result_summary' => 'ok',
        ]);
    }

    public function test_offers_simulate_and_learn_actions_when_recommendation_data_is_available(): void
    {
        Http::fake(['api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00'])]);

        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'moderate']);

        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['symbol' => 'AAPL', 'asset_type' => 'accion']);
        Holding::factory()->for($portfolio)->for($stock)->create(['quantity' => 10, 'average_cost' => 100]);

        $topic = EducationalTopic::create([
            'title' => 'Diversificación', 'slug' => 'diversificacion',
            'description' => '...', 'content' => '...',
            'category' => 'risk', 'difficulty' => 'beginner', 'estimated_minutes' => 5,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.actions', [
                [
                    'id' => 'simulate_with_recommended_profile',
                    'label' => 'Simular con perfil recomendado',
                    'tool' => 'simulate_investment',
                    'params' => ['risk_profile' => 'moderate'],
                ],
                [
                    'id' => 'learn_diversification',
                    'label' => 'Aprender a diversificar',
                    'tool' => 'get_educational_topic',
                    'params' => ['topic_id' => $topic->id],
                ],
            ])->etc());
    }

    public function test_omits_the_diversification_action_when_the_topic_is_not_seeded(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'moderate']);
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.actions', [
                [
                    'id' => 'simulate_with_recommended_profile',
                    'label' => 'Simular con perfil recomendado',
                    'tool' => 'simulate_investment',
                    'params' => ['risk_profile' => 'moderate'],
                ],
            ])->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }

    /**
     * Gap 9: Response::error() solo tomaba texto plano. errorResponse()
     * (LogsToolInvocation) lo estructura como JSON {code, message} sin
     * cambiar el mecanismo de error de laravel/mcp -- assertHasErrors()
     * sigue funcionando porque hace str_contains() contra el texto.
     */
    public function test_the_scope_denied_error_is_structured_with_a_code(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(AnalyzePortfolio::class, [])
            ->assertHasErrors([
                '"code":"scope_denied"',
                '"message":"No autorizado: se requiere el scope mcp:read."',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'tool_name' => 'analyze_portfolio',
            'result_summary' => 'scope_denied',
        ]);
    }
}
