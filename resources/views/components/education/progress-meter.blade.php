@props(['completed', 'total', 'percentage'])

{{-- Progreso general de la ruta (patrón Score Meter, nodo 19:132): la barra
     usa el ancho exacto en porcentaje -- se llena sola conforme se completan
     más temas, sin necesitar JavaScript. --}}
<div class="flex flex-col gap-2 rounded-2xl border border-border-default bg-bg-surface p-5">
    <div class="flex items-center justify-between">
        <p class="text-xs leading-4 font-medium text-text-muted">Tu progreso</p>
        <p class="font-display text-xl leading-7 font-semibold text-text-primary">{{ $percentage }}%</p>
    </div>

    <div class="h-2 w-full overflow-hidden rounded-full bg-chart-track">
        <div class="h-full rounded-full bg-chart-marker transition-[width] duration-700 ease-out" style="width: {{ $percentage }}%"></div>
    </div>

    <div class="flex items-center justify-between text-xs leading-4 text-text-muted">
        <span>{{ $completed }} de {{ $total }} completados</span>
        <span>100%</span>
    </div>
</div>
