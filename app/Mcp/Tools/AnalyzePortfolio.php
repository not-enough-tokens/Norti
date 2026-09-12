<?php

namespace App\Mcp\Tools;

use App\Services\Contracts\RiskAnalysisServiceContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('analyze_portfolio')]
#[Description('Analiza el riesgo y la diversificación del portafolio del usuario autenticado: allocation por tipo de activo e índice de concentración.')]
class AnalyzePortfolio extends Tool
{
    public function __construct(
        private readonly RiskAnalysisServiceContract $riskAnalysis,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        return Response::structured([
            'component' => 'risk_analysis_panel',
            'props' => $this->riskAnalysis->analyze($user),
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
