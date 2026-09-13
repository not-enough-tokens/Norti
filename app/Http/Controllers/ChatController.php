<?php

namespace App\Http\Controllers;

use App\Ai\Agents\BanorteMcpAgent;
use App\Ai\ToolInvocationCollector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Laravel\Mcp\Client;
use Throwable;

/**
 * Chat real conectado a BanorteMcpAgent (M5) -- a diferencia de
 * OnboardingController (preguntas fijas, sin LLM, solo para arrancar el
 * FinancialProfile de un usuario nuevo, que el agente no tiene tool para
 * escribir), cada turno aquí invoca al agente real contra las 11 tools de
 * /mcp/banorte. La conversación vive en sesión, igual que el wizard -- no
 * hay tabla de mensajes todavía.
 *
 * Cada mensaje del agente trae `components`: los `component`/`props` de
 * cada tool que invocó para responder (ver App\Ai\ToolInvocationCollector),
 * que la vista intenta renderizar con un componente Blade real
 * (`resources/views/components/a2ui/*`) antes de caer a un volcado JSON.
 */
class ChatController extends Controller
{
    private const SESSION_KEY = 'chat_conversation';

    private const TOKEN_SESSION_KEY = 'mcp_chat_token';

    public function __construct(
        private readonly ToolInvocationCollector $collector,
        private readonly McpTokenController $tokens,
    ) {}

    public function index(Request $request): View
    {
        return view('chat.index', [
            'user' => $request->user(),
            'messages' => $request->session()->get(self::SESSION_KEY, []),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message = trim($validated['message']);
        $messages = $request->session()->get(self::SESSION_KEY, []);
        $messages[] = ['role' => 'user', 'text' => $message];

        // Un turno del agente son varios round-trips MCP+LLM (initialize,
        // tools/list, un tools/call por tool que decida invocar, más una
        // respuesta de OpenAI entre cada uno) -- fácilmente más de los 30s
        // por default de PHP en web.
        set_time_limit(120);

        $this->collector->reset();

        try {
            // No url('/mcp/banorte'): dentro de una request real ese helper
            // resuelve contra el host de ESTA request, no necesariamente
            // donde vive /mcp/banorte. services.mcp.loopback_url (no
            // app.url a secas) para poder apuntar esta llamada a un
            // segundo proceso en local -- ver la nota en config/services.php.
            $mcpUrl = rtrim((string) config('services.mcp.loopback_url'), '/').'/mcp/banorte';
            $client = Client::web($mcpUrl)->withToken($this->mcpToken($request))->connect();

            try {
                $answer = (string) (new BanorteMcpAgent($client))->prompt($message);
            } finally {
                $client->disconnect();
            }

            $messages[] = [
                'role' => 'assistant',
                'text' => $answer,
                'components' => $this->collector->all(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            $messages[] = [
                'role' => 'assistant',
                'text' => 'No pude procesar tu mensaje en este momento. Intenta de nuevo en unos segundos.',
                'components' => [],
            ];
        }

        $request->session()->put(self::SESSION_KEY, $messages);

        return redirect()->route('chat.index');
    }

    /**
     * Un token por sesión de navegador, no uno por mensaje --
     * McpTokenController::issue() ya revoca el token 'mcp-session' anterior,
     * así que pedir uno nuevo en cada turno invalidaría el que se acaba de
     * usar sin necesidad.
     */
    private function mcpToken(Request $request): string
    {
        $cached = $request->session()->get(self::TOKEN_SESSION_KEY);

        if ($cached && Carbon::parse($cached['expires_at'])->isFuture()) {
            return $cached['token'];
        }

        $issued = $this->tokens->issue($request->user());
        $request->session()->put(self::TOKEN_SESSION_KEY, $issued);

        return $issued['token'];
    }
}
