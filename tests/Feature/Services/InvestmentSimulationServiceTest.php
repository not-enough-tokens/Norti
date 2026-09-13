<?php

namespace Tests\Feature\Services;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\User;
use App\Services\InvestmentSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InvestmentSimulationServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvestmentSimulationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvestmentSimulationService::class);
    }

    /**
     * @return array{0: FinancialGoal, 1: FinancialProfile}
     */
    private function goalAndProfile(array $goalAttributes = []): array
    {
        $user = User::factory()->create();

        $profile = FinancialProfile::factory()->for($user)->create([
            'risk_tolerance' => 'moderate',
            'monthly_income' => 30_000,
            'monthly_expenses' => 20_000,
        ]);

        $goal = FinancialGoal::factory()->for($user)->create($goalAttributes + [
            'target_amount' => 500_000,
            'current_amount' => 10_000,
        ]);

        return [$goal, $profile];
    }

    public function test_projects_month_by_month_until_the_target_day(): void
    {
        [$goal, $profile] = $this->goalAndProfile(['target_day' => now()->addYears(2)->toDateString()]);

        $result = $this->service->projectForGoal($goal, $profile);

        $this->assertFalse($result['is_overdue']);
        $this->assertSame(24, $result['months']);
        $this->assertCount(24, $result['projection']);
        $this->assertSame(10_000.0, $result['monthly_contribution']);
        $this->assertGreaterThan(10_000, $result['final_amount']);
    }

    /**
     * max(1, ...) convertía una meta vencida en una proyección de un mes hacia
     * el futuro, indistinguible de una meta vigente.
     */
    public function test_an_overdue_goal_is_flagged_instead_of_projected(): void
    {
        [$goal, $profile] = $this->goalAndProfile(['target_day' => now()->subYears(2)->toDateString()]);

        $result = $this->service->projectForGoal($goal, $profile);

        $this->assertTrue($result['is_overdue']);
        $this->assertSame(0, $result['months']);
        $this->assertSame([], $result['projection']);
        $this->assertSame(10_000.0, $result['final_amount'], 'No debe proyectar crecimiento sin tiempo restante.');
    }

    public function test_a_goal_due_today_is_treated_as_overdue(): void
    {
        [$goal, $profile] = $this->goalAndProfile(['target_day' => now()->toDateString()]);

        $result = $this->service->projectForGoal($goal, $profile);

        $this->assertTrue($result['is_overdue']);
        $this->assertSame(0, $result['months']);
    }

    public function test_evaluate_goal_propagates_the_overdue_flag(): void
    {
        [$goal, $profile] = $this->goalAndProfile(['target_day' => now()->subYear()->toDateString()]);

        $evaluation = $this->service->evaluateGoal($goal, $this->service->projectForGoal($goal, $profile));

        $this->assertFalse($evaluation['reaches_goal']);
        $this->assertTrue($evaluation['is_overdue']);
        $this->assertSame(490_000.0, $evaluation['shortfall']);
    }

    public function test_evaluate_goal_reports_no_shortfall_when_reached(): void
    {
        [$goal, $profile] = $this->goalAndProfile([
            'target_amount' => 5_000,
            'target_day' => now()->addYear()->toDateString(),
        ]);

        $evaluation = $this->service->evaluateGoal($goal, $this->service->projectForGoal($goal, $profile));

        $this->assertTrue($evaluation['reaches_goal']);
        $this->assertSame(0.0, $evaluation['shortfall']);
        $this->assertFalse($evaluation['is_overdue']);
    }

    /**
     * Los dos argumentos son independientes, así que nada impedía proyectar la
     * meta de un usuario con el perfil financiero de otro.
     */
    public function test_rejects_a_goal_and_profile_from_different_users(): void
    {
        [$goal] = $this->goalAndProfile(['target_day' => now()->addYear()->toDateString()]);
        $otherProfile = FinancialProfile::factory()->for(User::factory())->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->projectForGoal($goal, $otherProfile);
    }
}
