<x-layouts.base title="Tu norte financiero" class="bg-bg-inverse">
    {{-- Landing pública. Estructura inspirada en la home de Claude (marca
         arriba a la izquierda, titular gigante, tarjeta de acceso, panel de
         vista previa a la derecha), sin su header de producto -- aquí no hay
         nada más que navegar todavía --, y con la paleta e ilustración de la
         portada del Figma A2UI (círculos decorativos, blanco → coral). --}}
    <div class="relative flex min-h-screen items-center overflow-hidden px-6 py-10 lg:px-16">
        <span class="pointer-events-none absolute -top-[420px] -right-[420px] size-[820px] rounded-full bg-bg-inverse-raised"></span>
        <span class="pointer-events-none absolute -bottom-[300px] -left-[220px] size-[600px] rounded-full bg-bg-brand-deep"></span>

        <div class="relative mx-auto grid w-full max-w-6xl items-center gap-16 lg:grid-cols-2">
            <div class="flex flex-col gap-8">
                <div class="flex items-center gap-3">
                    <x-norti.app-mark />
                    <span class="font-display text-2xl leading-8 font-bold text-text-inverse">Norti</span>
                </div>

                <div>
                    <p class="font-display text-6xl leading-[1.05] font-bold tracking-tight text-text-inverse">Tu norte</p>
                    <p class="font-display text-6xl leading-[1.05] font-bold tracking-tight text-text-accent-on-dark">financiero</p>
                </div>

                <p class="max-w-md text-lg leading-7 text-text-inverse/80">
                    Cuéntale tu meta, Norti conversa contigo y arma el plan: cuánto ahorrar, dónde invertir y qué tan rápido puedes llegar.
                </p>

                <div class="flex w-full max-w-sm flex-col gap-4 rounded-2xl border border-border-on-dark p-6">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex h-12 w-full items-center justify-center rounded-full border border-border-on-dark text-sm leading-5 font-semibold text-text-inverse transition hover:border-text-inverse"
                    >
                        Iniciar sesión
                    </a>

                    <div class="flex items-center gap-3 text-xs leading-4 text-text-inverse/60">
                        <span class="h-px flex-1 bg-border-on-dark"></span>
                        O
                        <span class="h-px flex-1 bg-border-on-dark"></span>
                    </div>

                    <x-button :href="route('register')" class="w-full">Registrarse</x-button>
                </div>

                <p class="text-xs leading-4 text-text-inverse/50">Datos sintéticos — no es asesoría financiera.</p>
            </div>

            <div class="hidden flex-col gap-6 rounded-3xl bg-bg-surface p-8 shadow-panel lg:flex">
                <div class="flex items-center gap-2">
                    <span class="size-2 bg-action-primary"></span>
                    <p class="font-display text-[11px] leading-4 font-bold tracking-[0.12em] text-text-muted uppercase">Vista previa</p>
                </div>

                <p class="font-display text-xl leading-7 font-semibold text-text-primary">Tu plan en segundos</p>

                <div class="flex flex-col gap-2">
                    <div class="flex h-3 w-full overflow-hidden rounded-full">
                        <span class="h-full bg-chart-asset-accion" style="width: 55%"></span>
                        <span class="h-full bg-chart-asset-bono" style="width: 30%"></span>
                        <span class="h-full bg-chart-asset-efectivo" style="width: 15%"></span>
                    </div>

                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs leading-4 text-text-muted">
                        <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-chart-asset-accion"></span>Acciones · 55%</span>
                        <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-chart-asset-bono"></span>Bonos · 30%</span>
                        <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-chart-asset-efectivo"></span>Efectivo · 15%</span>
                    </div>
                </div>

                <x-chat.agent-message>Con $50,000 y una meta a 2 años, te sugiero un perfil moderado: 55% acciones, 30% bonos, 15% efectivo.</x-chat.agent-message>
            </div>
        </div>
    </div>
</x-layouts.base>
