@props(['props' => []])

<x-a2ui.atoms.card :title="$props['title']" tool="mark_topic_completed">
    <div class="flex items-center gap-2">
        <x-a2ui.atoms.badge tone="positive">Completado</x-a2ui.atoms.badge>
        <span class="text-sm text-text-body">Se guardó tu progreso en este tema.</span>
    </div>

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
