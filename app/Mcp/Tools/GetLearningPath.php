<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
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
            return $this->errorResponse($request, 'scope_denied', 'No autorizado: se requiere el scope mcp:read.');
        }

        $recommendedTopic = $this->education->getRecommendedTopic($user);

        // Gap 10: antes era el arreglo crudo de modelos Eloquent (con
        // content y timestamps que esta lista nunca usa). Recortado a lo que
        // el contrato de Figma pide para una Topic Row.
        $topics = $this->education->getLearningPath($user)->map(fn ($topic) => [
            'id' => $topic->id,
            'title' => $topic->title,
            'category' => $topic->category,
            'estimated_minutes' => $topic->estimated_minutes,
            'is_completed' => $topic->is_completed,
            'actions' => [
                ToolAction::make(
                    "view_topic_{$topic->id}",
                    'Ver tema',
                    'get_educational_topic',
                    ['topic_id' => $topic->id],
                ),
            ],
        ])->all();

        $this->logToolCall(
            $request,
            success: true
        );

        return Response::structured([
            'component' => 'learning_path',
            'props' => [
                'topics' => $topics,
                'recommended_topic' => $recommendedTopic ? [
                    'id' => $recommendedTopic->id,
                    'title' => $recommendedTopic->title,
                    'recommended_reason' => $recommendedTopic->recommended_reason,
                ] : null,
                'actions' => [
                    ToolAction::make('view_progress', 'Ver mi progreso', 'get_learning_progress'),
                ],
            ],
        ]);
    }
}
