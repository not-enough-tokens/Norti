<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class McpRateLimitConfigTest extends TestCase
{
    public function test_the_mcp_limiter_reads_its_max_attempts_from_config(): void
    {
        config(['mcp.rate_limit_per_minute' => 5]);

        $limit = RateLimiter::limiter('mcp')(Request::create('/mcp/banorte'));

        $this->assertSame(5, $limit->maxAttempts);
    }
}
