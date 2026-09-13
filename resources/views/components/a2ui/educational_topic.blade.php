@props(['props' => []])

<x-a2ui.atoms.card :title="$props['title']" tool="get_educational_topic">
    <div class="flex flex-wrap items-center gap-2">
        <x-a2ui.atoms.badge tone="brand">{{ $props['category'] }}</x-a2ui.atoms.badge>
        <x-a2ui.atoms.badge>{{ ucfirst($props['difficulty']) }}</x-a2ui.atoms.badge>
        <span class="text-xs text-text-muted">{{ $props['estimated_minutes'] }} min</span>

        @if ($props['is_completed'])
            <x-a2ui.atoms.badge tone="positive">Completado</x-a2ui.atoms.badge>
        @endif
    </div>

    <p class="text-sm text-text-body">{{ $props['description'] }}</p>

    <details class="text-sm">
        <summary class="cursor-pointer text-text-muted">Contenido completo</summary>
        <p class="mt-2 text-text-body">{{ $props['content'] }}</p>
    </details>

    <x-slot:actions>
        @foreach ($props['actions'] ?? [] as $action)
            <x-a2ui.atoms.option-chip>{{ $action['label'] }}</x-a2ui.atoms.option-chip>
        @endforeach
    </x-slot:actions>
</x-a2ui.atoms.card>
