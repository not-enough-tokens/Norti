{{-- Chart / Stacked Column: capital + rendimiento por periodo (simulation_result_card). --}}
@props(['chart'])

@php
    $data = $chart['data'] ?? [];
    $yMax = max(1, $chart['y_axis']['domain'][1] ?? 1);
@endphp

<div role="img" aria-label="Capital y rendimiento acumulado por periodo">
    {{-- items-stretch (default) a propósito -- ver nota en chart-waterfall.blade.php. --}}
    <div class="flex gap-2" style="height: 5rem;">
        @foreach ($data as $period)
            @php
                $principalPct = $period['values']['principal'] / $yMax * 100;
                $gainPct = $period['values']['gain'] / $yMax * 100;
            @endphp
            <div class="flex flex-1 flex-col-reverse">
                <div class="w-full bg-chart-muted" style="height: {{ $principalPct }}%"></div>
                <div class="w-full rounded-t bg-chart-positive" style="height: {{ $gainPct }}%"></div>
            </div>
        @endforeach
    </div>

    <div class="mt-1 flex gap-2">
        @foreach ($data as $period)
            <span class="flex-1 text-center text-xs text-text-muted">{{ $period['key'] }}</span>
        @endforeach
    </div>

    <div class="mt-2 flex items-center gap-4 text-xs text-text-muted">
        <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-chart-muted"></span> Capital</span>
        <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-chart-positive"></span> Rendimiento</span>
    </div>
</div>
