<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpDebugAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_the_latest_audit_log_entries(): void
    {
        $user = User::factory()->create();

        AuditLog::create([
            'user_id' => $user->id,
            'tool_name' => 'simulate_investment',
            'input' => ['months' => 12, 'risk_profile' => 'moderate'],
            'result_summary' => 'ok',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/mcp-test/audit-logs');

        $response->assertOk()->assertJsonFragment(['tool_name' => 'simulate_investment']);
    }
}
