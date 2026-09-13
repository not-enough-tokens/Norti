{{-- Chart / Line: SVG inline (asset_info_card). --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $yDomain = $chart['y_axis']['domain'] ?? [0, 1];
    $n = count($data);
    $range = max(0.01, $yDomain[1] - $yDomain[0]);
    $points = collect($data)->values()->map(function ($point, $i) use ($n, $yDomain, $range) {
        $x = $n > 1 ? $i / ($n - 1) * 280 + 10 : 150;
        $y = 90 - (($point['y'] - $yDomain[0]) / $range * 80);

        return "{$x},{$y}";
    })->implode(' ');
@endphp

<div>
    <svg viewBox="0 0 300 100" role="img" aria-label="Serie de precios" class="w-full">
        <polyline points="{{ $points }}" fill="none" class="stroke-chart-primary" stroke-width="2" vector-effect="non-scaling-stroke" />
    </svg>

    @if ($data !== [])
        <div class="mt-1 flex justify-between text-xs text-text-muted">
            <span>{{ $data[0]['x'] }}</span>
            <span>{{ $data[count($data) - 1]['x'] }}</span>
        </div>
    @endif
</div>
