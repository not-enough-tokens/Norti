@props(['props' => []])

@php
    $quote = $props['quote'] ?? [];
    $profile = $props['profile'] ?? [];
    $title = $props['local_asset']['name'] ?? $props['symbol'];
@endphp

<x-a2ui.atoms.card :title="$title" tool="get_asset_information">
    <div class="flex flex-wrap items-center gap-2">
        <x-a2ui.atoms.badge>{{ $props['symbol'] }}</x-a2ui.atoms.badge>

        @if ($props['local_asset'] ?? null)
            <x-a2ui.atoms.badge tone="brand">{{ ucfirst($props['local_asset']['asset_type']) }}</x-a2ui.atoms.badge>
            <x-a2ui.atoms.badge>{{ $props['local_asset']['currency'] }}</x-a2ui.atoms.badge>
        @else
            <span class="text-xs text-text-muted">No está en el catálogo local.</span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        @if (isset($quote['close']))
            <x-a2ui.atoms.stat label="Último cierre">{{ $quote['close'] }}</x-a2ui.atoms.stat>
        @endif

        @if (isset($quote['percent_change']))
            <x-a2ui.atoms.stat label="Variación" :tone="(float) $quote['percent_change'] >= 0 ? 'positive' : 'negative'">
                {{ (float) $quote['percent_change'] >= 0 ? '+' : '' }}{{ $quote['percent_change'] }}%
            </x-a2ui.atoms.stat>
        @endif

        @if (isset($quote['exchange']))
            <x-a2ui.atoms.stat label="Bolsa">{{ $quote['exchange'] }}</x-a2ui.atoms.stat>
        @endif
    </div>

    @if ($props['chart'] ?? null)
        <x-a2ui.atoms.chart-line :chart="$props['chart']" />
    @else
        <p class="text-sm text-text-muted">Sin serie de precios disponible.</p>
    @endif

    @if ($profile !== [])
        <details class="text-sm">
            <summary class="cursor-pointer text-text-muted">Perfil de la empresa</summary>
            <div class="mt-2 flex flex-col">
                @foreach ($profile as $key => $value)
                    @continue (is_array($value))
                    <x-a2ui.atoms.key-value-row :label="$key">{{ $value }}</x-a2ui.atoms.key-value-row>
                @endforeach
            </div>
        </details>
    @endif

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
