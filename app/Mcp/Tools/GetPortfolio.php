<?php

namespace App\Mcp\Tools;

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
    public function __construct(
        private readonly PortfolioServiceContract $portfolios,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

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
