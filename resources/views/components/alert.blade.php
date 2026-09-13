@props(['title'])

{{-- Alert Tone=Highlight (nodo 20:6): mensaje clave. Warning / Error / Neutral existen en Figma (nodo 20:42) y usan otro ícono. --}}
<div role="status" {{ $attributes->class('flex items-start gap-3 rounded-xl border border-border-highlight bg-bg-tint px-5 py-4 text-left text-sm leading-5') }}>
    <span class="relative size-5 shrink-0" aria-hidden="true">
        <span class="absolute inset-[8.33%]">
            <span class="absolute inset-[-6%]">
                <img src="{{ asset('images/norti/info.svg') }}" alt="" class="block size-full max-w-none">
            </span>
        </span>
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <p class="font-semibold text-text-on-tint">{{ $title }}</p>

        @if ($slot->isNotEmpty())
            <p class="text-text-body">{{ $slot }}</p>
        @endif
    </div>
</div>
