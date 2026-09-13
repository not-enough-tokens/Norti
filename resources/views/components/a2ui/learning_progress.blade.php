@props(['props' => []])

<div>
    <div class="stat">
        <span class="label">Avance</span>
        <span class="value">{{ $props['completed_topics'] }} de {{ $props['total_topics'] }} ({{ $props['completion_percentage'] }}%)</span>
    </div>
    <div class="progress">
        <div style="width: {{ $props['completion_percentage'] }}%;"></div>
    </div>

    @if (($props['category_gaps'] ?? []) !== [])
        <p class="hint" style="margin-top: 0.5rem;">Oportunidades de aprendizaje:</p>
        @foreach ($props['category_gaps'] as $category)
            <span class="pill">{{ $category }}</span>
        @endforeach
    @endif
</div>

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
