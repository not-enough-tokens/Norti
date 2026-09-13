<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpAgentDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_anthropic_api_key_is_missing(): void
    {
        config(['services.anthropic.key' => null]);

        $this->artisan('mcp:demo-agent')
            ->expectsOutputToContain('Falta ANTHROPIC_API_KEY')
            ->assertExitCode(1);
    }

    public function test_fails_when_there_are_no_users(): void
    {
        config(['services.anthropic.key' => 'test-key']);

        $this->artisan('mcp:demo-agent')
            ->expectsOutputToContain('No hay usuarios en la base de datos')
            ->assertExitCode(1);
    }

    public function test_fails_when_the_requested_user_email_does_not_exist(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('mcp:demo-agent', ['--user' => 'missing@example.com'])
            ->expectsOutputToContain('No hay usuarios en la base de datos')
            ->assertExitCode(1);
    }
}
