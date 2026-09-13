<?php

namespace Tests\Unit;

use App\Console\Commands\McpAgentDemo;
use Illuminate\Support\Collection;
use Laravel\Mcp\Client\Primitives\Tool;
use PHPUnit\Framework\TestCase;

class McpAgentDemoTest extends TestCase
{
    public function test_maps_mcp_tools_into_anthropics_tool_schema(): void
    {
        $tool = new Tool(
            client: null,
            name: 'get_market_snapshot',
            title: null,
            description: 'Obtiene cotizaciones para una lista de símbolos.',
            inputSchema: ['type' => 'object', 'properties' => ['symbols' => ['type' => 'array']], 'required' => ['symbols']],
            outputSchema: null,
            annotations: [],
            meta: null,
        );

        $mapped = McpAgentDemo::mapToolsForAnthropic(new Collection([$tool]));

        $this->assertSame([[
            'name' => 'get_market_snapshot',
            'description' => 'Obtiene cotizaciones para una lista de símbolos.',
            'input_schema' => ['type' => 'object', 'properties' => ['symbols' => ['type' => 'array']], 'required' => ['symbols']],
        ]], $mapped);
    }

    public function test_gives_an_empty_object_shaped_schema_for_a_tool_without_input(): void
    {
        $tool = new Tool(
            client: null,
            name: 'get_portfolio',
            title: null,
            description: null,
            inputSchema: [],
            outputSchema: null,
            annotations: [],
            meta: null,
        );

        $mapped = McpAgentDemo::mapToolsForAnthropic(new Collection([$tool]));

        $this->assertSame('', $mapped[0]['description']);
        $this->assertEquals(['type' => 'object', 'properties' => (object) []], $mapped[0]['input_schema']);
    }
}
