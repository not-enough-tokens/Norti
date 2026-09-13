<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;
use Laravel\Mcp\Client;
use Throwable;

/**
 * M5, vertical slice 2: verify our own application can act as an MCP client
 * and invoke a specific tool (call_tool) against our own Banorte MCP server,
 * proving the request reaches the existing MarketDataProviderContract chain
 * unchanged. No LLM/agent involved yet -- the caller supplies the tool name
 * and arguments manually, exactly like list_tools() did for discovery.
 *
 * Requires `php artisan serve` running: this speaks real HTTP to
 * /mcp/banorte via Laravel\Mcp\Client's HttpTransport, not the in-process
 * test kernel.
 */
#[Signature('mcp:client-call
    {tool : Nombre de la tool a invocar, ej. get_market_snapshot}
    {--arguments= : Argumentos en JSON (objeto), ej. symbols con un arreglo de tickers}
    {--user= : Email del usuario Passport a usar (default: el primero de la tabla users)}')]
#[Description('Conecta como cliente MCP a /mcp/banorte e invoca una tool específica, vía call_tool().')]
class McpClientCallTool extends Command
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

        $rawArguments = trim((string) $this->option('arguments'));

        try {
            $arguments = $rawArguments === '' ? [] : json_decode($rawArguments, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error("El valor de --arguments no es JSON válido: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! is_array($arguments)) {
            $this->error('--arguments debe decodificar a un objeto JSON, ej. {"symbols":["AAPL"]}.');

            return self::FAILURE;
        }

        $tool = $this->argument('tool');
        $token = $user->createToken('mcp-client-demo', ['mcp:read', 'mcp:simulate'])->accessToken;
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
            $this->newLine();
            $this->info("Llamando call_tool(\"{$tool}\", ".json_encode($arguments).')...');
            $this->newLine();

            $result = $client->callTool($tool, $arguments);

            $this->info($result->isError ? 'Resultado (error):' : 'Resultado:');
            $this->line($result->text());

            if ($result->structuredContent !== null) {
                $this->newLine();
                $this->info('structuredContent:');
                $this->line(json_encode(
                    $result->structuredContent,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ));
            }

            return $result->isError ? self::FAILURE : self::SUCCESS;
        } finally {
            $client->disconnect();
        }
    }
}
