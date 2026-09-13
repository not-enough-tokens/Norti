@props(['props' => []])

<div>
    @foreach ($props['quotes'] as $symbol => $quote)
        <div style="margin-bottom: 0.5rem;">
            <strong>{{ $symbol }}</strong>
            @if (isset($quote['error']))
                <span class="badge negative">Error</span>
                <p class="hint">{{ $quote['error'] }}</p>
            @else
                <span class="value">{{ $quote['close'] ?? '—' }}</span>
                @if (isset($quote['percent_change']))
                    <span class="badge {{ (float) $quote['percent_change'] >= 0 ? 'positive' : 'negative' }}">
                        {{ (float) $quote['percent_change'] >= 0 ? '+' : '' }}{{ $quote['percent_change'] }}%
                    </span>
                @endif
            @endif

            @if (($quote['actions'] ?? []) !== [])
                @foreach ($quote['actions'] as $action)
                    <span class="pill">{{ $action['label'] }}</span>
                @endforeach
            @endif
        </div>
    @endforeach

    @if ($props['chart'] ?? null)
        {{-- % de cambio por símbolo (Chart / Column en el contrato). --}}
        @php $bound = max(array_map('abs', array_column($props['chart']['data'], 'value') ?: [1])) ?: 1; @endphp
        <div style="margin-top: 0.5rem;">
            @foreach ($props['chart']['data'] as $bar)
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                    <span class="hint" style="width: 4rem;">{{ $bar['key'] }}</span>
                    <div style="flex: 1; background: rgba(128,128,128,0.15); border-radius: 4px; height: 0.6rem;">
                        @if ($bar['value'] !== null)
                            <div style="width: {{ min(100, abs($bar['value']) / $bound * 100) }}%; background: {{ $bar['value'] >= 0 ? '#2e7d32' : '#c62828' }}; height: 100%; border-radius: 4px;"></div>
                        @endif
                    </div>
                    <span class="hint">{{ $bar['value'] !== null ? $bar['value'].'%' : 'sin dato' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
