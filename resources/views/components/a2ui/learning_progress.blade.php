@props(['props' => []])

<x-a2ui.atoms.card title="Tu progreso" tool="get_learning_progress">
    <x-a2ui.atoms.stat label="Avance">{{ $props['completed_topics'] }} de {{ $props['total_topics'] }} ({{ $props['completion_percentage'] }}%)</x-a2ui.atoms.stat>
    <x-a2ui.atoms.progress-fill :percentage="$props['completion_percentage']" />

    @if (($props['category_gaps'] ?? []) !== [])
        <div>
            <p class="mb-1 text-xs text-text-muted">Oportunidades de aprendizaje</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($props['category_gaps'] as $category)
                    <x-a2ui.atoms.badge tone="brand">{{ $category }}</x-a2ui.atoms.badge>
                @endforeach
            </div>
        </div>
    @endif

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
