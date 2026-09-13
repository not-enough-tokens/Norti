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
            ->assertStructuredContent(fn ($json) => $json->where('component', 'simulation_result_card')
                ->where('props.initial_amount', 1000)
                ->where('props.months', 12)
                ->where('props.risk_profile', 'moderate')
                ->where('props.assumed_annual_rate', 0.09)
                ->where('props.projected_value', 1093.81)
                ->where('props.projected_gain', 93.81)
                ->where('props.disclaimer', 'Proyección aritmética simplificada (interés compuesto mensual a tasa fija). No considera volatilidad de mercado ni constituye asesoría financiera.')
                ->etc());
    }

    /**
     * Option Chip (plazo) y Option Row (perfil) del contrato A2UI: el plazo y
     * el perfil actuales nunca se repiten como acción -- ya son el resultado
     * que se está mostrando.
     */
    public function test_offers_resimulate_actions_excluding_the_current_term_and_profile(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'moderate',
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'simulation_result_card')
                ->where('props.initial_amount', 1000)
                ->where('props.months', 12)
                ->where('props.risk_profile', 'moderate')
                ->where('props.assumed_annual_rate', 0.09)
                ->where('props.projected_value', 1093.81)
                ->where('props.projected_gain', 93.81)
                ->where('props.disclaimer', 'Proyección aritmética simplificada (interés compuesto mensual a tasa fija). No considera volatilidad de mercado ni constituye asesoría financiera.')
                ->where('props.actions', [
                    [
                        'id' => 'resimulate_36_months',
                        'label' => 'Simular a 36 meses',
                        'tool' => 'simulate_investment',
                        'params' => ['amount' => 1000, 'months' => 36, 'risk_profile' => 'moderate'],
                    ],
                    [
                        'id' => 'resimulate_60_months',
                        'label' => 'Simular a 60 meses',
                        'tool' => 'simulate_investment',
                        'params' => ['amount' => 1000, 'months' => 60, 'risk_profile' => 'moderate'],
                    ],
                    [
                        'id' => 'resimulate_conservative',
                        'label' => 'Perfil conservador',
                        'tool' => 'simulate_investment',
                        'params' => ['amount' => 1000, 'months' => 12, 'risk_profile' => 'conservative'],
                    ],
                    [
                        'id' => 'resimulate_aggressive',
                        'label' => 'Perfil agresivo',
                        'tool' => 'simulate_investment',
                        'params' => ['amount' => 1000, 'months' => 12, 'risk_profile' => 'aggressive'],
                    ],
                ])
                ->etc());
    }

    /**
     * A2UI contract gap 14: project() ya calculaba la serie mes a mes, pero el
     * adapter solo usaba el último valor. Máximo 10 periodos (regla de
     * gráficas) para una simulación de 12 meses.
     */
    public function test_includes_a_stacked_column_chart_with_principal_and_gain_per_period(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'moderate',
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.chart.type', 'stacked_column')
                ->where('props.chart.unit', 'currency')
                ->where('props.chart.series', [
                    ['key' => 'principal', 'role' => 'muted'],
                    ['key' => 'gain', 'role' => 'positive'],
                ])
                ->where('props.chart.data.9.key', 12)
                ->where('props.chart.data.9.total', 1093.81)
                ->where('props.chart.data.9.values.principal', 1000)
                ->where('props.chart.data.9.values.gain', 93.81)
                ->etc());
    }

    /**
     * Un plazo corto (≤ 10 meses) no necesita agrupar: cada mes es su propio
     * periodo.
     */
    public function test_stacked_column_chart_keeps_every_month_for_a_short_term(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 6,
            'risk_profile' => 'moderate',
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->has('props.chart.data', 6)
                ->where('props.chart.data.0.key', 1)
                ->where('props.chart.data.5.key', 6)
                ->etc());
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

    public function test_audits_a_validation_failure(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 12,
            'risk_profile' => 'yolo',
        ])->assertHasErrors();

        $log = AuditLog::where('tool_name', 'simulate_investment')
            ->where('result_summary', 'validation_failed')
            ->firstOrFail();

        $this->assertSame(['risk_profile'], $log->input['failed_fields']);
    }

    public function test_rejects_months_over_the_50_year_cap(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:simulate']);

        BanorteServer::tool(SimulateInvestment::class, [
            'amount' => 1000,
            'months' => 601,
            'risk_profile' => 'moderate',
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
