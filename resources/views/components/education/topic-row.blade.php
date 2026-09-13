@props(['topic'])

{{-- Topic Row (nodo 40:182): category fija ícono y etiqueta, Completed
     cuando is_completed es true. Toda la fila abre education.show. --}}
<a
    href="{{ route('education.show', $topic) }}"
    class="flex items-center gap-3 rounded-xl border border-border-default bg-bg-surface px-4 py-3 transition hover:border-border-strong"
>
    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-bg-inverse text-icon-accent-on-dark">
        <x-dynamic-component :component="'icon.'.$topic->categoryIcon()" class="size-[22px]" />
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <div class="flex items-center gap-1 text-xs leading-4 text-text-muted">
            <x-icon.clock class="size-3.5 shrink-0" />
            <span>{{ $topic->estimated_minutes }} min</span>
            <span>·</span>
            <span class="truncate">{{ $topic->categoryLabel() }}</span>
        </div>
        <p class="truncate text-sm leading-5 font-semibold text-text-primary">{{ $topic->title }}</p>
    </div>

    @if ($topic->is_completed)
        <span class="shrink-0 rounded-full bg-feedback-positive-bg px-3 py-1 text-xs leading-4 font-medium text-feedback-positive-text">Completado</span>
    @else
        <x-icon.chevron-right class="size-4 shrink-0 text-icon-muted" />
    @endif
</a>
