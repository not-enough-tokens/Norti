<x-layouts.base title="Chat" class="bg-bg-subtle">
    <main class="mx-auto flex min-h-screen max-w-[720px] flex-col gap-6 px-6 py-12">
        <div class="flex items-center gap-3">
            <x-norti.app-mark class="size-10" />
            <span class="font-display text-lg leading-6 font-semibold text-text-primary">Norti</span>
        </div>

        <div class="flex flex-1 flex-col gap-5 overflow-y-auto py-2">
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

        @if ($errors->any())
            <x-alert title="No pude leer tu mensaje">{{ $errors->first('message') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('chat.send') }}">
            @csrf
            <x-chat.composer placeholder="Pregúntame sobre tu portafolio, tus metas, o educación financiera" />
        </form>
    </main>
</x-layouts.base>
