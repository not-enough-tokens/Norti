<?php

namespace Tests\Feature\MCP;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetMarketQuote;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetMarketQuoteToolTest extends TestCase
{
    public function test_successful_quote_returns_the_normalized_payload(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response([
                'symbol' => 'AAPL',
                'name' => 'Apple Inc',
                'close' => '150.00',
            ]),
        ]);

        $response = BanorteServer::tool(GetMarketQuote::class, ['symbol' => 'AAPL']);

        $response->assertOk()->assertSee('AAPL');
    }

    public function test_missing_symbol_triggers_a_validation_error(): void
    {
        $response = BanorteServer::tool(GetMarketQuote::class, []);

        $response->assertHasErrors();
    }

    public function test_optional_filters_are_forwarded_to_twelvedata(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response(['symbol' => 'AAPL']),
        ]);

        BanorteServer::tool(GetMarketQuote::class, [
            'symbol' => 'AAPL',
            'exchange' => 'NASDAQ',
            'country' => 'United States',
            'type' => 'Common Stock',
        ]);

        Http::assertSent(fn ($request): bool => $request['symbol'] === 'AAPL'
            && $request['exchange'] === 'NASDAQ'
            && $request['country'] === 'United States'
            && $request['type'] === 'Common Stock');
    }

    public function test_twelvedata_error_is_translated_to_a_tool_error(): void
    {
        Http::fake([
            'api.twelvedata.com/quote*' => Http::response([
                'code' => 400,
                'message' => 'symbol parameter is missing or incorrect',
                'status' => 'error',
            ]),
        ]);

        $response = BanorteServer::tool(GetMarketQuote::class, ['symbol' => 'INVALID']);

        $response->assertHasErrors(['symbol parameter is missing or incorrect']);
    }
}
