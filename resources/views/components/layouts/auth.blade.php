@props(['title'])

<x-layouts.base :title="$title" class="bg-bg-surface">
    <div class="flex min-h-screen">
        {{-- Panel de marca: portada (nodo 5:24) de la librería de Figma «Banorte MCP — A2UI Components». --}}
        <aside class="relative hidden w-[55%] flex-col justify-center gap-8 overflow-hidden bg-bg-inverse px-16 lg:flex xl:px-24">
            <span class="pointer-events-none absolute -top-[380px] -right-[380px] size-[760px] rounded-full bg-bg-inverse-raised"></span>
            <span class="pointer-events-none absolute -bottom-[260px] -left-[200px] size-[540px] rounded-full bg-bg-brand-deep"></span>

            <div class="relative flex items-center gap-3">
                <span class="size-2.5 bg-action-primary"></span>
                <p class="font-display text-[11px] leading-4 font-bold tracking-[0.12em] text-text-accent-on-dark uppercase">
                    HackMTY 2026 · Reto Banorte × Tec de Monterrey
                </p>
            </div>

            <p class="relative font-display text-5xl leading-[1.15] font-bold tracking-[-0.02em] xl:text-6xl">
                <span class="block text-text-inverse">Norti</span>
                <span class="block text-text-accent-on-dark">Tu norte financiero</span>
            </p>

            <p class="relative max-w-xl text-xl leading-[30px] text-text-inverse">
                Cuéntale qué quieres lograr con tu dinero: Norti consulta tus datos, los analiza y te explica cada paso.
            </p>

            <ul class="relative flex flex-wrap gap-3 text-sm leading-5">
                @foreach (['Aprende' => 'a tu ritmo', 'Analiza' => 'tu portafolio', 'Simula' => 'antes de invertir'] as $keyword => $text)
                    <li class="flex items-center gap-2 rounded-full border border-border-on-dark px-5 py-3">
                        <span class="font-semibold text-text-accent-on-dark">{{ $keyword }}</span>
                        <span class="text-text-inverse">{{ $text }}</span>
                    </li>
                @endforeach
            </ul>

            <p class="relative text-xs leading-4 text-text-inverse">Datos sintéticos — no es asesoría financiera</p>
        </aside>

        <main class="flex flex-1 flex-col items-center justify-center px-6 py-12 sm:px-12">
            <div class="flex w-full max-w-[400px] flex-col gap-10">
                <div class="flex items-center gap-3">
                    <x-norti.app-mark />
                    <span class="font-display text-[22px] leading-[30px] font-semibold text-text-primary">Norti</span>
                </div>

                {{ $slot }}

                <p class="text-xs leading-4 text-text-muted lg:hidden">Datos sintéticos — no es asesoría financiera</p>
            </div>
        </main>
    </div>
</x-layouts.base>
