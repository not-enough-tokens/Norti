<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\BanorteServer;
use App\Mcp\Tools\MarkTopicCompleted;
use App\Models\EducationalTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MarkTopicCompletedToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_the_topic_completed_for_the_authenticated_user(): void
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

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:write']);

        BanorteServer::tool(MarkTopicCompleted::class, ['topic_id' => $topic->id])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('component', 'learning_topic_completed')
                ->where('props.topic_id', $topic->id)
                ->where('props.completed', true)
                ->where('props.actions', [
                    [
                        'id' => 'view_progress',
                        'label' => 'Ver mi progreso',
                        'tool' => 'get_learning_progress',
                        'params' => [],
                    ],
                    [
                        'id' => 'next_topic',
                        'label' => 'Siguiente tema',
                        'tool' => 'get_learning_path',
                        'params' => [],
                    ],
                ])
                ->etc());

        $this->assertNotNull(
            $user->educationalTopics()->where('educational_topics.id', $topic->id)->first()?->pivot->completed_at
        );
    }

    public function test_is_idempotent_for_an_already_completed_topic(): void
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

        $user = User::factory()->create();
        $user->educationalTopics()->attach($topic, ['completed_at' => now()->subDay()]);

        Passport::actingAs($user, ['mcp:write']);

        BanorteServer::tool(MarkTopicCompleted::class, ['topic_id' => $topic->id])
            ->assertOk();

        $this->assertSame(1, $user->educationalTopics()->where('educational_topics.id', $topic->id)->count());
    }

    public function test_rejects_a_nonexistent_topic_id(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:write']);

        BanorteServer::tool(MarkTopicCompleted::class, ['topic_id' => 999])
            ->assertHasErrors();
    }

    public function test_rejects_without_the_mcp_write_scope(): void
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

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:read']);

        BanorteServer::tool(MarkTopicCompleted::class, ['topic_id' => $topic->id])
            ->assertHasErrors(['No autorizado: se requiere el scope mcp:write.']);
    }
}
