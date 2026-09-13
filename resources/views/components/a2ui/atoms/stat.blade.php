@props(['label', 'tone' => 'default'])

@php
    $toneClass = match ($tone) {
        'positive' => 'text-feedback-positive-text',
        'negative' => 'text-feedback-negative-text',
        default => 'text-text-primary',
    };
@endphp

<div>
    <p class="text-xs text-text-muted">{{ $label }}</p>
    <p class="font-display text-lg leading-6 font-semibold {{ $toneClass }}">{{ $slot }}</p>
</div>
