<x-layouts.auth title="Crear cuenta">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-display text-[28px] leading-9 font-bold tracking-[-0.5px] text-text-primary">Crea tu cuenta</h1>
            <p class="text-sm leading-5 text-text-muted">Solo necesitamos unos datos para empezar.</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-form.text-field
                name="name"
                label="Nombre completo"
                placeholder="Ana López"
                autocomplete="name"
                required
                autofocus
            />

            <x-form.text-field
                name="email"
                type="email"
                label="Correo electrónico"
                placeholder="tucorreo@ejemplo.com"
                autocomplete="email"
                required
            />

            <x-form.text-field
                name="password"
                type="password"
                label="Contraseña"
                placeholder="Crea una contraseña"
                hint="Mínimo 8 caracteres."
                autocomplete="new-password"
                required
            />

            <x-form.text-field
                name="password_confirmation"
                type="password"
                label="Confirmar contraseña"
                placeholder="Repite tu contraseña"
                autocomplete="new-password"
                required
            />

            <x-button class="mt-1 w-full">Crear cuenta</x-button>
        </form>

        <p class="flex items-center justify-center gap-1 text-sm leading-5 text-text-muted">
            ¿Ya tienes cuenta?
            <x-button variant="ghost" :href="route('login')">Inicia sesión</x-button>
        </p>
    </div>
</x-layouts.auth>
