<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\GetEducationalTopic;
use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GetEducationalTopicToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_requested_topic(): void
    {
        $topic = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido de ahorro',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetEducationalTopic::class, ['topic_id' => $topic->id])
            ->assertOk()
            ->assertStructuredContent([
                'component' => 'educational_topic',
                'props' => [
                    'id' => $topic->id,
                    'title' => 'Ahorro',
                    'slug' => 'ahorro',
                    'description' => 'Cómo ahorrar',
                    'content' => 'Contenido de ahorro',
                    'category' => 'personal_finance',
                    'difficulty' => 'beginner',
                    'estimated_minutes' => 5,
                    'is_completed' => false,
                    'actions' => [
                        [
                            'id' => 'mark_completed',
                            'label' => 'Marcar como completado',
                            'tool' => 'mark_topic_completed',
                            'params' => ['topic_id' => $topic->id],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Gap 11: sin is_completed, "Marcar como completado" se ofrecía incluso
     * para un tema que el usuario ya completó.
     */
    public function test_omits_the_mark_completed_action_when_already_completed(): void
    {
        $topic = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido de ahorro',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        $user = User::factory()->create();
        $user->educationalTopics()->attach($topic, ['completed_at' => now()]);

        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetEducationalTopic::class, ['topic_id' => $topic->id])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('props.is_completed', true)
                ->where('props.actions', [])
                ->etc());
    }

    public function test_rejects_a_nonexistent_topic_id(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(GetEducationalTopic::class, ['topic_id' => 999])
            ->assertHasErrors();
    }

    public function test_rejects_without_the_mcp_read_scope(): void
    {
        $topic = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido de ahorro',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, []);

        BanorteServer::tool(GetEducationalTopic::class, ['topic_id' => $topic->id])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:read.']);
    }
}
