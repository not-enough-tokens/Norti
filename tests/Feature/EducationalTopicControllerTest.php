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
}
