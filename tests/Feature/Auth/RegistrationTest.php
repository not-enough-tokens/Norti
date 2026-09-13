<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_new_users_can_register_and_are_sent_back_to_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'Felix Test',
            'email' => 'felix@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['name' => 'Felix Test', 'email' => 'felix@example.com']);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $response->assertSessionHasInput('email', 'felix@example.com');
    }

    public function test_registered_users_can_log_in_with_their_new_credentials(): void
    {
        $this->post('/register', [
            'name' => 'Felix Test',
            'email' => 'felix@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'felix@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.index'));
    }

    public function test_registration_requires_a_unique_email(): void
    {
        $existing = User::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Otro',
            'email' => $existing->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'Ya existe una cuenta con este correo.']);
        $this->assertGuest();
    }

    /**
     * "Ana@x.com" y "ana@x.com" no deben poder registrarse como dos cuentas
     * distintas -- ver migración users_email_lower_unique.
     */
    public function test_registration_treats_email_as_case_insensitive_for_uniqueness(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $response = $this->post('/register', [
            'name' => 'Otra Ana',
            'email' => 'Ana@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'Ya existe una cuenta con este correo.']);
        $this->assertGuest();
    }

    public function test_registration_stores_the_email_in_lowercase(): void
    {
        $this->post('/register', [
            'name' => 'Ana López',
            'email' => 'Ana.Lopez@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'ana.lopez@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'Ana.Lopez@Example.COM']);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Felix Test',
            'email' => 'felix@example.com',
            'password' => 'password123',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors(['password' => 'Las contraseñas no coinciden.']);
        $this->assertGuest();
    }

    public function test_registration_requires_a_password_of_at_least_8_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'Felix Test',
            'email' => 'felix@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        $this->assertDatabaseMissing('users', ['email' => 'felix@example.com']);
    }
}
