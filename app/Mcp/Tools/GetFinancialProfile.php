<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
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
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        $validated = $this->validateOrLog($request, [
            'detail' => ['sometimes', 'string', 'in:summary,exact'],
        ]);

        $detail = $validated['detail'] ?? 'summary';

        $this->logToolCall($request, success: true, safeInput: ['detail' => $detail]);

        return Response::structured([
            'component' => 'financial_profile_card',
            'props' => $this->profiles->getProfile($user, $detail),
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
