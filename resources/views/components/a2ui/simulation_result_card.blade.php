@props(['props' => []])

<div>
    <div class="stat">
        <span class="label">Monto inicial</span>
        <span class="value">${{ number_format($props['initial_amount'], 2) }}</span>
    </div>
    <div class="stat">
        <span class="label">Plazo</span>
        <span class="value">{{ $props['months'] }} meses</span>
    </div>
    <div class="stat">
        <span class="label">Perfil</span>
        <span class="value">{{ ucfirst($props['risk_profile']) }}</span>
    </div>
    <div class="stat">
        <span class="label">Tasa anual asumida</span>
        <span class="value">{{ number_format($props['assumed_annual_rate'] * 100, 1) }}%</span>
    </div>
    <div class="stat">
        <span class="label">Valor proyectado</span>
        <span class="value">${{ number_format($props['projected_value'], 2) }}</span>
    </div>
    <div class="stat">
        <span class="label">Ganancia proyectada</span>
        <span class="value badge positive">+${{ number_format($props['projected_gain'], 2) }}</span>
    </div>

    <p class="hint">{{ $props['disclaimer'] }}</p>

    @if ($props['chart'] ?? null)
        {{-- Columnas apiladas capital + rendimiento (Chart / Stacked Column en el contrato). --}}
        @php $yMax = $props['chart']['y_axis']['domain'][1] ?: 1; @endphp
        <div style="display: flex; gap: 0.4rem; align-items: flex-end; height: 6rem; margin-top: 0.75rem;">
            @foreach ($props['chart']['data'] as $period)
                @php
                    $principalPct = $period['values']['principal'] / $yMax * 100;
                    $gainPct = $period['values']['gain'] / $yMax * 100;
                @endphp
                <div style="flex: 1; text-align: center;">
                    <div style="display: flex; flex-direction: column-reverse; height: 5rem;">
                        <div style="height: {{ $principalPct }}%; background: #888;"></div>
                        <div style="height: {{ $gainPct }}%; background: #2e7d32;"></div>
                    </div>
                    <span class="hint">{{ $period['key'] }}</span>
                </div>
            @endforeach
        </div>
        <span class="hint">■ capital &nbsp; ■ rendimiento</span>
    @endif
</div>

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
