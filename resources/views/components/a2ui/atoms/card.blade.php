{{-- A2UI Card (frame, ver docs/architecture/a2ui-components.md#átomos-marco-y-shell): eyebrow, badge
     «vía {tool}», título, slot de contenido, acciones, pie de datos sintéticos. --}}
@props(['title', 'tool'])

<div class="rounded-2xl border border-border-default bg-bg-surface p-6 shadow-card">
    <div class="mb-4 flex items-center justify-between gap-2">
        <span class="font-display text-[11px] font-bold tracking-[0.12em] text-text-muted uppercase">Componente generado</span>
        <span class="rounded-full bg-bg-subtle px-2.5 py-0.5 text-[11px] font-medium text-text-muted">vía {{ $tool }}</span>
    </div>

    <h3 class="mb-4 font-display text-lg leading-6 font-semibold text-text-primary">{{ $title }}</h3>

    <div class="flex flex-col gap-4">
        {{ $slot }}
    </div>

    @if ($actions->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2 border-t border-border-default pt-4">
            {{ $actions }}
        </div>
    @endif

    <p class="mt-4 text-xs text-text-muted">Datos sintéticos · no es asesoría financiera</p>
</div>
