<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\SimulateInvestment;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SimulateInvestmentToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_compound_interest_projection(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'moderate',
        ])
            ->assertOk()
            ->assertStructuredContent([
                'component' => 'simulation_result_card',
                'props' => [
                    'initial_amount' => 1000,
                    'months' => 12,
                    'risk_profile' => 'moderate',
                    'assumed_annual_rate' => 0.07,
                    'projected_value' => 1072.29,
                    'projected_gain' => 72.29,
                    'disclaimer' => 'Proyección aritmética simplificada (interés compuesto mensual a tasa fija). No considera volatilidad de mercado ni constituye asesoría financiera.',
                ],
            ]);
    }

    public function test_never_writes_the_amount_to_the_audit_log(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 999999,
            'months' => 6,
            'risk_profile' => 'aggressive',
        ])->assertOk();

        $log = AuditLog::where('tool_name', 'simulate_investment')->firstOrFail();

        $this->assertSame(['months' => 6, 'risk_profile' => 'aggressive'], $log->input);
    }

    public function test_rejects_invalid_risk_profile(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'yolo',
        ])->assertHasErrors();
    }

    public function test_rejects_without_the_mcp_simulate_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'moderate',
        ])->assertHasErrors(['No autorizado: se requiere el scope mcp:simulate.']);
    }
}
