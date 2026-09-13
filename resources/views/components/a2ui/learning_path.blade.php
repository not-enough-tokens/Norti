@props(['props' => []])

@forelse ($props['topics'] ?? [] as $topic)
    @php $isRecommended = ($props['recommended_topic']['id'] ?? null) === $topic['id']; @endphp
    <div class="topic-row @if ($isRecommended) recommended @endif">
        <div>
            <span>{{ $topic['title'] }}</span>
            <span class="pill">{{ $topic['category'] }}</span>
            <span class="hint">{{ $topic['estimated_minutes'] }} min</span>
            @if ($isRecommended)
                <span class="pill" style="border-color: #1565c0; color: #1565c0;">Recomendado</span>
            @endif
        </div>
        <div>
            @if ($topic['is_completed'])
                <span class="badge positive">Completado</span>
            @else
                <span class="badge">Pendiente</span>
            @endif
            @foreach ($topic['actions'] as $action)
                <span class="pill">{{ $action['label'] }}</span>
            @endforeach
        </div>
    </div>
@empty
    <p class="hint">Sin temas disponibles.</p>
@endforelse

@if ($props['recommended_topic'] ?? null)
    <p class="hint" style="margin-top: 0.5rem;">
        Razón de la recomendación: <code>{{ $props['recommended_topic']['recommended_reason'] }}</code>
        (el agente la traduce a lenguaje natural, nunca se muestra este código al usuario).
    </p>
@endif

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
