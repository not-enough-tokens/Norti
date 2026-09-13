@props([
    'user',
    'active' => null,
    'title' => null,
    'showSidebar' => true,
])

{{-- Shell de la app (nodo 5:20) fijo al viewport: sidebar y Top Bar del
     mismo tamaño siempre ("sticky"), scroll solo dentro de $slot. Sin esto,
     la página entera crecía con la conversación y arrastraba consigo la
     sidebar y los botones de acción, que dejaban de estar a la vista.
     `showSidebar=false` la oculta -- se usa durante el onboarding a medio
     llenar, para no dejar salir de las preguntas fijas antes de terminarlas. --}}
<x-layouts.base :title="$title" class="bg-bg-canvas">
    <div class="flex h-screen gap-6 overflow-hidden p-6">
        @if ($showSidebar)
            <x-shell.sidebar :active="$active" class="shrink-0" />
        @endif

        <div class="flex min-w-0 flex-1 flex-col gap-6">
            <header class="flex shrink-0 justify-end">
                <x-shell.topbar :user="$user" />
            </header>

            <div class="flex min-h-0 flex-1 flex-col">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.base>
