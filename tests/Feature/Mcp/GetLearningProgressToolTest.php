<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetLearningProgress;
use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetLearningProgressToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarizes_completed_and_pending_topics(): void
    {
        $completed = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        EducationalTopic::create([
            'title' => 'Inversión',
            'slug' => 'inversion',
            'description' => 'Cómo invertir',
            'content' => 'Contenido',
            'category' => 'investing',
            'difficulty' => 'intermediate',
            'estimated_minutes' => 10,
        ]);

        $user = User::factory()->create();
        $user->educationalTopics()->attach($completed, ['completed_at' => now()]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetLearningProgress::class)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'learning_progress')
                ->where('props.total_topics', 2)
                ->where('props.completed_topics', 1)
                ->where('props.pending_topics', 1)
                ->where('props.completion_percentage', 50)
                ->where('props.category_gaps', ['investing'])
                ->where('props.actions', [
                    [
                        'id' => 'continue_learning',
                        'label' => 'Continuar con el siguiente tema',
                        'tool' => 'get_learning_path',
                        'params' => [],
                    ],
                ])
                ->etc());
    }

    public function test_reports_zero_percent_when_there_are_no_topics(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetLearningProgress::class)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.total_topics', 0)
                ->where('props.completion_percentage', 0)
                ->where('props.category_gaps', [])
                ->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetLearningProgress::class)
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
