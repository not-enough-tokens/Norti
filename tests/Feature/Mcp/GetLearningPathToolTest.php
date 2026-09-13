<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetLearningPath;
use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetLearningPathToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_every_topic_with_its_completion_status_for_the_authenticated_user(): void
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

        $pending = EducationalTopic::create([
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

        BanorteServer::tool(GetLearningPath::class)
            ->assertOk()
            ->assertStructuredContent([
                'component' => 'learning_path',
                'props' => [
                    'topics' => [
                        [
                            'id' => $completed->id,
                            'title' => 'Ahorro',
                            'category' => 'personal_finance',
                            'estimated_minutes' => 5,
                            'is_completed' => true,
                            'actions' => [
                                [
                                    'id' => "view_topic_{$completed->id}",
                                    'label' => 'Ver tema',
                                    'tool' => 'get_educational_topic',
                                    'params' => ['topic_id' => $completed->id],
                                ],
                            ],
                        ],
                        [
                            'id' => $pending->id,
                            'title' => 'Inversión',
                            'category' => 'investing',
                            'estimated_minutes' => 10,
                            'is_completed' => false,
                            'actions' => [
                                [
                                    'id' => "view_topic_{$pending->id}",
                                    'label' => 'Ver tema',
                                    'tool' => 'get_educational_topic',
                                    'params' => ['topic_id' => $pending->id],
                                ],
                            ],
                        ],
                    ],
                    'recommended_topic' => [
                        'id' => $pending->id,
                        'title' => 'Inversión',
                        'recommended_reason' => 'default',
                    ],
                    'actions' => [
                        [
                            'id' => 'view_progress',
                            'label' => 'Ver mi progreso',
                            'tool' => 'get_learning_progress',
                            'params' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_progress_is_scoped_to_the_authenticated_user(): void
    {
        $topic = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        $otherUser = User::factory()->create();
        $otherUser->educationalTopics()->attach($topic, ['completed_at' => now()]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetLearningPath::class)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.topics.0.is_completed', false)->etc());
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetLearningPath::class)
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
