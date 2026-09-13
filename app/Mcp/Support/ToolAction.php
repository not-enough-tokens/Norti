<?php

namespace App\Mcp\Support;

/**
 * `props.actions[]` (A2UI contract gap 1, docs/architecture/a2ui-components.md):
 * every tool that offers a next step tells the component which one via
 * `{ id, label, tool, params }`, instead of the component inventing a tool
 * call itself ("el componente no calcula"). When a param isn't known yet
 * (e.g. an amount only the user can supply), it's simply omitted -- the
 * agent fills the gap from conversation context, it never guesses a value.
 */
class ToolAction
{
    /**
     * @param  array<string, mixed>  $params
     * @return array{id: string, label: string, tool: string, params: array<string, mixed>}
     */
    public static function make(string $id, string $label, string $tool, array $params = []): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'tool' => $tool,
            'params' => $params,
        ];
    }
}
