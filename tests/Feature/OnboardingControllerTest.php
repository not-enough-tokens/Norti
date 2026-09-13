<?php

namespace Tests\Feature;

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
}
