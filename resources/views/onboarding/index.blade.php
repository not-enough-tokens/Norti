<x-layouts.base title="Bienvenida" class="bg-bg-subtle">
    {{-- Placeholder del onboarding: aquí vivirá el flujo que detecta la intención (aprender, invertir, meta). --}}
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="flex w-full max-w-[480px] flex-col items-center gap-6 rounded-xl border border-border-default bg-bg-surface p-10 text-center">
            <x-norti.app-mark />

            @if (session('status'))
                <x-alert :title="session('status')" class="w-full" />
            @endif

            <div class="flex flex-col gap-2">
                <h1 class="font-display text-[28px] leading-9 font-bold tracking-[-0.5px] text-text-primary">Hola, {{ $user->name }}</h1>
                <p class="text-base leading-6 text-text-muted">Te damos la bienvenida a Norti. ¿Qué quieres lograr con tu dinero hoy?</p>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-2">
                <x-button :href="route('education.index')" class="w-full">Continuar</x-button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button variant="secondary" class="w-full">Cerrar sesión</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.base>
