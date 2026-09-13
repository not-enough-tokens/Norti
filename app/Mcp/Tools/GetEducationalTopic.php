<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsToolInvocation;
use App\Models\EducationalTopic;
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

        $validated = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:educational_topics,id'],
        ]);

        $topic = EducationalTopic::find($validated['topic_id']);

        $this->logToolCall(
            $request,
            success: true,
            safeInput: [
                'topic_id' => $topic->id,
            ]
        );

        return Response::structured([
            'component' => 'educational_topic',
            'props' => $topic,
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
