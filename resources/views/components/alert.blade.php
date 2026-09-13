@props(['title', 'tone' => 'highlight'])

{{-- Alert Tone=Highlight (nodo 20:6): mensaje clave. Warning / Error / Neutral existen en Figma (nodo 20:42) --
     reusan el mismo ícono (todavía no hay assets propios para esos tonos), solo cambia el color. --}}
@php
    $toneClasses = match ($tone) {
        'warning' => 'border-border-warning bg-feedback-warning-bg',
        'error' => 'border-banorte-200 bg-feedback-negative-bg',
        'neutral' => 'border-border-default bg-bg-subtle',
        default => 'border-border-highlight bg-bg-tint',
    };

    $titleClass = match ($tone) {
        'warning' => 'text-feedback-warning-text',
        'error' => 'text-feedback-negative-text',
        'neutral' => 'text-text-primary',
        default => 'text-text-on-tint',
    };
@endphp

<div role="status" {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-5 py-4 text-left text-sm leading-5', $toneClasses]) }}>
    <span class="relative size-5 shrink-0" aria-hidden="true">
        <span class="absolute inset-[8.33%]">
            <span class="absolute inset-[-6%]">
                <img src="{{ asset('images/norti/info.svg') }}" alt="" class="block size-full max-w-none">
            </span>
        </span>
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <p class="font-semibold {{ $titleClass }}">{{ $title }}</p>

        @if ($slot->isNotEmpty())
            <p class="text-text-body">{{ $slot }}</p>
        @endif
    </div>
</div>
