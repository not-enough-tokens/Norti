<?php

namespace App\Console\Commands;

use App\Ai\Agents\BanorteMcpAgent;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Mcp\Client;
use Throwable;

/**
 * M5, vertical slice 3: a real AI agent (Laravel AI SDK) discovers and
 * invokes Banorte's MCP tools -- tool selection is now the model's job,
 * unlike mcp:client-tools/mcp:client-call which call things manually.
 *
 * The agent (App\Ai\Agents\BanorteMcpAgent) is provider-agnostic: it only
 * knows how to reach the MCP server, never which model answers the prompt.
 * The model provider defaults to OpenAI (the agent's #[Provider] attribute)
 * and can be overridden per-run with --provider/--model, so swapping models
 * never touches the agent or MCP layers.
 *
 * Requires `php artisan serve` running (real HTTP to /mcp/banorte) and the
 * chosen provider's API key configured (OPENAI_API_KEY by default).
 */
#[Signature('mcp:demo-agent
    {question? : Pregunta en lenguaje natural para el agente}
    {--user= : Email del usuario Passport a usar (default: el primero de la tabla users)}
    {--provider= : Proveedor del modelo, ej. openai o anthropic (default: el del agente)}
    {--model= : Modelo específico a usar (default: el modelo por defecto del proveedor)}')]
#[Description('Demuestra el flujo M5: un agente real (Laravel AI SDK) descubre e invoca las MCP tools de Banorte.')]
class McpAgentDemo extends Command
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

        $question = $this->argument('question')
            ?? '¿Cuál es la cotización actual de AAPL y qué tan riesgoso es mi portafolio?';

        $token = $user->createToken('mcp-agent-demo', ['mcp:read', 'mcp:simulate'])->accessToken;
        $mcpUrl = url('/mcp/banorte');

        $this->info("Usuario: {$user->email}");
        $this->info("Pregunta: {$question}");
        $this->newLine();

        try {
            $client = Client::web($mcpUrl)->withToken($token)->connect();
        } catch (Throwable $exception) {
            $this->error("No se pudo conectar a /mcp/banorte: {$exception->getMessage()}");
            $this->line('¿Está corriendo `php artisan serve`?');

            return self::FAILURE;
        }

        try {
            $agent = new BanorteMcpAgent($client);

            $response = $agent->prompt(
                $question,
                provider: $this->option('provider') ?: null,
                model: $this->option('model') ?: null,
            );

            $this->newLine();
            $this->info('Respuesta del agente:');
            $this->line((string) $response);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error("Error del agente: {$exception->getMessage()}");

            return self::FAILURE;
        } finally {
            $client->disconnect();
        }
    }
}
