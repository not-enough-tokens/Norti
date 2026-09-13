<?php

namespace Tests\Feature;

use App\Models\EducationalTopic;
use App\Models\FinancialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/onboarding');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_onboarding(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertOk();
        $response->assertViewHas('user', fn (User $viewUser) => $viewUser->is($user));
    }

    public function test_welcome_screen_confirms_the_login_right_after_signing_in(): void
    {
        $user = User::factory()->create(['name' => 'Ana López']);

        $response = $this->actingAs($user)
            ->withSession(['status' => 'Sesión iniciada de forma correcta'])
            ->get('/onboarding');

        $response->assertOk();
        $response->assertSee('Sesión iniciada de forma correcta');
        $response->assertSee('Ana López');
    }

    /**
     * El chat abre preguntando de una vez -- no espera un mensaje libre del
     * usuario antes de arrancar las preguntas fijas (confuso: invitaba a
     * escribir cualquier cosa y luego lo ignoraba).
     */
    public function test_chat_opens_with_the_first_fixed_question(): void
    {
        $user = User::factory()->create(['name' => 'Ana López']);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertSee('Hola Ana López, soy Norti.', false);
        $response->assertSee('¿Cuánto es lo que ganas al mes?');
    }

    public function test_full_conversation_creates_a_financial_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/onboarding', ['message' => '30,000 pesos'])
            ->assertRedirect(route('onboarding.index'));

        $this->post('/onboarding', ['message' => '50,000 pesos'])
            ->assertRedirect(route('onboarding.index'));

        $this->post('/onboarding', ['message' => '2 años'])
            ->assertRedirect(route('onboarding.index'));

        $response = $this->post('/onboarding', ['message' => 'moderate']);
        $response->assertRedirect(route('onboarding.index'));

        $this->assertDatabaseHas('financial_profiles', [
            'user_id' => $user->id,
            'monthly_income' => 30000,
            'savings' => 50000,
            'investment_horizon_months' => 24,
            'risk_tolerance' => 'moderate',
        ]);

        $final = $this->get('/onboarding');
        $final->assertSee('¡Listo!', false);
        $final->assertSee('Moderado', false);
        $final->assertSee('Hablar con Norti');
        $final->assertSee('Ver educación');
    }

    /** La pregunta de riesgo se responde con las 3 opciones fijas (Option Chip), no texto libre. */
    public function test_the_risk_question_is_shown_as_three_fixed_options(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['30000', '50000', '2 años'] as $message) {
            $this->post('/onboarding', ['message' => $message]);
        }

        $response = $this->get('/onboarding');

        $response->assertSee('Conservador');
        $response->assertSee('Moderado');
        $response->assertSee('Agresivo');
    }

    public function test_conversation_ignores_extra_messages_once_done(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['10000', '5000', '1 año', 'conservative'] as $message) {
            $this->post('/onboarding', ['message' => $message]);
        }

        $countBefore = FinancialProfile::count();

        $this->post('/onboarding', ['message' => 'otra pregunta que no debería reiniciar nada'])
            ->assertRedirect(route('onboarding.index'));

        $this->assertSame($countBefore, FinancialProfile::count());
    }

    public function test_restarting_after_a_completed_profile_skips_the_fixed_questions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['10000', '5000', '1 año', 'conservative'] as $message) {
            $this->post('/onboarding', ['message' => $message]);
        }

        $this->post('/onboarding/restart')->assertRedirect(route('onboarding.index'));

        $response = $this->get('/onboarding');

        $response->assertDontSee('¿Cuánto es lo que ganas al mes?');
        $response->assertSee('¿En qué te puedo ayudar hoy?', false);
        $response->assertSee('Hablar con Norti');
    }

    public function test_returning_users_with_a_profile_skip_the_fixed_questions_on_a_fresh_session(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertDontSee('¿Cuánto es lo que ganas al mes?');
        $response->assertSee('Hablar con Norti');
    }

    /**
     * "Preguntar al asistente" desde un tema de Educación manda el contexto
     * del tema al chat -- sin repetir las preguntas si el perfil ya existe.
     */
    public function test_visiting_onboarding_with_a_topic_mentions_it_and_skips_the_questions_when_profile_exists(): void
    {
        $user = User::factory()->create();
        FinancialProfile::factory()->for($user)->create();
        $this->createTopic();

        $response = $this->actingAs($user)->get('/onboarding?topic=diversificacion');

        $response->assertSee('Diversificación', false);
        $response->assertDontSee('¿Cuánto es lo que ganas al mes?');
    }

    public function test_visiting_onboarding_with_a_topic_mentions_it_before_the_fixed_questions_when_no_profile_yet(): void
    {
        $user = User::factory()->create();
        $this->createTopic();

        $response = $this->actingAs($user)->get('/onboarding?topic=diversificacion');

        $response->assertSee('Diversificación', false);
        $response->assertSee('¿Cuánto es lo que ganas al mes?');
    }

    private function createTopic(): EducationalTopic
    {
        return EducationalTopic::create([
            'title' => 'Diversificación',
            'slug' => 'diversificacion',
            'description' => 'Descripción',
            'content' => 'Contenido',
            'category' => 'risk',
            'difficulty' => 'beginner',
            'estimated_minutes' => 5,
        ]);
    }
}
