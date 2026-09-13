<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/chat');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_an_empty_conversation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/chat');

        $response->assertOk();
        $response->assertViewHas('messages', []);
        $response->assertSee('Hola, soy Norti', false);
        $response->assertSee('aria-label="Navegación principal"', false);
    }

    public function test_guests_cannot_send_a_message(): void
    {
        $response = $this->post('/chat', ['message' => 'hola']);

        $response->assertRedirect(route('login'));
    }

    /**
     * send() invoca a BanorteMcpAgent de verdad (OpenAI + /mcp/banorte real
     * vía HTTP) -- no hay forma de simularlo en un test de request sin un
     * doble de todo el SDK de IA y del cliente MCP, así que, igual que
     * McpAgentDemo (el comando artisan equivalente), no se prueba ese
     * camino aquí. Verificado manualmente end-to-end contra la app real
     * (ver PR #43).
     */
    public function test_rejects_an_empty_message_without_calling_the_agent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/chat', ['message' => '']);

        $response->assertSessionHasErrors('message');
        $this->assertNull($this->app['session']->get('chat_conversation'));
    }

    public function test_rejects_a_message_over_the_length_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/chat', ['message' => str_repeat('a', 1001)]);

        $response->assertSessionHasErrors('message');
    }
}
