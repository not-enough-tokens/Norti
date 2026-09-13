<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Mcp\Client;
use Stringable;

/**
 * M5: an AI agent that answers questions by calling Banorte's MCP server as
 * a plain MCP client. The model provider defaults to OpenAI via the
 * attribute below but stays decoupled from any single provider -- callers
 * may override it per-prompt (see McpAgentDemo's --provider/--model).
 */
#[Provider(Lab::OpenAI)]
class BanorteMcpAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(
        private readonly Client $mcpClient,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Eres un asistente financiero de Banorte. Responde preguntas sobre el perfil '
            .'financiero, portafolio, información de activos, cotizaciones de mercado y '
            .'simulaciones de inversión del usuario autenticado usando únicamente las tools '
            .'disponibles. No inventes cifras: si necesitas un dato, invoca la tool correspondiente.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [...$this->mcpClient->tools()];
    }
}
