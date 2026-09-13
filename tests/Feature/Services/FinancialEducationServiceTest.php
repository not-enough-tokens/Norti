<?php

namespace Tests\Feature\Services;

use App\Models\Asset;
use App\Models\EducationalTopic;
use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\FinancialEducationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEducationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedTopics(): void
    {
        EducationalTopic::create([
            'title' => 'Ahorro vs inversión',
            'slug' => 'ahorro-vs-inversion',
            'description' => '...',
            'content' => '...',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Diversificación',
            'slug' => 'diversificacion',
            'description' => '...',
            'content' => '...',
            'category' => 'risk',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Riesgo de inversión',
            'slug' => 'riesgo-de-inversion',
            'description' => '...',
            'content' => '...',
            'category' => 'risk',
            'difficulty' => 'beginner',
            'estimated_minutes' => 7,
        ]);

        EducationalTopic::create([
            'title' => 'Interés compuesto',
            'slug' => 'interes-compuesto',
            'description' => '...',
            'content' => '...',
            'category' => 'basics',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);
    }

    public function test_recommends_nothing_when_every_topic_is_already_completed(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        $user->educationalTopics()->attach(EducationalTopic::pluck('id'), ['completed_at' => now()]);

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertNull($recommended);
    }

    public function test_recommends_ahorro_vs_inversion_when_the_user_has_no_goals(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('ahorro-vs-inversion', $recommended->slug);
        $this->assertSame('no_goals', $recommended->recommended_reason);
    }

    public function test_recommends_diversificacion_when_the_portfolio_holds_a_single_asset(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        FinancialGoal::factory()->for($user)->create();

        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create(['asset_type' => 'bono']);
        Holding::factory()->for($portfolio)->for($asset)->create();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('diversificacion', $recommended->slug);
        $this->assertSame('concentrated_portfolio', $recommended->recommended_reason);
    }

    public function test_recommends_riesgo_de_inversion_for_a_conservative_profile_holding_stocks(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        FinancialGoal::factory()->for($user)->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'conservative']);

        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['asset_type' => 'accion']);
        $bond = Asset::factory()->create(['asset_type' => 'bono']);
        Holding::factory()->for($portfolio)->for($stock)->create();
        Holding::factory()->for($portfolio)->for($bond)->create();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('riesgo-de-inversion', $recommended->slug);
        $this->assertSame('conservative_profile_with_stocks', $recommended->recommended_reason);
    }

    public function test_falls_back_to_the_first_incomplete_topic_when_no_rule_applies(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        FinancialGoal::factory()->for($user)->create();
        FinancialProfile::factory()->for($user)->create(['risk_tolerance' => 'aggressive']);

        $portfolio = Portfolio::factory()->for($user)->create();
        $stock = Asset::factory()->create(['asset_type' => 'accion']);
        $bond = Asset::factory()->create(['asset_type' => 'bono']);
        Holding::factory()->for($portfolio)->for($stock)->create();
        Holding::factory()->for($portfolio)->for($bond)->create();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('ahorro-vs-inversion', $recommended->slug);
        $this->assertSame('default', $recommended->recommended_reason);
    }

    public function test_no_goals_rule_takes_priority_over_diversification_rule(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();
        Holding::factory()->for($portfolio)->for($asset)->create();

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertSame('ahorro-vs-inversion', $recommended->slug);
    }

    public function test_skips_a_matching_rule_when_its_topic_is_already_completed(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        $user->educationalTopics()->attach(
            EducationalTopic::where('slug', 'ahorro-vs-inversion')->sole(),
            ['completed_at' => now()]
        );

        $recommended = app(FinancialEducationService::class)->getRecommendedTopic($user);

        $this->assertNotSame('ahorro-vs-inversion', $recommended->slug);
    }

    public function test_category_gaps_lists_every_category_when_nothing_is_completed(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();

        $gaps = app(FinancialEducationService::class)->getCategoryGaps($user);

        $this->assertEqualsCanonicalizing(['personal_finance', 'risk', 'basics'], $gaps);
    }

    public function test_category_gaps_excludes_a_category_with_at_least_one_completed_topic(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        $user->educationalTopics()->attach(
            EducationalTopic::where('slug', 'diversificacion')->sole(),
            ['completed_at' => now()]
        );

        $gaps = app(FinancialEducationService::class)->getCategoryGaps($user);

        // 'riesgo-de-inversion' is also 'risk' but is not completed -- one
        // completed topic in the category is enough to close the gap.
        $this->assertEqualsCanonicalizing(['personal_finance', 'basics'], $gaps);
    }

    public function test_category_gaps_is_empty_when_everything_is_completed(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        $user->educationalTopics()->attach(EducationalTopic::pluck('id'), ['completed_at' => now()]);

        $gaps = app(FinancialEducationService::class)->getCategoryGaps($user);

        $this->assertSame([], $gaps);
    }

    public function test_progress_summary_reports_counts_and_percentage(): void
    {
        $this->seedTopics();

        $user = User::factory()->create();
        $user->educationalTopics()->attach(
            EducationalTopic::where('slug', 'ahorro-vs-inversion')->sole(),
            ['completed_at' => now()]
        );

        $summary = app(FinancialEducationService::class)->getProgressSummary($user);

        $this->assertSame([
            'total_topics' => 4,
            'completed_topics' => 1,
            'pending_topics' => 3,
            'completion_percentage' => 25,
        ], $summary);
    }

    public function test_progress_summary_reports_zero_percent_with_no_topics(): void
    {
        $user = User::factory()->create();

        $summary = app(FinancialEducationService::class)->getProgressSummary($user);

        $this->assertSame([
            'total_topics' => 0,
            'completed_topics' => 0,
            'pending_topics' => 0,
            'completion_percentage' => 0,
        ], $summary);
    }
}
