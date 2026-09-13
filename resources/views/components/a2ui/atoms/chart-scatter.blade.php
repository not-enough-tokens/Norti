{{-- Chart / Scatter: SVG inline (risk_analysis_panel). Ancla el texto según la
     posición para que las etiquetas de los extremos no se corten. --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $xDomain = $chart['x_axis']['domain'] ?? [0, 100];
    $yDomain = $chart['y_axis']['domain'] ?? [0, 1];
    $xRange = max(0.01, $xDomain[1] - $xDomain[0]);
    $yRange = max(0.01, $yDomain[1] - $yDomain[0]);
    $toX = fn ($v) => 20 + ($v - $xDomain[0]) / $xRange * 260;
    $toY = fn ($v) => 90 - ($v - $yDomain[0]) / $yRange * 80;
@endphp

<svg viewBox="0 0 300 110" role="img" aria-label="Riesgo contra rendimiento esperado por perfil" class="w-full">
    <line x1="20" y1="90" x2="290" y2="90" class="stroke-chart-grid" stroke-width="1" />
    <line x1="20" y1="10" x2="20" y2="90" class="stroke-chart-grid" stroke-width="1" />

    @if ($chart['reference_x'] ?? null)
        @php $refX = $toX($chart['reference_x']['value']); @endphp
        <line x1="{{ $refX }}" y1="10" x2="{{ $refX }}" y2="90" class="stroke-chart-highlight" stroke-width="1.5" stroke-dasharray="4 3" />
        <text x="{{ min(max($refX, 34), 280) }}" y="8" font-size="8" text-anchor="middle" class="fill-chart-highlight">Tu portafolio</text>
    @endif

    @foreach ($data as $point)
        @php
            $x = $toX($point['x']);
            $y = $toY($point['y']);
            $isEmphasis = ($point['emphasis'] ?? null) !== null;
            // Puntos pegados al borde izquierdo se etiquetan abajo en vez de
            // arriba -- si no, el texto (anclado a la izquierda) invade el
            // espacio del siguiente punto a la derecha.
            $nearLeftEdge = $x < 40;
            $anchor = $nearLeftEdge ? 'start' : ($x > 260 ? 'end' : 'middle');
            $labelY = $nearLeftEdge ? min(96, $y + 14) : max(9, $y - 8);
        @endphp
        <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $isEmphasis ? 5 : 3.5 }}" class="{{ $isEmphasis ? 'fill-chart-highlight' : 'fill-chart-muted' }}" />
        <text x="{{ $x }}" y="{{ $labelY }}" font-size="9" text-anchor="{{ $anchor }}" class="fill-text-primary">{{ ucfirst((string) $point['key']) }}</text>
    @endforeach
</svg>
