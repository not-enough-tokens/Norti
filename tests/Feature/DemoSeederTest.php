<?php

namespace Tests\Feature;

use App\Models\EducationalTopic;
use App\Models\Holding;
use App\Models\User;
use App\Services\FinancialEducationService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_a_demo_user_with_profile_portfolio_and_partial_education_progress(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', 'demo@banorte.local')->sole();

        $this->assertNotNull($user->financialProfile);
        $this->assertSame(3, $user->portfolios->first()->holdings()->count());
        $this->assertGreaterThan(0, EducationalTopic::count());
        $this->assertTrue($user->educationalTopics()->wherePivotNotNull('completed_at')->exists());

        // Deliberadamente sin metas -- ver el docblock de DemoSeeder.
        $this->assertSame(0, $user->financialGoals()->count());
    }

    public function test_demo_user_gets_the_no_goals_recommendation(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', 'demo@banorte.local')->sole();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('no_goals', $recommended->recommended_reason);
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(1, User::where('email', 'demo@banorte.local')->count());

        $topicCountAfterOnce = EducationalTopic::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($topicCountAfterOnce, EducationalTopic::count());
        $this->assertSame(3, Holding::count());
    }
}
