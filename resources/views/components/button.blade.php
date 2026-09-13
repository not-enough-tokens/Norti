@props([
    'variant' => 'primary',
    'href' => null,
])

@php
    // Button (nodo 16:106): Primary / Secondary / Ghost × Default / Hover / Disabled.
    $classes = [
        'inline-flex h-11 items-center justify-center gap-2 rounded-full px-5 text-sm leading-5 font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-border-selected',
        'bg-action-primary text-text-on-brand hover:bg-action-primary-hover disabled:bg-action-disabled disabled:text-text-disabled' => $variant === 'primary',
        'border-[1.5px] border-border-strong text-text-primary hover:bg-bg-subtle disabled:border-border-default disabled:bg-transparent disabled:text-text-disabled' => $variant === 'secondary',
        'text-text-brand hover:bg-bg-tint hover:text-text-on-tint disabled:bg-transparent disabled:text-text-disabled' => $variant === 'ghost',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
