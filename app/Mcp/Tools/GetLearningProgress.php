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

        $total = $learningPath->count();

        $completed = $learningPath
            ->where('is_completed', true)
            ->count();

        $pending = $total - $completed;

        $percentage = $total > 0
            ? round(($completed / $total) * 100)
            : 0;

        $this->logToolCall(
            $request,
            success: true
        );

        return Response::structured([
            'component' => 'learning_progress',
            'props' => [
                'total_topics' => $total,
                'completed_topics' => $completed,
                'pending_topics' => $pending,
                'completion_percentage' => $percentage,
                'category_gaps' => $this->education->getCategoryGaps($user),
            ],
        ]);
    }
}
