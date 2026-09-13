@props(['active' => null])

{{-- App Sidebar (nodo 38:19): pill oscura. Ítems = rutas reales
     (Asistente, Educación, Cerrar sesión), nunca features que no existen. --}}
<nav {{ $attributes->class('flex w-20 shrink-0 flex-col items-center gap-6 rounded-full bg-bg-inverse py-6') }} aria-label="Navegación principal">
    <x-norti.app-mark />

    <div class="flex flex-col items-center gap-4">
        <a
            href="{{ route('onboarding.index') }}"
            aria-label="Asistente"
            @class([
                'flex size-12 items-center justify-center rounded-xl',
                'border-[1.5px] border-border-selected text-icon-brand' => $active === 'asistente',
                'text-icon-on-dark-muted hover:text-icon-inverse' => $active !== 'asistente',
            ])
        >
            <x-icon.message-circle class="size-[22px]" />
        </a>

        <a
            href="{{ route('education.index') }}"
            aria-label="Educación"
            @class([
                'flex size-12 items-center justify-center rounded-xl',
                'border-[1.5px] border-border-selected text-icon-brand' => $active === 'educacion',
                'text-icon-on-dark-muted hover:text-icon-inverse' => $active !== 'educacion',
            ])
        >
            <x-icon.book-open class="size-[22px]" />
        </a>
    </div>

    <div class="flex-1"></div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" aria-label="Cerrar sesión" class="flex size-12 items-center justify-center rounded-xl text-icon-on-dark-muted transition hover:text-icon-inverse">
            <x-icon.log-out class="size-[22px]" />
        </button>
    </form>
</nav>
