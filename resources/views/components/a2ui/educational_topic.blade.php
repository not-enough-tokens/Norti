@props(['props' => []])

<div>
    <strong>{{ $props['title'] }}</strong>
    <span class="pill">{{ $props['category'] }}</span>
    <span class="pill">{{ $props['difficulty'] }}</span>
    <span class="hint">{{ $props['estimated_minutes'] }} min</span>
    @if ($props['is_completed'])
        <span class="badge positive">Completado</span>
    @endif

    <p>{{ $props['description'] }}</p>

    <details>
        <summary class="hint">Contenido completo</summary>
        <p>{{ $props['content'] }}</p>
    </details>
</div>

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
