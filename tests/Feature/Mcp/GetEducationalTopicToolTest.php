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
            ->assertStructuredContent(fn ($json) => $json->where('component', 'educational_topic')
                ->where('props.id', $topic->id)
                ->where('props.title', 'Ahorro')
                ->where('props.actions', [
                    [
                        'id' => 'mark_completed',
                        'label' => 'Marcar como completado',
                        'tool' => 'mark_topic_completed',
                        'params' => ['topic_id' => $topic->id],
                    ],
                ])
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
