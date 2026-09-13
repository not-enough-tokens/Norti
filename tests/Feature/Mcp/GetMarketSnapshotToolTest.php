<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetMarketSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetMarketSnapshotToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_quote_per_symbol_and_degrades_a_single_failure(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['symbol'] ?? null) {
                'AAPL' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
                default => Http::response([
                    'code' => 400,
                    'message' => 'symbol parameter is missing or incorrect',
                    'status' => 'error',
                ]),
            };
        });

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => ['AAPL', 'INVALID']])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'market_snapshot_grid')
                ->where('props.quotes.AAPL.close', '150.00')
                ->has('props.quotes.INVALID.error')
                ->where('props.quotes.AAPL.actions', [
                    [
                        'id' => 'view_asset_AAPL',
                        'label' => 'Ver información de AAPL',
                        'tool' => 'get_asset_information',
                        'params' => ['symbol' => 'AAPL'],
                    ],
                ])
                ->where('props.quotes.INVALID.actions', [
                    [
                        'id' => 'retry_INVALID',
                        'label' => 'Reintentar',
                        'tool' => 'get_market_snapshot',
                        'params' => ['symbols' => ['INVALID']],
                    ],
                ])
                ->etc());
    }

    public function test_includes_a_column_chart_with_the_percent_change_per_symbol(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['symbol'] ?? null) {
                'AAPL' => Http::response(['symbol' => 'AAPL', 'close' => '150.00', 'percent_change' => '1.24']),
                'MSFT' => Http::response(['symbol' => 'MSFT', 'close' => '300.00', 'percent_change' => '-0.82']),
                default => Http::response(['status' => 'error', 'message' => 'unknown'], 400),
            };
        });

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => ['AAPL', 'MSFT', 'CETES28']])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.chart.type', 'column')
                ->where('props.chart.unit', 'percent')
                ->where('props.chart.data', [
                    ['key' => 'AAPL', 'value' => 1.24],
                    ['key' => 'MSFT', 'value' => -0.82],
                    ['key' => 'CETES28', 'value' => null],
                ])
                ->where('props.chart.y_axis.domain', [-1.24, 1.24])
                ->etc());
    }

    /**
     * Los fallos por símbolo se devuelven en el payload en vez de abortar, así
     * que el audit log registraba 'ok' aunque no se obtuviera una sola cotización.
     */
    public function test_audits_a_snapshot_where_every_symbol_failed(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['status' => 'error', 'message' => 'nope'], 400),
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => ['AAPL', 'TSLA']])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'tool_name' => 'get_market_snapshot',
            'result_summary' => 'market_data_unavailable',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'tool_name' => 'get_market_snapshot',
            'result_summary' => 'ok',
        ]);
    }

    public function test_does_not_spend_a_request_per_repeated_symbol(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => ['AAPL', 'aapl', 'AAPL']])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_requires_at_least_one_symbol(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => []])
            ->assertHasErrors();
    }

    public function test_rejects_more_than_10_symbols(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        $symbols = array_map(fn (int $i): string => "SYM{$i}", range(1, 11));

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => $symbols])
            ->assertHasErrors();
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetMarketSnapshot::class, ['symbols' => ['AAPL']])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
