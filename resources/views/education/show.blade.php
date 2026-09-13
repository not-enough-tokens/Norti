@php
    $reasonMessages = [
        'no_goals' => 'Todavía no registras una meta financiera. Este tema te ayuda a decidir por dónde empezar.',
        'concentrated_portfolio' => 'Tu portafolio está concentrado en un solo activo. Este tema explica por qué diversificar ayuda a administrar el riesgo.',
        'conservative_profile_with_stocks' => 'Tu perfil es conservador pero tienes acciones en tu portafolio. Este tema te ayuda a entender ese riesgo.',
        'default' => 'Es el siguiente paso natural en tu ruta de aprendizaje.',
    ];
@endphp

<x-layouts.base :title="$topic->title" class="bg-bg-canvas">
    <div class="flex min-h-screen gap-6 p-6">
        <x-shell.sidebar active="educacion" />

        <div class="flex flex-1 flex-col gap-6">
            <header class="flex items-center justify-between">
                <a href="{{ route('education.index') }}" class="text-sm leading-5 font-semibold text-text-muted transition hover:text-text-primary">
                    ← Volver a educación
                </a>

                <x-shell.topbar :user="$user" />
            </header>

            <main class="mx-auto w-full max-w-[560px] pb-10">
                @if (session('status'))
                    <x-alert :title="session('status')" class="mb-6" />
                @endif

                <div class="flex flex-col gap-4 rounded-2xl border border-border-default bg-bg-surface p-6 shadow-panel">
                    <div class="flex items-center gap-2">
                        <span class="size-2 bg-action-primary"></span>
                        <p class="font-display text-[11px] leading-4 font-bold tracking-[0.12em] text-text-brand uppercase">
                            Educación financiera · {{ $topic->categoryLabel() }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <h1 class="font-display text-[22px] leading-[30px] font-semibold text-text-primary">{{ $topic->title }}</h1>
                        <p class="text-sm leading-5 text-text-muted">{{ $topic->description }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="flex items-center gap-1 text-xs leading-4 text-text-muted">
                            <x-icon.clock class="size-3.5" />
                            {{ $topic->estimated_minutes }} min
                        </span>

                        <span class="rounded-full bg-bg-subtle px-3 py-1 text-xs leading-4 font-medium text-text-body">
                            {{ $topic->difficultyLabel() }}
                        </span>

                        @if ($isCompleted)
                            <span class="rounded-full bg-feedback-positive-bg px-3 py-1 text-xs leading-4 font-medium text-feedback-positive-text">
                                Completado
                            </span>
                        @endif
                    </div>

                    @if ($recommendedReason)
                        <x-alert title="Recomendado para ti">{{ $reasonMessages[$recommendedReason] ?? $reasonMessages['default'] }}</x-alert>
                    @endif

                    <div class="flex flex-col gap-2">
                        <p class="text-sm leading-5 font-semibold text-text-primary">Contenido</p>
                        <p class="text-sm leading-5 text-text-body">{{ $topic->content }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @unless ($isCompleted)
                            <form method="POST" action="{{ route('education.complete', $topic) }}">
                                @csrf
                                <x-button class="w-full sm:w-auto">Marcar como completado</x-button>
                            </form>
                        @endunless

                        <x-button variant="secondary" :href="route('onboarding.index', ['topic' => $topic->slug])" class="w-full sm:w-auto">
                            {{ $isCompleted ? 'Repasar con el asistente' : 'Preguntar al asistente' }}
                            <x-icon.chevron-right class="size-4" />
                        </x-button>
                    </div>

                    <p class="text-xs leading-4 text-text-muted">Contenido educativo · no es asesoría financiera</p>
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
