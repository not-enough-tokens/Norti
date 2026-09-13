@props(['user'])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

{{-- Top Bar (nodo 38:47), simplificada a la cuenta: el saludo ya lo da el
     primer mensaje de Norti en el chat, mostrarlo dos veces sería redundante. --}}
<div class="flex items-center gap-3">
    <span class="flex size-10 items-center justify-center rounded-full bg-bg-tint text-sm leading-4 font-medium text-text-on-tint">
        {{ $initials }}
    </span>
    <span class="text-sm leading-5 font-semibold text-text-primary">{{ $user->name }}</span>
</div>
