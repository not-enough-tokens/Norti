<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Services\Contracts\FinancialGoalServiceContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_financial_goals')]
#[Description('Obtiene las metas financieras del usuario autenticado y su progreso: si las alcanzará, meses restantes y porcentaje de avance. Por defecto regresa un resumen sin montos exactos (detail=summary); usa detail=exact solo si el usuario lo pidió explícitamente.')]
class GetFinancialGoals extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly FinancialGoalServiceContract $goals,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            return $this->errorResponse($request, 'scope_denied', 'No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $this->validateOrLog($request, [
            'detail' => ['sometimes', 'string', 'in:summary,exact'],
        ]);

        $detail = $validated['detail'] ?? 'summary';

        $props = $this->goals->getGoals($user, $detail);

        // Como el resto del catálogo: la tool solo delega en el Service para
        // los datos, pero arma actions[] aquí (no es lógica financiera, es la
        // sugerencia de siguiente paso para el agente).
        $props['actions'] = [
            ToolAction::make('view_financial_profile', 'Ver mi perfil financiero', 'get_financial_profile'),
        ];

        $props['goals'] = array_map(
            fn (array $goal, int $index): array => [...$goal, 'actions' => $this->goalActions($goal, $index)],
            $props['goals'],
            array_keys($props['goals']),
        );

        $this->logToolCall($request, success: true, safeInput: [
            'detail' => $detail,
            'goal_count' => count($props['goals']),
        ]);

        return Response::structured([
            'component' => 'financial_goals_list',
            'props' => $props,
        ]);
    }

    /**
     * Solo se ofrece cuando el Service pudo evaluar la meta contra el perfil
     * financiero (requiere `FinancialProfile`, ver `FinancialGoalServiceAdapter`)
     * y la meta todavía no se alcanza a tiempo -- una vencida ya no tiene nada
     * que simular.
     *
     * @param  array<string, mixed>  $goal
     * @return list<array<string, mixed>>
     */
    private function goalActions(array $goal, int $index): array
    {
        if (($goal['is_overdue'] ?? false) || ($goal['reaches_goal'] ?? true)) {
            return [];
        }

        return [
            ToolAction::make(
                "simulate_for_goal_{$index}",
                'Simular una inversión para esta meta',
                'simulate_investment',
            ),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'detail' => $schema->string()
                ->enum(['summary', 'exact'])
                ->description('Usa "summary" (default) salvo que el usuario haya pedido explícitamente ver cifras exactas; en ese caso usa "exact".'),
        ];
    }
}
