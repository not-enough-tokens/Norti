<x-layouts.base title="Asistente" class="bg-bg-canvas">
    {{-- Shell (nodo 5:20): sidebar oscura + barra de cuenta + conversación
         centrada con el Intent Composer abajo, como el resto del deck A2UI. --}}
    <div class="flex min-h-screen gap-6 p-6">
        <x-shell.sidebar active="asistente" />

        <div class="flex flex-1 flex-col">
            <header class="flex justify-end">
                <x-shell.topbar :user="$user" />
            </header>

            <main class="mx-auto flex w-full max-w-[720px] flex-1 flex-col justify-end gap-6 py-6">
                @if (session('status'))
                    <x-alert :title="session('status')" />
                @endif

                <div class="flex flex-1 flex-col justify-end gap-6 overflow-y-auto">
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
                    <x-button :href="route('education.index')" class="w-full">Continuar</x-button>
                @else
                    <form method="POST" action="{{ route('onboarding.chat') }}">
                        @csrf
                        <x-chat.composer />
                    </form>
                @endif
            </main>
        </div>
    </div>
</x-layouts.base>
