<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Services\FinancialEducationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_learning_path')]
#[Description('Obtiene la ruta de aprendizaje financiero del usuario autenticado, los temas disponibles con su estado de progreso, y el tema recomendado según su situación financiera actual (con recommended_reason indicando la señal que lo motivó, para que el agente explique el porqué).')]
class GetLearningPath extends Tool
{
    use LogsToolInvocation;

    public function __construct(
        private readonly FinancialEducationService $education,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:read')) {
            $this->logToolCall(
                $request,
                success: false,
                resultSummary: 'scope_denied'
            );

            return Response::error(
                'No autorizado: se requiere el scope mcp:read.'
            );
        }

        $learningPath = $this->education->getLearningPath($user);
        $recommendedTopic = $this->education->getRecommendedTopic($user);

        $this->logToolCall(
            $request,
            success: true
        );

        return Response::structured([
            'component' => 'learning_path',
            'props' => [
                'topics' => $learningPath,
                'recommended_topic' => $recommendedTopic,
            ],
        ]);
    }
}
