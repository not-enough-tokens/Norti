<?php

namespace Tests\Feature;

use App\Models\EducationalTopic;
use App\Models\FinancialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationalTopicControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_education_index(): void
    {
        $response = $this->get('/education');

        $response->assertRedirect('/login');
    }

    public function test_guests_cannot_view_a_topic(): void
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

        $response = $this->get("/education/{$topic->id}");

        $response->assertRedirect('/login');
    }

    /**
     * La vista nunca usó $financialContext, así que se dejó de construir: eran
     * tres queries desechadas y un FinancialProfile con montos exactos suelto
     * en el scope de la vista. Este test evita que se reintroduzca sin querer.
     */
    public function test_the_education_index_does_not_expose_financial_data(): void
    {
        $otherUser = User::factory()->create();
        FinancialProfile::factory()->for($otherUser)->create();

        $response = $this->actingAs(User::factory()->create())->get('/education');

        $response->assertOk();
        $response->assertViewMissing('financialContext');
    }

    /**
     * La ruta de aprendizaje sigue siendo información por usuario: el avance de
     * otra persona no debe aparecer como propio (el bug original de este
     * controlador era servirle a todos el contexto de User::first()).
     */
    public function test_the_learning_path_is_scoped_to_the_authenticated_user(): void
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

        $response = $this->actingAs($user)->get('/education');

        $response->assertOk();
        $response->assertViewHas(
            'learningPath',
            fn ($path) => $path->firstWhere('id', $topic->id)->is_completed === false
        );
    }

    public function test_authenticated_users_can_view_a_topic(): void
    {
        $topic = EducationalTopic::create([
            'title' => 'Ahorro',
            'slug' => 'ahorro',
            'description' => 'Cómo ahorrar',
            'content' => 'Contenido del tema',
            'category' => 'personal_finance',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);

        $response = $this->actingAs(User::factory()->create())->get("/education/{$topic->id}");

        $response->assertOk();
        $response->assertSee('Ahorro');
        $response->assertSee('Contenido del tema');
        $response->assertViewHas('isCompleted', false);
    }

    public function test_marking_a_topic_completed_flashes_status_and_updates_the_pivot(): void
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

        $response = $this->actingAs($user)->post("/education/{$topic->id}/complete");

        $response->assertRedirect(route('education.show', $topic));
        $response->assertSessionHas('status', 'Tema marcado como completado');
        $this->assertDatabaseHas('educational_topic_user', [
            'user_id' => $user->id,
            'educational_topic_id' => $topic->id,
        ]);
    }

    public function test_completing_a_topic_does_not_affect_other_users(): void
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
        $completer = User::factory()->create();
        $bystander = User::factory()->create();

        $this->actingAs($completer)->post("/education/{$topic->id}/complete");

        $this->assertDatabaseMissing('educational_topic_user', [
            'user_id' => $bystander->id,
            'educational_topic_id' => $topic->id,
        ]);
    }

    public function test_the_education_index_reports_progress_across_the_learning_path(): void
    {
        $topics = collect(range(1, 4))->map(fn (int $i) => EducationalTopic::create([
            'title' => "Tema {$i}",
            'slug' => "tema-{$i}",
            'description' => 'Descripción',
            'content' => 'Contenido',
            'category' => 'basics',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]));

        $user = User::factory()->create();
        $user->educationalTopics()->attach($topics->first(), ['completed_at' => now()]);

        $response = $this->actingAs($user)->get('/education');

        $response->assertOk();
        $response->assertViewHas('progress', [
            'total_topics' => 4,
            'completed_topics' => 1,
            'pending_topics' => 3,
            'completion_percentage' => 25,
        ]);
        $response->assertSee('25%');
    }
}
