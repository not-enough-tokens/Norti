@props(['props' => []])

@if (! ($props['has_profile'] ?? false))
    <p class="hint">Este usuario todavía no tiene un perfil financiero registrado.</p>
@else
    <div>
        <div class="stat">
            <span class="label">Perfil de riesgo</span>
            <span class="value">{{ ucfirst((string) $props['risk_tolerance']) }}</span>
        </div>
        <div class="stat">
            <span class="label">Horizonte</span>
            <span class="value">{{ $props['investment_horizon_months'] }} meses</span>
        </div>

        @if (($props['detail'] ?? null) === 'exact')
            <div class="stat">
                <span class="label">Ingreso mensual</span>
                <span class="value">${{ number_format((float) $props['monthly_income'], 2) }}</span>
            </div>
            <div class="stat">
                <span class="label">Gasto mensual</span>
                <span class="value">${{ number_format((float) $props['monthly_expenses'], 2) }}</span>
            </div>
            <div class="stat">
                <span class="label">Ahorro</span>
                <span class="value">${{ number_format((float) $props['savings'], 2) }}</span>
            </div>
            <div class="stat">
                <span class="label">Capacidad de ahorro mensual</span>
                <span class="value">${{ number_format((float) $props['monthly_savings_capacity'], 2) }}</span>
            </div>

            @if ($props['chart'] ?? null)
                {{-- Cascada ingresos -> gastos -> capacidad de ahorro (Chart / Waterfall en el contrato). --}}
                <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; align-items: flex-end; height: 4rem;">
                    @foreach ($props['chart']['data'] as $bar)
                        @php $pct = $props['chart']['y_axis']['domain'][1] > 0 ? abs($bar['value']) / $props['chart']['y_axis']['domain'][1] * 100 : 0; @endphp
                        <div style="flex: 1; text-align: center;">
                            <div style="height: {{ $pct }}%; background: {{ $bar['value'] < 0 ? '#c62828' : '#1565c0' }}; border-radius: 3px 3px 0 0;"></div>
                            <span class="hint">{{ $bar['key'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="stat">
                <span class="label">Categoría de ahorro</span>
                <span class="value">{{ ucfirst((string) $props['savings_rate_category']) }}</span>
            </div>
        @endif
    </div>
@endif

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.75rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
