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
}
