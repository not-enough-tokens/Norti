@props(['props' => []])

<x-a2ui.atoms.card title="Tu perfil financiero" tool="get_financial_profile">
    @if (! ($props['has_profile'] ?? false))
        <p class="text-sm text-text-muted">Todavía no tienes un perfil financiero registrado.</p>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs text-text-muted">Perfil de riesgo</p>
                <x-a2ui.atoms.risk-level-badge :level="$props['risk_tolerance']" />
            </div>
            <x-a2ui.atoms.stat label="Horizonte">{{ $props['investment_horizon_months'] }} meses</x-a2ui.atoms.stat>

            @if (($props['detail'] ?? null) === 'exact')
                <x-a2ui.atoms.stat label="Ingreso mensual">${{ number_format($props['monthly_income'], 2) }}</x-a2ui.atoms.stat>
                <x-a2ui.atoms.stat label="Gasto mensual">${{ number_format($props['monthly_expenses'], 2) }}</x-a2ui.atoms.stat>
                <x-a2ui.atoms.stat label="Ahorro">${{ number_format($props['savings'], 2) }}</x-a2ui.atoms.stat>
                <x-a2ui.atoms.stat label="Capacidad de ahorro mensual" tone="positive">${{ number_format($props['monthly_savings_capacity'], 2) }}</x-a2ui.atoms.stat>
            @else
                <x-a2ui.atoms.stat label="Categoría de ahorro">{{ ucfirst($props['savings_rate_category']) }}</x-a2ui.atoms.stat>
            @endif
        </div>

        @if ($props['chart'] ?? null)
            <x-a2ui.atoms.chart-waterfall :chart="$props['chart']" />
        @endif
    @endif

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
