<?php

namespace Tests\Unit\Mcp\Support;

use App\Mcp\Support\ChartData;
use PHPUnit\Framework\TestCase;

class ChartDataTest extends TestCase
{
    public function test_make_includes_only_the_fields_that_were_provided(): void
    {
        $chart = ChartData::make(type: 'line', data: [['x' => '2024-01-01', 'y' => 1.0]]);

        $this->assertSame([
            'type' => 'line',
            'data' => [['x' => '2024-01-01', 'y' => 1.0]],
        ], $chart);
    }

    public function test_make_keeps_an_empty_data_array(): void
    {
        $chart = ChartData::make(type: 'column', data: []);

        $this->assertArrayHasKey('data', $chart);
        $this->assertSame([], $chart['data']);
    }

    public function test_make_builds_the_full_shape_when_every_field_is_given(): void
    {
        $chart = ChartData::make(
            type: 'scatter',
            data: [['key' => 'moderate', 'x' => 20, 'y' => 9.0]],
            unit: 'percent',
            currency: null,
            xAxis: ['domain' => [0, 100]],
            yAxis: ['domain' => [0, 15]],
            series: [['key' => 'gain', 'role' => 'positive']],
            excluded: [['key' => 'CETES28', 'reason' => 'unpriced']],
            referenceX: ['key' => 'portfolio', 'value' => 28.47],
        );

        $this->assertSame([
            'type' => 'scatter',
            'unit' => 'percent',
            'x_axis' => ['domain' => [0, 100]],
            'y_axis' => ['domain' => [0, 15]],
            'series' => [['key' => 'gain', 'role' => 'positive']],
            'data' => [['key' => 'moderate', 'x' => 20, 'y' => 9.0]],
            'excluded' => [['key' => 'CETES28', 'reason' => 'unpriced']],
            'reference_x' => ['key' => 'portfolio', 'value' => 28.47],
        ], $chart);
    }
}
