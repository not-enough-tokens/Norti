{{-- Option Chip: hoy solo muestra la sugerencia (props.actions[]) -- todavía no re-invoca la tool. --}}
@props([])

<span class="inline-flex items-center rounded-full border border-border-default px-3 py-1.5 text-xs font-medium text-text-body">
    {{ $slot }}
</span>
