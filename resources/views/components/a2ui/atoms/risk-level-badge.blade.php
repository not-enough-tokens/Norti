@props(['level'])

@php
    $label = match ($level) {
        'conservative' => 'Conservador',
        'moderate' => 'Moderado',
        'aggressive' => 'Agresivo',
        default => $level !== null ? ucfirst((string) $level) : 'Sin definir',
    };

    $tone = match ($level) {
        'conservative' => 'positive',
        'moderate' => 'brand',
        'aggressive' => 'warning',
        default => 'default',
    };
@endphp

<x-a2ui.atoms.badge :tone="$tone">{{ $label }}</x-a2ui.atoms.badge>
