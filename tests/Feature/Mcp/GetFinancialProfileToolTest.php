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
