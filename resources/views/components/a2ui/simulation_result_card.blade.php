@props(['props' => []])

<x-a2ui.atoms.card title="Simulación de inversión" tool="simulate_investment">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <x-a2ui.atoms.stat label="Monto inicial">${{ number_format($props['initial_amount'], 2) }}</x-a2ui.atoms.stat>
        <x-a2ui.atoms.stat label="Plazo">{{ $props['months'] }} meses</x-a2ui.atoms.stat>
        <div>
            <p class="text-xs text-text-muted">Perfil</p>
            <x-a2ui.atoms.risk-level-badge :level="$props['risk_profile']" />
        </div>
        <x-a2ui.atoms.stat label="Tasa anual asumida">{{ number_format($props['assumed_annual_rate'] * 100, 1) }}%</x-a2ui.atoms.stat>
        <x-a2ui.atoms.stat label="Valor proyectado">${{ number_format($props['projected_value'], 2) }}</x-a2ui.atoms.stat>
        <x-a2ui.atoms.stat label="Ganancia proyectada" tone="positive">+${{ number_format($props['projected_gain'], 2) }}</x-a2ui.atoms.stat>
    </div>

    <p class="text-xs text-text-muted">{{ $props['disclaimer'] }}</p>

    @if ($props['chart'] ?? null)
        <x-a2ui.atoms.chart-stacked-column :chart="$props['chart']" />
    @endif

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
