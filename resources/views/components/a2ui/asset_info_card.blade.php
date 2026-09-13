@props(['props' => []])

@php
    $quote = $props['quote'] ?? [];
    $profile = $props['profile'] ?? [];
    // quote/profile son el payload de Twelve Data (o el Mock) casi sin tocar --
    // no hay un shape fijo que hardcodear, así que solo se resaltan los
    // campos más comunes y el resto se ve como una lista clave/valor.
    $highlighted = ['symbol', 'name', 'close', 'percent_change', 'currency', 'exchange'];
@endphp

<div>
    <strong>{{ $props['symbol'] }}</strong>
    @if ($props['local_asset'] ?? null)
        <span class="pill">{{ $props['local_asset']['name'] }}</span>
        <span class="pill">{{ $props['local_asset']['asset_type'] }}</span>
        <span class="pill">{{ $props['local_asset']['currency'] }}</span>
    @else
        <span class="hint">No está en el catálogo local.</span>
    @endif

    <div style="margin-top: 0.5rem;">
        @foreach ($highlighted as $key)
            @continue (! isset($quote[$key]))
            <div class="stat">
                <span class="label">{{ $key }}</span>
                <span class="value @if ($key === 'percent_change') badge {{ (float) $quote[$key] >= 0 ? 'positive' : 'negative' }} @endif">
                    {{ $quote[$key] }}@if ($key === 'percent_change')%@endif
                </span>
            </div>
        @endforeach
    </div>

    @if ($props['chart'] ?? null)
        {{-- Serie de precios (Chart / Line en el contrato). --}}
        @php
            $data = $props['chart']['data'];
            $yDomain = $props['chart']['y_axis']['domain'];
            $n = count($data);
            $points = collect($data)->values()->map(function ($point, $i) use ($n, $yDomain) {
                $x = $n > 1 ? $i / ($n - 1) * 280 + 10 : 150;
                $y = $yDomain[1] > $yDomain[0] ? 140 - (($point['y'] - $yDomain[0]) / ($yDomain[1] - $yDomain[0]) * 130) : 75;

                return "{$x},{$y}";
            })->implode(' ');
        @endphp
        <svg class="chart" viewBox="0 0 300 150" role="img" aria-label="Serie de precios">
            <polyline points="{{ $points }}" fill="none" stroke="#1565c0" stroke-width="2" vector-effect="non-scaling-stroke" />
        </svg>
        <span class="hint">{{ $data[0]['x'] }} → {{ $data[count($data) - 1]['x'] }}</span>
    @else
        <p class="hint">Sin serie de precios disponible.</p>
    @endif

    @if ($profile !== [])
        <details style="margin-top: 0.5rem;">
            <summary class="hint">Perfil de la empresa</summary>
            <table>
                @foreach ($profile as $key => $value)
                    @continue (is_array($value))
                    <tr><th>{{ $key }}</th><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        </details>
    @endif
</div>

@if (($props['actions'] ?? []) !== [])
    <div style="margin-top: 0.5rem;">
        @foreach ($props['actions'] as $action)
            <span class="badge">{{ $action['label'] }}</span>
        @endforeach
    </div>
@endif
