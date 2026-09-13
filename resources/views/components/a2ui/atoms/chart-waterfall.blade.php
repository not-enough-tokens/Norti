{{-- Chart / Waterfall: ingresos -> gastos -> capacidad de ahorro (financial_profile_card, detail=exact). --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $yMax = max(1, $chart['y_axis']['domain'][1] ?? 1);
    $labels = ['monthly_income' => 'Ingreso', 'monthly_expenses' => 'Gasto', 'monthly_savings_capacity' => 'Ahorro'];
@endphp

<div role="img" aria-label="Ingresos menos gastos, capacidad de ahorro resultante">
    {{-- items-stretch (default) a propósito: con items-end la barra hija con
         height en % nunca resuelve contra un alto definido y colapsa a 0. --}}
    <div class="flex gap-4" style="height: 5rem;">
        @foreach ($data as $bar)
            @php
                $heightPct = abs($bar['value']) / $yMax * 100;
                $colorClass = $bar['kind'] === 'decrease' ? 'bg-chart-negative' : 'bg-chart-total';
            @endphp
            <div class="flex flex-1 flex-col justify-end">
                <span class="mb-1 text-center text-xs font-medium text-text-primary">${{ number_format(abs($bar['value']), 0) }}</span>
                <div class="w-full rounded-t {{ $colorClass }}" style="height: {{ max(2, $heightPct) }}%"></div>
            </div>
        @endforeach
    </div>

    <div class="mt-1 flex gap-4">
        @foreach ($data as $bar)
            <span class="flex-1 text-center text-xs text-text-muted">{{ $labels[$bar['key']] ?? $bar['key'] }}</span>
        @endforeach
    </div>
</div>
