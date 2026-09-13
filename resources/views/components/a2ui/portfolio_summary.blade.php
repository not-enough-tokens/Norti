@props(['props' => []])

<x-a2ui.atoms.card title="Tu portafolio" tool="get_portfolio">
    @forelse ($props['portfolios'] ?? [] as $portfolio)
        <div class="flex flex-col gap-3">
            <div>
                <p class="font-medium text-text-primary">{{ $portfolio['name'] }}</p>
                @if ($portfolio['description'])
                    <p class="text-xs text-text-muted">{{ $portfolio['description'] }}</p>
                @endif
            </div>

            <div class="flex flex-col">
                @foreach ($portfolio['holdings'] as $holding)
                    <x-a2ui.atoms.key-value-row :label="$holding['symbol'].' · '.$holding['asset_type']">
                        @if ($holding['market_value'] !== null)
                            {{ $holding['currency'] }} {{ number_format($holding['market_value'], 2) }}
                            @if ($holding['unrealized_gain_pct'] !== null)
                                <span class="ml-1 {{ $holding['unrealized_gain'] >= 0 ? 'text-feedback-positive-text' : 'text-feedback-negative-text' }}">
                                    ({{ $holding['unrealized_gain'] >= 0 ? '+' : '' }}{{ $holding['unrealized_gain_pct'] }}%)
                                </span>
                            @endif
                        @else
                            <span class="text-text-muted">Sin cotización</span>
                        @endif
                    </x-a2ui.atoms.key-value-row>
                @endforeach
            </div>

            @if ($portfolio['totals'] ?? null)
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <x-a2ui.atoms.stat label="Costo acumulado">${{ number_format($portfolio['totals']['cost_basis'], 2) }}</x-a2ui.atoms.stat>
                    <x-a2ui.atoms.stat label="Valor de mercado">${{ number_format($portfolio['totals']['market_value'], 2) }}</x-a2ui.atoms.stat>
                    <x-a2ui.atoms.stat
                        label="Ganancia no realizada"
                        :tone="$portfolio['totals']['unrealized_gain'] >= 0 ? 'positive' : 'negative'"
                    >${{ number_format($portfolio['totals']['unrealized_gain'], 2) }}</x-a2ui.atoms.stat>
                </div>
            @endif

            @if ($portfolio['chart'] ?? null)
                <x-a2ui.atoms.chart-bar :chart="$portfolio['chart']" />
            @endif
        </div>
    @empty
        <p class="text-sm text-text-muted">Sin portafolios registrados.</p>
    @endforelse

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
