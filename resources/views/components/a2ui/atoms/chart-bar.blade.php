{{-- Chart / Bar: horizontal desde 0 (portfolio_summary). --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $domain = $chart['x_axis']['domain'] ?? [0, 1];
    $range = max(0.01, $domain[1] - $domain[0]);
@endphp

<div role="img" aria-label="Rendimiento porcentual por posición" class="flex flex-col gap-2">
    @foreach ($data as $bar)
        @php
            $value = $bar['value'];
            $pct = min(100, abs($value - min(0, $value)) / $range * 100);
            $barColorClass = $value >= 0 ? 'bg-chart-positive' : 'bg-chart-negative';
        @endphp
        <div class="flex items-center gap-3 text-sm">
            <span class="w-20 shrink-0 truncate text-text-muted">{{ $bar['label'] ?? $bar['key'] }}</span>
            <div class="h-2 flex-1 rounded-full bg-chart-track">
                <div class="h-full rounded-full {{ $barColorClass }}" style="width: {{ $pct }}%"></div>
            </div>
            <span class="w-16 shrink-0 text-right font-medium text-text-primary">{{ $value >= 0 ? '+' : '' }}{{ $value }}%</span>
        </div>
    @endforeach

    @if (($chart['excluded'] ?? []) !== [])
        <p class="text-xs text-text-muted">Sin cotización: {{ collect($chart['excluded'])->pluck('key')->implode(', ') }}</p>
    @endif
</div>
