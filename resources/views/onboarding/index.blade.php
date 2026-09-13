<x-layouts.app :user="$user" active="asistente" title="Asistente" :show-sidebar="$isDone">
    {{-- Mientras el perfil no esté completo, la sidebar no se muestra: las 4
         preguntas son obligatorias antes de poder navegar a Educación o al
         chat real (ver isDone más abajo). --}}
    <div class="mx-auto flex min-h-0 w-full max-w-[720px] flex-1 flex-col">
        @if (session('status'))
            <x-alert :title="session('status')" class="mt-6 shrink-0" />
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="flex flex-col gap-6 py-6">
                @foreach ($messages as $message)
                    @if ($message['role'] === 'assistant')
                        <x-chat.agent-message>{{ $message['text'] }}</x-chat.agent-message>
                    @else
                        <x-chat.user-message>{{ $message['text'] }}</x-chat.user-message>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="shrink-0 pb-6">
            @if ($errors->any())
                <x-alert title="No pude leer tu respuesta" class="mb-4">{{ $errors->first('message') }}</x-alert>
            @endif

            @if ($isDone)
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-button :href="route('chat.index')" class="w-full">Hablar con Norti</x-button>
                    <x-button :href="route('education.index')" variant="secondary" class="w-full">Ver educación</x-button>
                </div>
            @elseif ($step === 'risk')
                <div class="flex flex-wrap gap-3">
                    @foreach ($riskOptions as $value => $label)
                        <x-chip :action="route('onboarding.chat')" :value="$value" :label="$label" />
                    @endforeach
                </div>
            @else
                <form method="POST" action="{{ route('onboarding.chat') }}">
                    @csrf
                    <x-chat.composer />
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
