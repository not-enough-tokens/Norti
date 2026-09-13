<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpRateLimitConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mcp_limiter_reads_its_max_attempts_from_config(): void
    {
        config(['mcp.rate_limit_per_minute' => 5]);

        $limit = RateLimiter::limiter('mcp')(Request::create('/mcp/banorte'));

        $this->assertSame(5, $limit->maxAttempts);
    }

    /**
     * El test de arriba solo prueba que el closure del limiter lee la config
     * correcta -- nunca golpea /mcp/banorte de verdad, así que nunca probó
     * que `throttle:mcp` en routes/ai.php realmente devuelva 429. Este sí.
     */
    public function test_the_mcp_endpoint_returns_429_once_the_configured_limit_is_exceeded(): void
    {
        config(['mcp.rate_limit_per_minute' => 2]);

        Passport::actingAs(User::factory()->create(), ['mcp:read']);

        $initialize = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ];

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/mcp/banorte', $initialize)->assertSuccessful();
        }

        $this->postJson('/mcp/banorte', $initialize)->assertStatus(429);
    }
}
