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
        return 'Eres un asistente financiero y educativo de Banorte. Ayudas al usuario autenticado '
            .'a entender su situación financiera y a aprender sobre finanzas personales, usando '
            .'únicamente las tools disponibles -- nunca inventes cifras ni contenido: si necesitas '
            .'un dato, invoca la tool correspondiente.'
            .PHP_EOL.PHP_EOL
            .'Capacidades disponibles: perfil financiero, metas financieras (get_financial_goals), '
            .'portafolio, información de activos, cotizaciones de mercado y simulaciones de '
            .'inversión; y educación financiera (get_educational_topic, get_learning_path, '
            .'get_learning_progress, mark_topic_completed).'
            .PHP_EOL.PHP_EOL
            .'Cómo presentar la información:'.PHP_EOL
            .'- get_learning_path regresa un recommended_topic con un recommended_reason '
            .'(no_goals, concentrated_portfolio, conservative_profile_with_stocks o default). Nunca '
            .'muestres ese código tal cual: tradúcelo a una explicación natural de por qué ese tema '
            .'es relevante para la situación del usuario (por ejemplo, no_goals -> "aún no tienes '
            .'metas registradas").'.PHP_EOL
            .'- get_learning_progress regresa category_gaps (categorías sin ningún tema completado). '
            .'Menciónalas como una oportunidad de aprender, no como una carencia.'.PHP_EOL
            .'- get_financial_profile y get_financial_goals regresan detail=summary por default; '
            .'solo pide detail=exact si el usuario pidió explícitamente el monto exacto.'.PHP_EOL
            .'- Tu objetivo es ayudar a entender la información y decidir mejor, no decirle al '
            .'usuario qué hacer con su dinero.';
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
