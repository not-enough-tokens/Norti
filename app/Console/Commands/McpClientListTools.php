<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Mcp\Client;
use Throwable;

/**
 * M5, vertical slice 1: verify our own application can act as an MCP client
 * against our own Banorte MCP server -- a pure protocol check (list_tools),
 * no LLM and no agent involved yet.
 *
 * Requires `php artisan serve` running: this speaks real HTTP to
 * /mcp/banorte via Laravel\Mcp\Client's HttpTransport, not the in-process
 * test kernel.
 */
#[Signature('mcp:client-tools
    {--user= : Email del usuario Passport a usar (default: el primero de la tabla users)}')]
#[Description('Conecta como cliente MCP a /mcp/banorte y lista las tools que expone, vía list_tools().')]
class McpClientListTools extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = $this->option('user')
            ? User::where('email', $this->option('user'))->first()
            : User::first();

        if (! $user) {
            $this->error('No hay usuarios en la base de datos. Crea uno con User::factory()->create() o pasa --user=email@existente.');

            return self::FAILURE;
        }

        $token = $user->createToken('mcp-client-demo', ['mcp:read', 'mcp:simulate', 'mcp:write'])->accessToken;

        $mcpUrl = url('/mcp/banorte');

        $this->info("Conectando a {$mcpUrl} como {$user->email}...");

        try {
            $client = Client::web($mcpUrl)->withToken($token)->connect();
        } catch (Throwable $exception) {
            $this->error("No se pudo conectar a /mcp/banorte: {$exception->getMessage()}");
            $this->line('¿Está corriendo `php artisan serve`?');

            return self::FAILURE;
        }

        try {
            $tools = $client->tools();

            $this->newLine();
            $this->info("Tools descubiertas vía list_tools() ({$tools->count()}):");
            $this->newLine();

            foreach ($tools as $tool) {
                $this->line("- {$tool->name}");
                $this->line("    description: {$tool->description}");
                $this->line('    inputSchema: '.json_encode($tool->inputSchema));
                $this->newLine();
            }

            return self::SUCCESS;
        } finally {
            $client->disconnect();
        }
    }
}
