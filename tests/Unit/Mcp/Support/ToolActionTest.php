<?php

namespace Tests\Unit\Mcp\Support;

use App\Mcp\Support\ToolAction;
use PHPUnit\Framework\TestCase;

class ToolActionTest extends TestCase
{
    public function test_make_builds_the_expected_shape(): void
    {
        $action = ToolAction::make('id', 'Label', 'some_tool', ['foo' => 'bar']);

        $this->assertSame([
            'id' => 'id',
            'label' => 'Label',
            'tool' => 'some_tool',
            'params' => ['foo' => 'bar'],
        ], $action);
    }

    public function test_params_default_to_an_empty_array(): void
    {
        $action = ToolAction::make('id', 'Label', 'some_tool');

        $this->assertSame([], $action['params']);
    }
}
