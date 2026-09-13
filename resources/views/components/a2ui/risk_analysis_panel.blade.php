@props(['props' => []])

<x-a2ui.atoms.card title="Análisis de riesgo" tool="analyze_portfolio">
    @if (! ($props['has_holdings'] ?? false))
        <p class="text-sm text-text-muted">Sin holdings todavía -- no hay distribución que analizar.</p>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($props['allocation_by_asset_type'] as $type => $pct)
                <x-a2ui.atoms.allocation-bar-row
                    :label="ucfirst($type)"
                    :value="$pct"
                    :recommended="$props['recommended_allocation_by_asset_type'][$type] ?? null"
                    :asset-type="$type"
                />
            @endforeach
        </div>

        <div>
            <p class="mb-1 text-xs text-text-muted">Índice de diversificación</p>
            <x-a2ui.atoms.score-meter :score="$props['diversification_score']" />
        </div>

        @if ($props['concentration_warning'] ?? false)
            <x-alert tone="warning" title="Concentración alta en un solo tipo de activo" />
        @endif

        @if (($props['priced_with_live_market_data'] ?? true) === false)
            <x-alert tone="neutral" title="Alguna posición no se valuó con precio de mercado en vivo" />
        @endif
    @endif

    @if ($props['chart'] ?? null)
        <x-a2ui.atoms.chart-scatter :chart="$props['chart']" />
    @endif

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
