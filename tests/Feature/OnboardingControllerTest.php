<?php

namespace Tests\Feature;

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

    public function test_chat_greets_the_user_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Ana López']);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertSee('Hola Ana López, ¿en qué te puede ayudar Norti el día de hoy?');
    }

    public function test_full_conversation_creates_a_financial_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/onboarding', ['message' => 'Quiero hacer crecer mis ahorros'])
            ->assertRedirect(route('onboarding.index'));

        $this->post('/onboarding', ['message' => '30,000 pesos'])
            ->assertRedirect(route('onboarding.index'));

        $this->post('/onboarding', ['message' => '50,000 pesos'])
            ->assertRedirect(route('onboarding.index'));

        $this->post('/onboarding', ['message' => '2 años'])
            ->assertRedirect(route('onboarding.index'));

        $response = $this->post('/onboarding', ['message' => 'moderado, no me gusta arriesgar mucho']);
        $response->assertRedirect(route('onboarding.index'));

        $this->assertDatabaseHas('financial_profiles', [
            'user_id' => $user->id,
            'monthly_income' => 30000,
            'savings' => 50000,
            'investment_horizon_months' => 24,
        ]);

        $profile = FinancialProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('moderado, no me gusta arriesgar mucho', $profile->risk_tolerance);

        $final = $this->get('/onboarding');
        $final->assertSee('¡Listo!', false);
        $final->assertSee(route('education.index'), false);
    }

    public function test_conversation_ignores_extra_messages_once_done(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['ingreso pasivo', '10000', '5000', '1 año', 'conservador'] as $message) {
            $this->post('/onboarding', ['message' => $message]);
        }

        $countBefore = FinancialProfile::count();

        $this->post('/onboarding', ['message' => 'otra pregunta que no debería reiniciar nada'])
            ->assertRedirect(route('onboarding.index'));

        $this->assertSame($countBefore, FinancialProfile::count());
    }
}
