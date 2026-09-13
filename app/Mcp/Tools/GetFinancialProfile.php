<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Services\Contracts\FinancialProfileServiceContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_financial_profile')]
#[Description('Obtiene el perfil financiero del usuario autenticado: tolerancia al riesgo, horizonte de inversión y categoría de ahorro. Por defecto regresa un resumen sin montos exactos (detail=summary); usa detail=exact solo si el usuario lo pidió explícitamente.')]
class GetFinancialProfile extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly FinancialProfileServiceContract $profiles,
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

        // Correr el servicio antes de auditar -- ver nota en AnalyzePortfolio.
        $props = $this->profiles->getProfile($user, $detail);
        $props['actions'] = $this->buildActions($props);

        $this->logToolCall($request, success: true, safeInput: ['detail' => $detail]);

        return Response::structured([
            'component' => 'financial_profile_card',
            'props' => $props,
        ]);
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<array<string, mixed>>
     */
    private function buildActions(array $props): array
    {
        if (! ($props['has_profile'] ?? false)) {
            return [];
        }

        // "Ver cifras exactas" (post-MVP, ver política de datos sensibles en
        // CLAUDE.md) queda fuera a propósito: requiere confirmación explícita
        // del usuario, no un action que el LLM pueda disparar por su cuenta.
        // risk_tolerance ya viene normalizado por EloquentFinancialProfileService
        // (gap 4) -- no hay que volver a llamar a RiskAnalysisService aquí.
        return [
            ToolAction::make(
                'simulate_with_my_profile',
                'Simular con mi perfil',
                'simulate_investment',
                ['risk_profile' => $props['risk_tolerance']],
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
