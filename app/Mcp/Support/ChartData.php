<?php

namespace App\Mcp\Support;

/**
 * Shared shape for the `props.chart` contract (A2UI contract gap 13,
 * docs/architecture/a2ui-components.md#contrato-propschart-propuesto-brecha-13).
 * Every Service that builds a chart uses this instead of hand-rolling the
 * array, so the six chart-producing components all speak the same shape.
 */
class ChartData
{
    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<string, mixed>|null  $xAxis
     * @param  array<string, mixed>|null  $yAxis
     * @param  array<int, array<string, mixed>>|null  $series
     * @param  array<int, array<string, mixed>>|null  $excluded
     * @param  array<string, mixed>|null  $referenceX
     * @return array<string, mixed>
     */
    public static function make(
        string $type,
        array $data,
        ?string $unit = null,
        ?string $currency = null,
        ?array $xAxis = null,
        ?array $yAxis = null,
        ?array $series = null,
        ?array $excluded = null,
        ?array $referenceX = null,
    ): array {
        return array_filter([
            'type' => $type,
            'unit' => $unit,
            'currency' => $currency,
            'x_axis' => $xAxis,
            'y_axis' => $yAxis,
            'series' => $series,
            'data' => $data,
            'excluded' => $excluded,
            'reference_x' => $referenceX,
        ], fn (mixed $value): bool => $value !== null);
    }
}
