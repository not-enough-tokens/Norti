@props(['tone' => 'default'])

@php
    $classes = match ($tone) {
        'positive' => 'bg-feedback-positive-bg text-feedback-positive-text',
        'negative' => 'bg-feedback-negative-bg text-feedback-negative-text',
        'warning' => 'bg-feedback-warning-bg text-feedback-warning-text',
        'brand' => 'bg-bg-tint text-text-on-tint',
        'inverse' => 'bg-bg-inverse text-text-inverse',
        default => 'bg-bg-subtle text-text-body',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $classes }}">{{ $slot }}</span>
