<x-layouts.app :user="$user" active="asistente" title="Chat">
    <div class="mx-auto flex min-h-0 w-full max-w-[720px] flex-1 flex-col">
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="flex flex-col gap-5 py-6">
                @forelse ($messages as $message)
                    @if ($message['role'] === 'assistant')
                        <x-chat.agent-message>{{ $message['text'] }}</x-chat.agent-message>

                        @foreach ($message['components'] ?? [] as $invocation)
                            @php $viewName = 'components.a2ui.'.$invocation['component']; @endphp
                            <div class="ml-11">
                                @if (\Illuminate\Support\Facades\View::exists($viewName))
                                    <x-dynamic-component :component="'a2ui.'.$invocation['component']" :props="$invocation['props']" />
                                @else
                                    <pre class="overflow-x-auto rounded-xl border border-border-default bg-bg-surface p-4 text-xs text-text-muted">{{ json_encode($invocation['props'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <x-chat.user-message>{{ $message['text'] }}</x-chat.user-message>
                    @endif
                @empty
                    <x-chat.agent-message>Hola, soy Norti. Pregúntame sobre tu perfil financiero, tu portafolio, tus metas, o educación financiera.</x-chat.agent-message>
                @endforelse
            </div>
        </div>

        <div class="shrink-0 pb-6">
            @if ($errors->any())
                <x-alert title="No pude leer tu mensaje" class="mb-4">{{ $errors->first('message') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('chat.send') }}">
                @csrf
                <x-chat.composer placeholder="Pregúntame sobre tu portafolio, tus metas, o educación financiera" />
            </form>
        </div>
    </div>
</x-layouts.app>
