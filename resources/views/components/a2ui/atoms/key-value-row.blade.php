@props(['label'])

<div class="flex items-center justify-between border-b border-border-default py-2 text-sm last:border-b-0">
    <span class="text-text-muted">{{ $label }}</span>
    <span class="font-medium text-text-primary">{{ $slot }}</span>
</div>
