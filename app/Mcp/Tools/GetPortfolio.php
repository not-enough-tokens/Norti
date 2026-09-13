<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Services\Contracts\PortfolioServiceContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_portfolio')]
#[Description('Obtiene los portafolios del usuario autenticado con sus holdings, valor de mercado en vivo y ganancia/pérdida no realizada.')]
class GetPortfolio extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly PortfolioServiceContract $portfolios,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        // Correr el servicio antes de auditar -- ver nota en AnalyzePortfolio.
        $props = $this->portfolios->getPortfolio($user);
        $props['actions'] = [
            ToolAction::make('analyze_risk', 'Analizar riesgo', 'analyze_portfolio'),
        ];

        foreach ($props['portfolios'] as &$portfolio) {
            foreach ($portfolio['holdings'] as &$holding) {
                $holding['actions'] = [
                    ToolAction::make(
                        "view_asset_{$holding['symbol']}",
                        "Ver información de {$holding['symbol']}",
                        'get_asset_information',
                        ['symbol' => $holding['symbol']],
                    ),
                ];
            }
        }
        unset($portfolio, $holding);

        $this->logToolCall($request, success: true);

        return Response::structured([
            'component' => 'portfolio_summary',
            'props' => $props,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
