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
                ->etc());
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
