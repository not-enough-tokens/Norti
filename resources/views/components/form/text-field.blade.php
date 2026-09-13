@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

@php
    $invalid = $errors->has($name);
    $describedBy = $invalid ? "{$name}-error" : ($hint ? "{$name}-hint" : null);
    $isPassword = $type === 'password';
@endphp

{{-- Campo pill con la geometría del Intent Composer (nodo 38:79): borde default, selected al enfocar.
     Contraseñas llevan un botón para mostrar/ocultar (Icon/eye, nodo 14:100) -- sin JS de más, solo
     alterna el atributo `type` del input; ver el listener en resources/js/app.js. --}}
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="px-6 text-sm leading-5 font-semibold text-text-primary">{{ $label }}</label>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            @if ($invalid) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'h-14 w-full rounded-full border bg-bg-surface px-6 text-base leading-6 text-text-primary outline-none transition placeholder:text-text-muted focus:border-border-selected focus:ring-[0.5px] focus:ring-border-selected focus:ring-inset',
                'pr-14' => $isPassword,
                'border-border-default' => ! $invalid,
                'border-feedback-negative-text' => $invalid,
            ]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                data-password-toggle="{{ $name }}"
                aria-label="Mostrar contraseña"
                aria-pressed="false"
                class="absolute inset-y-0 right-5 flex items-center text-icon-muted transition hover:text-icon-default"
            >
                <x-icon.eye data-password-toggle-icon="show" class="size-[18px]" />
                <x-icon.eye-off data-password-toggle-icon="hide" class="size-[18px]" hidden />
            </button>
        @endif
    </div>

    @if ($invalid)
        <p id="{{ $name }}-error" class="px-6 text-xs leading-4 font-medium text-feedback-negative-text">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $name }}-hint" class="px-6 text-xs leading-4 text-text-muted">{{ $hint }}</p>
    @endif
</div>
