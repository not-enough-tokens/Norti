<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Primitives\Tool;
use RuntimeException;
use Throwable;

/**
 * M5 demonstration: a real Claude agent discovers and invokes Banorte's MCP
 * tools over Passport-authenticated Streamable HTTP, exactly as an external
 * AI agent would (see docs/development/roadmap.md, M5).
 *
 * Requires `php artisan serve` running (this command speaks real HTTP to
 * /mcp/banorte, not the in-process test kernel) and ANTHROPIC_API_KEY set.
 */
#[Signature('mcp:demo-agent
    {question? : Pregunta en lenguaje natural para el agente}
    {--user= : Email del usuario Passport a usar (default: el primero de la tabla users)}
    {--max-turns=6 : Máximo de turnos de uso de tools antes de forzar una respuesta final}')]
#[Description('Demuestra el flujo M5: un agente de Claude descubre e invoca las MCP tools de Banorte.')]
class McpAgentDemo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('services.anthropic.key');

        if (blank($apiKey)) {
            $this->error('Falta ANTHROPIC_API_KEY en el .env.');

            return self::FAILURE;
        }

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

        $this->info("Usuario: {$user->email}");
        $this->info("Pregunta: {$question}");
        $this->newLine();

        try {
            $client = Client::web(url('/mcp/banorte'))->withToken($token)->connect();
        } catch (Throwable $exception) {
            $this->error("No se pudo conectar a /mcp/banorte: {$exception->getMessage()}");
            $this->line('¿Está corriendo `php artisan serve`?');

            return self::FAILURE;
        }

        try {
            return $this->converse($client, $question, (int) $this->option('max-turns'), (string) $apiKey);
        } finally {
            $client->disconnect();
        }
    }

    protected function converse(Client $client, string $question, int $maxTurns, string $apiKey): int
    {
        $tools = $client->tools();

        $this->info("Tools descubiertas ({$tools->count()}):");
        foreach ($tools as $tool) {
            $this->line("  - {$tool->name}: {$tool->description}");
        }
        $this->newLine();

        $anthropicTools = self::mapToolsForAnthropic($tools);

        /** @var array<int, array<string, mixed>> $messages */
        $messages = [
            ['role' => 'user', 'content' => $question],
        ];

        for ($turn = 1; $turn <= $maxTurns; $turn++) {
            $response = $this->callClaude($apiKey, $messages, $anthropicTools);

            /** @var array<int, array<string, mixed>> $content */
            $content = $response['content'] ?? [];
            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolUses = array_values(array_filter(
                $content,
                fn (array $block): bool => ($block['type'] ?? null) === 'tool_use',
            ));

            if ($toolUses === []) {
                $finalText = collect($content)
                    ->where('type', 'text')
                    ->pluck('text')
                    ->implode("\n");

                $this->newLine();
                $this->info('Respuesta final del agente:');
                $this->line($finalText);

                return self::SUCCESS;
            }

            $messages[] = ['role' => 'user', 'content' => $this->invokeTools($client, $toolUses)];
        }

        $this->warn("Se alcanzó el máximo de {$maxTurns} turnos sin una respuesta final.");

        return self::FAILURE;
    }

    /**
     * @param  array<int, array<string, mixed>>  $toolUses
     * @return array<int, array<string, mixed>>
     */
    protected function invokeTools(Client $client, array $toolUses): array
    {
        $toolResults = [];

        foreach ($toolUses as $toolUse) {
            $arguments = $toolUse['input'] ?? [];

            $this->comment("→ Llamando tool [{$toolUse['name']}] con ".json_encode($arguments));

            $result = $client->callTool($toolUse['name'], $arguments);

            $this->line('  Resultado: '.$result->text());

            $toolResults[] = [
                'type' => 'tool_result',
                'tool_use_id' => $toolUse['id'],
                'content' => $result->text(),
                'is_error' => $result->isError,
            ];
        }

        return $toolResults;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapToolsForAnthropic(Collection $tools): array
    {
        return $tools->map(fn (Tool $tool): array => [
            'name' => $tool->name,
            'description' => $tool->description ?? '',
            'input_schema' => $tool->inputSchema === []
                ? ['type' => 'object', 'properties' => (object) []]
                : $tool->inputSchema,
        ])->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    protected function callClaude(string $apiKey, array $messages, array $tools): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'messages' => $messages,
            'tools' => $tools,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Anthropic API error ({$response->status()}): {$response->body()}");
        }

        return $response->json();
    }
}
