<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetFinancialProfile;
use App\Models\FinancialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetFinancialProfileToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_summary_without_exact_amounts_by_default(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create([
            'monthly_income' => 20000,
            'monthly_expenses' => 15000,
            'risk_tolerance' => 'moderate',
            'investment_horizon_months' => 36,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialProfile::class, [])
            ->assertOk()
            ->assertStructuredContent([
                'component' => 'financial_profile_card',
                'props' => [
                    'has_profile' => true,
                    'detail' => 'summary',
                    'risk_tolerance' => 'moderate',
                    'investment_horizon_months' => 36,
                    'savings_rate_category' => 'high',
                    'actions' => [
                        [
                            'id' => 'simulate_with_my_profile',
                            'label' => 'Simular con mi perfil',
                            'tool' => 'simulate_investment',
                            'params' => ['risk_profile' => 'moderate'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_returns_exact_amounts_only_when_explicitly_requested(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create([
            'monthly_income' => 20000,
            'monthly_expenses' => 15000,
            'savings' => 5000,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialProfile::class, ['detail' => 'exact'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.detail', 'exact')
                ->where('props.monthly_income', 20000)
                ->where('props.monthly_expenses', 15000)
                ->where('props.savings', 5000)
                // Gap 18: ausente antes aunque ProfileService ya la calculaba.
                ->where('props.monthly_savings_capacity', 5000)
                ->etc());
    }

    /**
     * risk_tolerance es un string libre en la BD -- gap 4 pedía normalizarlo
     * antes de exponerlo, en vez de regresar lo que sea que haya en la
     * columna (aquí, con mayúscula).
     */
    public function test_normalizes_risk_tolerance_in_both_detail_levels(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'Moderate']);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialProfile::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.risk_tolerance', 'moderate')->etc());

        BanorteServer::tool(GetFinancialProfile::class, ['detail' => 'exact'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.risk_tolerance', 'moderate')->etc());
    }

    public function test_offers_no_actions_when_the_user_has_no_profile(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialProfile::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.has_profile', false)
                ->where('props.actions', [])
                ->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetFinancialProfile::class, [])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
