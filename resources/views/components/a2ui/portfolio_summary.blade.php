@props(['props' => []])

@forelse ($props['portfolios'] ?? [] as $portfolio)
    <div style="margin-bottom: 1.25rem;">
        <strong>{{ $portfolio['name'] }}</strong>
        @if ($portfolio['description'])
            <div class="hint">{{ $portfolio['description'] }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Símbolo</th><th>Tipo</th><th>Cantidad</th><th>Valor de mercado</th><th>Ganancia/pérdida</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portfolio['holdings'] as $holding)
                    <tr>
                        <td>{{ $holding['symbol'] }}</td>
                        <td>{{ $holding['asset_type'] }}</td>
                        <td>{{ $holding['quantity'] }}</td>
                        <td>
                            @if ($holding['market_value'] !== null)
                                {{ $holding['currency'] }} {{ number_format($holding['market_value'], 2) }}
                            @else
                                <span class="hint">Sin cotización</span>
                            @endif
                        </td>
                        <td>
                            @if ($holding['unrealized_gain_pct'] !== null)
                                <span class="badge {{ $holding['unrealized_gain'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ $holding['unrealized_gain'] >= 0 ? '+' : '' }}{{ number_format($holding['unrealized_gain'], 2) }}
                                    ({{ $holding['unrealized_gain_pct'] }}%)
                                </span>
                            @else
                                <span class="hint">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($portfolio['totals'] ?? null)
            <div style="margin-top: 0.5rem;">
                <div class="stat">
                    <span class="label">Costo acumulado ({{ $portfolio['totals']['currency'] }})</span>
                    <span class="value">${{ number_format($portfolio['totals']['cost_basis'], 2) }}</span>
                </div>
                <div class="stat">
                    <span class="label">Valor de mercado ({{ $portfolio['totals']['currency'] }})</span>
                    <span class="value">${{ number_format($portfolio['totals']['market_value'], 2) }}</span>
                </div>
                <div class="stat">
                    <span class="label">Ganancia no realizada</span>
                    <span class="value">${{ number_format($portfolio['totals']['unrealized_gain'], 2) }}</span>
                </div>
            </div>
        @endif

        @if ($portfolio['chart'] ?? null)
            {{-- Barras horizontales de rendimiento % por posición (Chart / Bar en el contrato). --}}
            <div style="margin-top: 0.5rem;">
                @foreach ($portfolio['chart']['data'] as $bar)
                    @php
                        [$min, $max] = $portfolio['chart']['x_axis']['domain'];
                        $range = max(1, $max - $min);
                        $pct = abs($bar['value'] - min(0, $bar['value'])) / $range * 100;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                        <span class="hint" style="width: 4rem;">{{ $bar['key'] }}</span>
                        <div style="flex: 1; background: rgba(128,128,128,0.15); border-radius: 4px; height: 0.6rem;">
                            <div style="width: {{ min(100, $pct) }}%; background: {{ $bar['value'] >= 0 ? '#2e7d32' : '#c62828' }}; height: 100%; border-radius: 4px;"></div>
                        </div>
                        <span class="hint">{{ $bar['value'] }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <p class="hint">Sin portafolios registrados.</p>
@endforelse

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
