<x-layouts.auth title="Iniciar sesión">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-display text-[28px] leading-9 font-bold tracking-[-0.5px] text-text-primary">Inicia sesión</h1>
            <p class="text-sm leading-5 text-text-muted">Ingresa con tu correo y contraseña para continuar.</p>
        </div>

        @if (session('status'))
            <x-alert :title="session('status')">Inicia sesión con tu correo y contraseña para continuar.</x-alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-form.text-field
                name="email"
                type="email"
                label="Correo electrónico"
                placeholder="tucorreo@ejemplo.com"
                autocomplete="email"
                required
                autofocus
            />

            <x-form.text-field
                name="password"
                type="password"
                label="Contraseña"
                placeholder="Tu contraseña"
                autocomplete="current-password"
                required
            />

            <label class="flex items-center gap-3 px-6 text-sm leading-5 text-text-body">
                <input type="checkbox" name="remember" class="size-4 accent-action-primary" @checked(old('remember'))>
                Recordarme
            </label>

            <x-button class="mt-1 w-full">Iniciar sesión</x-button>
        </form>

        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 text-xs leading-4 text-text-muted">
                <span class="h-px flex-1 bg-border-default"></span>
                ¿Aún no tienes cuenta?
                <span class="h-px flex-1 bg-border-default"></span>
            </div>

            <x-button variant="secondary" :href="route('register')" class="w-full">Crear una cuenta</x-button>
        </div>
    </div>
</x-layouts.auth>
