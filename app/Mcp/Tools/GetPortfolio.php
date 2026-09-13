<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
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

        $this->logToolCall($request, success: true);

        return Response::structured([
            'component' => 'portfolio_summary',
            'props' => $this->portfolios->getPortfolio($user),
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
