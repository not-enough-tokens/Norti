<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetFinancialGoals;
use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetFinancialGoalsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_summary_without_exact_amounts_by_default(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create([
            'risk_tolerance' => 'moderate',
            'monthly_income' => 30_000,
            'monthly_expenses' => 20_000,
        ]);

        FinancialGoal::factory()->for($user)->create([
            'name' => 'Enganche casa',
            'target_amount' => 500_000,
            'current_amount' => 50_000,
            'target_day' => now()->addYears(2)->toDateString(),
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialGoals::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'financial_goals_list')
                ->where('props.detail', 'summary')
                ->where('props.goals.0.name', 'Enganche casa')
                ->where('props.goals.0.progress_percentage', 10)
                ->has('props.goals.0.months_remaining')
                ->has('props.goals.0.reaches_goal')
                ->missing('props.goals.0.target_amount')
                ->missing('props.goals.0.current_amount')
                ->etc());
    }

    public function test_returns_exact_amounts_only_when_explicitly_requested(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create();

        FinancialGoal::factory()->for($user)->create([
            'name' => 'Fondo de emergencia',
            'target_amount' => 100_000,
            'current_amount' => 25_000,
            'target_day' => now()->addYear()->toDateString(),
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialGoals::class, ['detail' => 'exact'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.detail', 'exact')
                ->where('props.goals.0.target_amount', 100_000)
                ->where('props.goals.0.current_amount', 25_000)
                ->has('props.goals.0.shortfall')
                ->has('props.goals.0.target_day')
                ->etc());
    }

    public function test_returns_an_empty_list_when_the_user_has_no_goals(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialGoals::class, [])
            ->assertOk()
            ->assertStructuredContent(['component' => 'financial_goals_list', 'props' => ['detail' => 'summary', 'goals' => []]]);
    }

    public function test_goals_are_scoped_to_the_authenticated_user(): void
    {
        $otherUser = User::factory()->create();
        FinancialGoal::factory()->for($otherUser)->create();

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialGoals::class, [])
            ->assertOk()
            ->assertStructuredContent(['component' => 'financial_goals_list', 'props' => ['detail' => 'summary', 'goals' => []]]);
    }

    /**
     * Sin FinancialProfile no hay tasa de rendimiento que proyectar --
     * projectForGoal()/evaluateGoal() (M2) requieren uno. La tool debe seguir
     * respondiendo con lo que sí puede calcular, sin tronar.
     */
    public function test_returns_only_progress_when_the_user_has_no_financial_profile(): void
    {
        $user = User::factory()->create();

        FinancialGoal::factory()->for($user)->create([
            'target_amount' => 100_000,
            'current_amount' => 25_000,
        ]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetFinancialGoals::class, [])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.goals.0.progress_percentage', 25)
                ->missing('props.goals.0.months_remaining')
                ->missing('props.goals.0.reaches_goal')
                ->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetFinancialGoals::class, [])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
