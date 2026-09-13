{{-- Chart / Column: divergente desde 0 (market_snapshot_grid). Si ningún
     valor es negativo, la mitad reservada para pérdidas se colapsa -- si no,
     con datos casi siempre positivos (el caso común) media gráfica quedaba
     vacía y las barras se veían casi iguales aunque su % difiriera. --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $values = array_filter(array_column($data, 'value'), fn ($v) => $v !== null);
    $bound = max(0.01, $values !== [] ? max(array_map('abs', $values)) : 1);
    $hasNegative = $values !== [] && min($values) < 0;
@endphp

<div role="img" aria-label="Variación porcentual por símbolo" class="flex items-stretch gap-3" style="height: 6rem;">
    @foreach ($data as $bar)
        @php
            $value = $bar['value'];
            $pct = $value !== null ? min(100, abs($value) / $bound * 100) : 0;
        @endphp
        <div class="flex flex-1 flex-col items-center">
            <div class="flex w-full flex-1 flex-col justify-end">
                @if ($value !== null && $value >= 0)
                    <span class="mb-1 text-center text-xs font-medium text-text-primary">+{{ number_format($value, 2) }}%</span>
                    <div class="w-full rounded-t bg-chart-positive" style="height: {{ $pct }}%"></div>
                @endif
            </div>

            @if ($hasNegative)
                <div class="h-px w-full bg-chart-baseline"></div>

                <div class="flex w-full flex-1 flex-col">
                    @if ($value !== null && $value < 0)
                        <div class="w-full rounded-b bg-chart-negative" style="height: {{ $pct }}%"></div>
                        <span class="mt-1 text-center text-xs font-medium text-text-primary">{{ number_format($value, 2) }}%</span>
                    @elseif ($value === null)
                        <span class="mt-1 text-center text-xs text-text-muted">s/d</span>
                    @endif
                </div>
            @elseif ($value === null)
                <span class="mt-1 text-center text-xs text-text-muted">s/d</span>
            @endif

            <span class="mt-1 text-xs text-text-muted">{{ $bar['key'] }}</span>
        </div>
    @endforeach
</div>
