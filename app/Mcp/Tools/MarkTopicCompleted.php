<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Models\EducationalTopic;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('mark_topic_completed')]
#[Description('Marca un tema de educación financiera como completado para el usuario autenticado.')]
class MarkTopicCompleted extends Tool
{
    use LogsToolInvocation;

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->tokenCan('mcp:write')) {
            $this->logToolCall(
                $request,
                success: false,
                resultSummary: 'scope_denied'
            );

            return Response::error(
                'No autorizado: se requiere el scope mcp:write.'
            );
        }

        $validated = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:educational_topics,id'],
        ]);

        $topic = EducationalTopic::find($validated['topic_id']);

        $user->educationalTopics()->syncWithoutDetaching([
            $topic->id => [
                'completed_at' => now(),
            ],
        ]);

        $this->logToolCall(
            $request,
            success: true,
            safeInput: [
                'topic_id' => $topic->id,
            ]
        );

        return Response::structured([
            'component' => 'learning_topic_completed',
            'props' => [
                'topic_id' => $topic->id,
                'title' => $topic->title,
                'completed' => true,
                'actions' => [
                    ToolAction::make('view_progress', 'Ver mi progreso', 'get_learning_progress'),
                    ToolAction::make('next_topic', 'Siguiente tema', 'get_learning_path'),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic_id' => $schema->integer()
                ->description('ID del tema educativo que se desea marcar como completado.'),
        ];
    }
}
