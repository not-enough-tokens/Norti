<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Mcp\Support\ToolAction;
use App\Models\EducationalTopic;
use App\Services\FinancialEducationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_educational_topic')]
#[Description('Obtiene el contenido de un tema específico de educación financiera.')]
class GetEducationalTopic extends Tool
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

        $validated = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:educational_topics,id'],
        ]);

        $topic = EducationalTopic::find($validated['topic_id']);
        $isCompleted = $this->education->isTopicCompleted($user, $topic->id);

        // Gap 11: props del modelo Eloquent crudo (sin timestamps, que el
        // componente no usa) + is_completed, que antes no existía aquí --
        // ahora "Marcar como completado" solo se ofrece si aún no se completó.
        $props = [
            'id' => $topic->id,
            'title' => $topic->title,
            'slug' => $topic->slug,
            'description' => $topic->description,
            'content' => $topic->content,
            'category' => $topic->category,
            'difficulty' => $topic->difficulty,
            'estimated_minutes' => $topic->estimated_minutes,
            'is_completed' => $isCompleted,
            'actions' => $isCompleted ? [] : [
                ToolAction::make('mark_completed', 'Marcar como completado', 'mark_topic_completed', ['topic_id' => $topic->id]),
            ],
        ];

        $this->logToolCall(
            $request,
            success: true,
            safeInput: [
                'topic_id' => $topic->id,
            ]
        );

        return Response::structured([
            'component' => 'educational_topic',
            'props' => $props,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic_id' => $schema->integer()
                ->description('ID del tema educativo que se desea consultar.'),
        ];
    }
}
