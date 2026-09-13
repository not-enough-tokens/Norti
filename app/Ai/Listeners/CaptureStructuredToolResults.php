<?php

namespace App\Ai\Listeners;

use App\Ai\ToolInvocationCollector;
use Laravel\Ai\Events\ToolInvoked;

/**
 * See App\Ai\ToolInvocationCollector for why this works without touching the
 * AI SDK or BanorteMcpAgent: McpTool::handle() already JSON-encodes the MCP
 * tool's structuredContent before returning it, so it survives, as a string,
 * all the way to this event.
 */
class CaptureStructuredToolResults
{
    public function __construct(
        private readonly ToolInvocationCollector $collector,
    ) {}

    public function handle(ToolInvoked $event): void
    {
        if (! is_string($event->result)) {
            return;
        }

        $decoded = json_decode($event->result, true);

        if (
            ! is_array($decoded)
            || ! isset($decoded['component'], $decoded['props'])
            || ! is_string($decoded['component'])
            || ! is_array($decoded['props'])
        ) {
            // Not an A2UI structured result -- e.g. a tool_error, whose text
            // is a plain "MCP tool error: ..." string with no structuredContent.
            return;
        }

        $toolName = method_exists($event->tool, 'name') ? $event->tool->name() : $decoded['component'];

        $this->collector->record($toolName, $decoded['component'], $decoded['props']);
    }
}
