<?php

namespace App\Ai;

/**
 * Prototype for wiring A2UI into the chat: every MCP tool the agent calls
 * returns `Response::structured(['component' => ..., 'props' => ...])`, and
 * `Laravel\Ai\Tools\McpTool` JSON-encodes that `structuredContent` into the
 * string it hands back to the model -- it never gets discarded, it just
 * looks like plain tool-result text to the SDK. `CaptureStructuredToolResults`
 * (a listener on `Laravel\Ai\Events\ToolInvoked`) decodes that JSON back into
 * `component`/`props` and records it here, so a controller can hand both the
 * agent's final text AND the structured payloads it triggered along the way
 * to the view -- no fork of the AI SDK needed.
 *
 * Bound as a singleton (see AppServiceProvider) so it's shared for the
 * lifetime of one request/agent run; call reset() before starting a new one.
 */
class ToolInvocationCollector
{
    /** @var list<array{tool: string, component: string, props: array<string, mixed>}> */
    private array $invocations = [];

    /**
     * @param  array<string, mixed>  $props
     */
    public function record(string $tool, string $component, array $props): void
    {
        $this->invocations[] = ['tool' => $tool, 'component' => $component, 'props' => $props];
    }

    /**
     * @return list<array{tool: string, component: string, props: array<string, mixed>}>
     */
    public function all(): array
    {
        return $this->invocations;
    }

    public function reset(): void
    {
        $this->invocations = [];
    }
}
