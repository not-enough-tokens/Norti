@props(['props' => []])

<x-a2ui.atoms.card title="Ruta de aprendizaje" tool="get_learning_path">
    <div class="flex flex-col">
        @forelse ($props['topics'] ?? [] as $topic)
            @php $isRecommended = ($props['recommended_topic']['id'] ?? null) === $topic['id']; @endphp
            <div @class([
                'flex items-center justify-between gap-3 border-b border-border-default py-3 last:border-b-0',
                'rounded-xl border-b-0 bg-bg-tint px-3' => $isRecommended,
            ])>
                <div>
                    <p class="font-medium text-text-primary">{{ $topic['title'] }}</p>
                    <p class="text-xs text-text-muted">{{ $topic['category'] }} · {{ $topic['estimated_minutes'] }} min</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($isRecommended)
                        <x-a2ui.atoms.badge tone="brand">Recomendado</x-a2ui.atoms.badge>
                    @endif
                    <x-a2ui.atoms.badge :tone="$topic['is_completed'] ? 'positive' : 'default'">
                        {{ $topic['is_completed'] ? 'Completado' : 'Pendiente' }}
                    </x-a2ui.atoms.badge>
                </div>
            </div>
        @empty
            <p class="text-sm text-text-muted">Sin temas disponibles.</p>
        @endforelse
    </div>

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
