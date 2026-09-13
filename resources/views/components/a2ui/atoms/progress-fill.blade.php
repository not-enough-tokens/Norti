{{-- Color action-primary a propósito: progreso no es ganancia (nunca chart-positive aquí). --}}
@props(['percentage'])

<div class="h-2 w-full rounded-full bg-chart-track">
    <div class="h-full rounded-full bg-action-primary" style="width: {{ min(100, max(0, (float) $percentage)) }}%"></div>
</div>
