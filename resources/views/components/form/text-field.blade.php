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
@endphp

{{-- Campo pill con la geometría del Intent Composer (nodo 38:79): borde default, selected al enfocar. --}}
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="px-6 text-sm leading-5 font-semibold text-text-primary">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($invalid) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'h-14 w-full rounded-full border bg-bg-surface px-6 text-base leading-6 text-text-primary outline-none transition placeholder:text-text-muted focus:border-border-selected focus:ring-[0.5px] focus:ring-border-selected focus:ring-inset',
            'border-border-default' => ! $invalid,
            'border-feedback-negative-text' => $invalid,
        ]) }}
    >

    @if ($invalid)
        <p id="{{ $name }}-error" class="px-6 text-xs leading-4 font-medium text-feedback-negative-text">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $name }}-hint" class="px-6 text-xs leading-4 text-text-muted">{{ $hint }}</p>
    @endif
</div>
