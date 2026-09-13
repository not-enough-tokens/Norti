<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // createToken() (unlike Passport::actingAs()) goes through the real
        // OAuth flow, which needs a personal access client to exist.
        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Test Personal Access Client',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }

    public function test_issues_a_token_with_all_scopes_for_the_logged_in_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/mcp/token');

        $response->assertOk()->assertJsonStructure(['token', 'expires_at']);

        $token = $user->tokens()->where('name', 'mcp-session')->sole();
        $this->assertEqualsCanonicalizing(['mcp:read', 'mcp:simulate', 'mcp:write'], $token->scopes);
    }

    public function test_revokes_the_previous_mcp_session_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/mcp/token');
        $this->assertSame(1, $user->tokens()->where('name', 'mcp-session')->count());

        $this->actingAs($user)->postJson('/mcp/token');
        $this->assertSame(1, $user->tokens()->where('name', 'mcp-session')->count());
    }

    public function test_requires_an_authenticated_session(): void
    {
        $response = $this->postJson('/mcp/token');

        $response->assertUnauthorized();
    }
}
