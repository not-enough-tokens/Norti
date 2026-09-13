@props(['props' => []])

<div>
    <span class="badge positive">Completado</span>
    <strong>{{ $props['title'] }}</strong>
    <p class="hint">Se guardó tu progreso en este tema.</p>
</div>

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
