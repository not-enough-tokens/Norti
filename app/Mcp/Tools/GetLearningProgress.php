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

#[Name('get_learning_progress')]
#[Description('Obtiene el progreso del usuario en su ruta de educación financiera, incluyendo las categorías de contenido en las que todavía no ha completado ningún tema.')]
class GetLearningProgress extends Tool
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

        $this->logToolCall(
            $request,
            success: true
        );

        return Response::structured([
            'component' => 'learning_progress',
            'props' => [
                ...$this->education->getProgressSummary($user),
                'category_gaps' => $this->education->getCategoryGaps($user),
                'actions' => [
                    ToolAction::make('continue_learning', 'Continuar con el siguiente tema', 'get_learning_path'),
                ],
            ],
        ]);
    }
}
