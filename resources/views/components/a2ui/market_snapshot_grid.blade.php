@props(['props' => []])

<x-a2ui.atoms.card title="Cotizaciones de mercado" tool="get_market_snapshot">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        @foreach ($props['quotes'] as $symbol => $quote)
            <div>
                <p class="text-xs text-text-muted">{{ $symbol }}</p>

                @if (isset($quote['error']))
                    <x-a2ui.atoms.badge tone="negative">Error</x-a2ui.atoms.badge>
                @else
                    <p class="font-display text-base font-semibold text-text-primary">{{ $quote['close'] ?? '—' }}</p>

                    @if (isset($quote['percent_change']))
                        <span class="text-xs {{ (float) $quote['percent_change'] >= 0 ? 'text-feedback-positive-text' : 'text-feedback-negative-text' }}">
                            {{ (float) $quote['percent_change'] >= 0 ? '+' : '' }}{{ $quote['percent_change'] }}%
                        </span>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    @if ($props['chart'] ?? null)
        <x-a2ui.atoms.chart-column :chart="$props['chart']" />
    @endif

    <x-slot:actions>
        @foreach ($props['quotes'] as $quote)
            @foreach ($quote['actions'] ?? [] as $action)
                <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
            @endforeach
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
