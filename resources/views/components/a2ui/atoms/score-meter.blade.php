@props(['score'])

@php $pct = max(0, min(1, (float) $score)) * 100; @endphp

<div class="flex items-center gap-3">
    <div class="h-2 flex-1 rounded-full bg-chart-track">
        <div class="h-full rounded-full bg-chart-primary" style="width: {{ $pct }}%"></div>
    </div>
    <span class="font-display text-sm font-semibold text-text-primary">{{ number_format((float) $score, 2) }}</span>
</div>
