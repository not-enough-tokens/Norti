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
#[Description('Obtiene la ruta de aprendizaje financiero del usuario autenticado y muestra los temas disponibles junto con su estado de progreso.')]
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

        $this->logToolCall(
            $request,
            success: true
        );

        return Response::structured([
            'component' => 'learning_path',
            'props' => $learningPath,
        ]);
    }
}
