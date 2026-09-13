@props(['props' => []])

<x-a2ui.atoms.card title="Tus metas financieras" tool="get_financial_goals">
    @forelse ($props['goals'] ?? [] as $goal)
        <div class="flex flex-col gap-2 border-b border-border-default pb-4 last:border-b-0">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="font-medium text-text-primary">{{ $goal['name'] }}</p>
                    <p class="text-xs text-text-muted">{{ ucfirst($goal['goal_type']) }}</p>
                </div>

                @if (array_key_exists('is_overdue', $goal))
                    @if ($goal['is_overdue'])
                        <x-a2ui.atoms.badge tone="negative">Vencida</x-a2ui.atoms.badge>
                    @elseif ($goal['reaches_goal'])
                        <x-a2ui.atoms.badge tone="positive">En camino</x-a2ui.atoms.badge>
                    @else
                        <x-a2ui.atoms.badge tone="warning">Fuera de camino</x-a2ui.atoms.badge>
                    @endif
                @endif
            </div>

            <x-a2ui.atoms.progress-fill :percentage="$goal['progress_percentage']" />
            <p class="text-xs text-text-muted">
                {{ $goal['progress_percentage'] }}% completado
                @if (isset($goal['months_remaining']))
                    · {{ $goal['months_remaining'] }} meses restantes
                @endif
            </p>

            @if (($props['detail'] ?? null) === 'exact')
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <x-a2ui.atoms.stat label="Meta">${{ number_format($goal['target_amount'], 2) }}</x-a2ui.atoms.stat>
                    <x-a2ui.atoms.stat label="Ahorrado">${{ number_format($goal['current_amount'], 2) }}</x-a2ui.atoms.stat>

                    @if (($goal['shortfall'] ?? 0) > 0)
                        <x-a2ui.atoms.stat label="Falta" tone="negative">${{ number_format($goal['shortfall'], 2) }}</x-a2ui.atoms.stat>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-text-muted">Sin metas financieras registradas.</p>
    @endforelse

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
