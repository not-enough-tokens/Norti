@props(['active' => null])

{{-- App Sidebar (nodo 38:19): pill oscura. El mark de Norti hace doble
     función de marca y de acceso al Asistente -- tener además un ítem de
     nav idéntico (mismo glyph, en chico) al lado era redundante. Cerrar
     sesión ya no vive aquí: se movió al pop-up de la cuenta en la Top Bar. --}}
<nav {{ $attributes->class('flex w-20 shrink-0 flex-col items-center gap-6 rounded-full bg-bg-inverse py-6') }} aria-label="Navegación principal">
    <a href="{{ route('onboarding.index') }}" aria-label="Asistente" aria-current="{{ $active === 'asistente' ? 'page' : 'false' }}">
        <x-norti.app-mark />
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

    <div class="flex-1"></div>
</nav>
