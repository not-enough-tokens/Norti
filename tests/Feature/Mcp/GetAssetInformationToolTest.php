<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetAssetInformation;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetAssetInformationToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_merges_local_asset_data_with_a_live_quote_and_profile(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
            'api.twelvedata.com/profile*' => Http::response(['symbol' => 'AAPL', 'sector' => 'Technology']),
        ]);

        Asset::factory()->create([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc',
            'asset_type' => 'accion',
            'currency' => 'USD',
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetAssetInformation::class, ['symbol' => 'AAPL'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'asset_info_card')
                ->where('props.symbol', 'AAPL')
                ->where('props.local_asset.asset_type', 'accion')
                ->where('props.quote.close', '150.00')
                ->where('props.profile.sector', 'Technology')
                ->etc());
    }

    /**
     * El catálogo guarda tickers en mayúsculas y el `=` de Postgres distingue
     * mayúsculas, así que un símbolo en minúsculas devolvía local_asset => null
     * aunque el Asset sí estuviera sembrado.
     */
    public function test_finds_the_local_asset_regardless_of_symbol_case(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL', 'close' => '150.00']),
            'api.twelvedata.com/profile*' => Http::response(['symbol' => 'AAPL', 'sector' => 'Technology']),
        ]);

        Asset::factory()->create(['symbol' => 'AAPL', 'name' => 'Apple Inc', 'asset_type' => 'accion']);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetAssetInformation::class, ['symbol' => 'aapl'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.symbol', 'AAPL')
                ->where('props.local_asset.name', 'Apple Inc')
                ->etc());
    }

    public function test_still_returns_market_data_when_the_asset_is_not_seeded_locally(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'TSLA', 'close' => '200.00']),
            'api.twelvedata.com/profile*' => Http::response(['symbol' => 'TSLA', 'sector' => 'Automotive']),
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetAssetInformation::class, ['symbol' => 'TSLA'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.local_asset', null)
                ->where('props.quote.close', '200.00')
                ->etc());
    }

    public function test_returns_a_clean_error_when_market_data_is_unavailable(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response([
                'code' => 400,
                'message' => 'symbol parameter is missing or incorrect',
                'status' => 'error',
            ]),
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetAssetInformation::class, ['symbol' => 'INVALID'])
            ->assertHasErrors();
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetAssetInformation::class, ['symbol' => 'AAPL'])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
