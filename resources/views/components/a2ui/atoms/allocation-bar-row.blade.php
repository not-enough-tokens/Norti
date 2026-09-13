{{-- Allocation Bar Row: valor real (barra) + Target Marker opcional (recomendado). --}}
@props(['label', 'value', 'recommended' => null, 'assetType' => null])

@php
    $barClass = match ($assetType) {
        'accion' => 'bg-chart-asset-accion',
        'bono' => 'bg-chart-asset-bono',
        'fondo' => 'bg-chart-asset-fondo',
        'efectivo' => 'bg-chart-asset-efectivo',
        default => 'bg-chart-primary',
    };
@endphp

<div class="flex flex-col gap-1.5">
    <div class="flex items-center justify-between text-sm">
        <span class="text-text-body">{{ $label }}</span>
        <span class="font-medium text-text-primary">
            {{ $value }}%
            @if ($recommended !== null)
                <span class="text-text-muted">(recomendado: {{ $recommended }}%)</span>
            @endif
        </span>
    </div>
    <div class="relative h-2 w-full rounded-full bg-chart-track">
        <div class="h-full rounded-full {{ $barClass }}" style="width: {{ min(100, max(0, $value)) }}%"></div>

        @if ($recommended !== null)
            <div
                class="absolute top-1/2 h-3 w-0.5 -translate-y-1/2 rounded-full bg-chart-marker"
                style="left: {{ min(100, max(0, $recommended)) }}%"
                title="Recomendado: {{ $recommended }}%"
            ></div>
        @endif
    </div>
</div>
