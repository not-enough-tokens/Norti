<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Models\EducationalTopic;
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
    use LogsToolInvocation;

    public function __construct(
        private readonly RiskAnalysisServiceContract $riskAnalysis,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            $this->logToolCall($request, success: false, resultSummary: 'scope_denied');

            return Response::error('No autorizado: se requiere el scope mcp:read.');
        }

        // Correr el servicio ANTES de auditar: si se loggea primero, una
        // excepción del servicio deja un audit log que dice 'ok' para una
        // llamada que en realidad falló.
        $props = $this->riskAnalysis->analyze($user);
        $props['actions'] = $this->buildActions($props);

        $this->logToolCall($request, success: true);

        return Response::structured([
            'component' => 'risk_analysis_panel',
            'props' => $props,
        ]);
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<array<string, mixed>>
     */
    private function buildActions(array $props): array
    {
        $actions = [];

        if ($props['risk_tolerance'] ?? null) {
            $actions[] = ToolAction::make(
                'simulate_with_recommended_profile',
                'Simular con perfil recomendado',
                'simulate_investment',
                ['risk_profile' => $props['risk_tolerance']],
            );
        }

        $diversification = EducationalTopic::where('slug', 'diversificacion')->first();

        if ($diversification) {
            $actions[] = ToolAction::make(
                'learn_diversification',
                'Aprender a diversificar',
                'get_educational_topic',
                ['topic_id' => $diversification->id],
            );
        }

        return $actions;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
