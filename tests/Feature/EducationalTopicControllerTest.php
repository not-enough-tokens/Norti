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

    public function test_authenticated_user_sees_their_own_context_not_someone_elses(): void
    {
        $otherUser = User::factory()->create();
        FinancialProfile::factory()->for($otherUser)->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/education');

        $response->assertOk();
        $response->assertViewHas('financialContext', function (array $context) {
            return $context['profile'] === null
                && $context['goals']->isEmpty()
                && $context['portfolios']->isEmpty();
        });
    }
}
