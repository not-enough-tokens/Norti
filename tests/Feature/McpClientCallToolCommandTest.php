<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpClientCallToolCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_there_are_no_users(): void
    {
        $this->artisan('mcp:client-call', ['tool' => 'get_market_snapshot'])
            ->expectsOutputToContain('No hay usuarios en la base de datos')
            ->assertExitCode(1);
    }

    public function test_fails_when_arguments_is_not_valid_json(): void
    {
        User::factory()->create();

        $this->artisan('mcp:client-call', [
            'tool' => 'get_market_snapshot',
            '--arguments' => '{not json',
        ])
            ->expectsOutputToContain('no es JSON válido')
            ->assertExitCode(1);
    }

    public function test_fails_when_arguments_decodes_to_a_non_array(): void
    {
        User::factory()->create();

        $this->artisan('mcp:client-call', [
            'tool' => 'get_market_snapshot',
            '--arguments' => '"just a string"',
        ])
            ->expectsOutputToContain('debe decodificar a un objeto JSON')
            ->assertExitCode(1);
    }
}
