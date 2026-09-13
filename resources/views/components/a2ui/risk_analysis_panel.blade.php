@props(['props' => []])

@if (! ($props['has_holdings'] ?? false))
    <p class="hint">Sin holdings todavía -- no hay distribución que analizar.</p>
@else
    <div>
        @foreach ($props['allocation_by_asset_type'] as $type => $pct)
            @php $recommended = $props['recommended_allocation_by_asset_type'][$type] ?? null; @endphp
            <div style="margin-top: 0.35rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                    <span>{{ ucfirst($type) }}</span>
                    <span>{{ $pct }}%@if ($recommended !== null) <span class="hint">(recomendado: {{ $recommended }}%)</span>@endif</span>
                </div>
                <div class="progress">
                    <div style="width: {{ $pct }}%;"></div>
                </div>
            </div>
        @endforeach

        <div style="margin-top: 0.75rem;">
            <div class="stat">
                <span class="label">Índice de diversificación</span>
                <span class="value">{{ $props['diversification_score'] }}</span>
            </div>
        </div>

        @if ($props['concentration_warning'] ?? false)
            <div class="alert warning">⚠ Concentración alta en un solo tipo de activo.</div>
        @endif

        @if (($props['priced_with_live_market_data'] ?? true) === false)
            <div class="alert neutral">Alguna posición no se valuó con precio de mercado en vivo.</div>
        @endif
    </div>
@endif

@if ($props['chart'] ?? null)
    {{-- Dispersión riesgo/rendimiento de los 3 perfiles (Chart / Scatter en el contrato). --}}
    @php
        $xDomain = $props['chart']['x_axis']['domain'];
        $yDomain = $props['chart']['y_axis']['domain'];
        $toPx = fn ($v, $domain, $size) => $domain[1] > $domain[0] ? ($v - $domain[0]) / ($domain[1] - $domain[0]) * $size : 0;
    @endphp
    <svg class="chart" viewBox="0 0 300 150" role="img" aria-label="Riesgo contra rendimiento esperado por perfil">
        <line x1="10" y1="140" x2="290" y2="140" stroke="currentColor" stroke-opacity="0.3" />
        <line x1="10" y1="10" x2="10" y2="140" stroke="currentColor" stroke-opacity="0.3" />
        @foreach ($props['chart']['data'] as $point)
            @php
                $x = 10 + $toPx($point['x'], $xDomain, 280);
                $y = 140 - $toPx($point['y'], $yDomain, 130);
            @endphp
            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ ($point['emphasis'] ?? null) ? 6 : 4 }}" fill="{{ ($point['emphasis'] ?? null) ? '#1565c0' : 'currentColor' }}" />
            <text x="{{ $x }}" y="{{ $y - 8 }}" font-size="9" text-anchor="middle" fill="currentColor">{{ $point['key'] }}</text>
        @endforeach
        @if ($props['chart']['reference_x'] ?? null)
            @php $refX = 10 + $toPx($props['chart']['reference_x']['value'], $xDomain, 280); @endphp
            <line x1="{{ $refX }}" y1="10" x2="{{ $refX }}" y2="140" stroke="#c62828" stroke-dasharray="4" />
            <text x="{{ $refX }}" y="10" font-size="9" fill="#c62828">Tu portafolio</text>
        @endif
    </svg>
@endif

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
