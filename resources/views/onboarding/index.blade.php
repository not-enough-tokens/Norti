<x-layouts.base title="Bienvenida" class="bg-bg-subtle">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="flex w-full max-w-[640px] flex-col gap-6 rounded-xl border border-border-default bg-bg-surface p-8">
            <div class="flex items-center gap-3">
                <x-norti.app-mark class="size-10" />
                <span class="font-display text-lg leading-6 font-semibold text-text-primary">Norti</span>
            </div>

            @if (session('status'))
                <x-alert :title="session('status')" />
            @endif

            <div class="flex max-h-[60vh] flex-col gap-5 overflow-y-auto py-2">
                @foreach ($messages as $message)
                    @if ($message['role'] === 'assistant')
                        <x-chat.agent-message>{{ $message['text'] }}</x-chat.agent-message>
                    @else
                        <x-chat.user-message>{{ $message['text'] }}</x-chat.user-message>
                    @endif
                @endforeach
            </div>

            @if ($errors->any())
                <x-alert title="No pude leer tu respuesta">{{ $errors->first('message') }}</x-alert>
            @endif

            @if ($isDone)
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-button :href="route('education.index')" class="w-full">Continuar</x-button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button variant="secondary" class="w-full">Cerrar sesión</x-button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('onboarding.chat') }}">
                    @csrf
                    <x-chat.composer />
                </form>
            @endif
        </div>
    </main>
</x-layouts.base>
