<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
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
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $this->validateOrLog($request, [
            'detail' => ['sometimes', 'string', 'in:summary,exact'],
        ]);

        $detail = $validated['detail'] ?? 'summary';

        $props = $this->goals->getGoals($user, $detail);

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
