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

    /**
     * Los /mcp-test/* están pensados solo para local/testing -- antes esto se
     * garantizaba envolviendo las rutas en `if (app()->environment(...))` en
     * routes/web.php, lo cual nunca se probó (las rutas se registran una vez
     * al boot, cambiar el entorno a mitad de un test no las quita). Ahora es
     * EnsureDebugRoutesAreAllowed, un middleware que sí corre por request.
     */
    public function test_the_debug_routes_are_unreachable_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->getJson('/mcp-test/audit-logs')->assertNotFound();
        $this->getJson('/mcp-test')->assertNotFound();
        $this->getJson('/mcp-test/seed')->assertNotFound();
    }
}
