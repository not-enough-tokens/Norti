@props(['user'])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

{{-- Top Bar (nodo 38:47), simplificada a la cuenta: el saludo ya lo da el
     primer mensaje de Norti en el chat, mostrarlo dos veces sería redundante.
     Clic en el avatar abre el único punto de cerrar sesión de la pantalla
     (antes vivía como ícono aparte en la sidebar). `<details>` da el toggle
     sin JavaScript; app.js solo lo cierra al tocar fuera o con Escape. --}}
<details data-dropdown class="group relative">
    <summary class="flex cursor-pointer list-none items-center gap-3 [&::-webkit-details-marker]:hidden">
        <span class="flex size-10 items-center justify-center rounded-full bg-bg-tint text-sm leading-4 font-medium text-text-on-tint">
            {{ $initials }}
        </span>
        <span class="text-sm leading-5 font-semibold text-text-primary">{{ $user->name }}</span>
    </summary>

    <div class="absolute top-[calc(100%+8px)] right-0 z-10 w-48 rounded-xl border border-border-default bg-bg-surface p-1.5 shadow-panel">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm leading-5 text-text-primary transition hover:bg-bg-subtle">
                <x-icon.log-out class="size-4 text-icon-default" />
                Cerrar sesión
            </button>
        </form>
    </div>
</details>
