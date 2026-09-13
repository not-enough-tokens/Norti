@props(['props' => []])

@forelse ($props['goals'] ?? [] as $goal)
    <div style="margin-bottom: 0.75rem; border-bottom: 1px solid rgba(128,128,128,0.2); padding-bottom: 0.5rem;">
        <div style="display: flex; justify-content: space-between;">
            <span><strong>{{ $goal['name'] }}</strong> <span class="pill">{{ $goal['goal_type'] }}</span></span>
            @if (array_key_exists('is_overdue', $goal))
                @if ($goal['is_overdue'])
                    <span class="badge negative">Vencida</span>
                @elseif ($goal['reaches_goal'])
                    <span class="badge positive">En camino</span>
                @else
                    <span class="badge" style="background: #b26a00; color: white;">Fuera de camino</span>
                @endif
            @endif
        </div>

        <div class="progress">
            <div style="width: {{ min(100, $goal['progress_percentage']) }}%;"></div>
        </div>
        <span class="hint">{{ $goal['progress_percentage'] }}% completado</span>
        @if (isset($goal['months_remaining']))
            <span class="hint">· {{ $goal['months_remaining'] }} meses restantes</span>
        @endif

        @if ($props['detail'] === 'exact')
            <div style="margin-top: 0.25rem;">
                <span class="stat"><span class="label">Meta</span><span class="value">${{ number_format($goal['target_amount'], 2) }}</span></span>
                <span class="stat"><span class="label">Ahorrado</span><span class="value">${{ number_format($goal['current_amount'], 2) }}</span></span>
                @if (($goal['shortfall'] ?? 0) > 0)
                    <span class="stat"><span class="label">Falta</span><span class="value">${{ number_format($goal['shortfall'], 2) }}</span></span>
                @endif
            </div>
        @endif

        @foreach ($goal['actions'] as $action)
            <span class="pill">{{ $action['label'] }}</span>
        @endforeach
    </div>
@empty
    <p class="hint">Sin metas financieras registradas.</p>
@endforelse

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
