<x-layouts.app :user="$user" active="educacion" title="Educación financiera">
    <div class="mx-auto min-h-0 w-full max-w-[720px] flex-1 overflow-y-auto">
        <div class="flex flex-col gap-8 pb-10">
            @if (session('status'))
                <x-alert :title="session('status')" class="mt-6" />
            @endif

            <div class="mt-6 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <span class="size-2 bg-action-primary"></span>
                    <p class="font-display text-[11px] leading-4 font-bold tracking-[0.12em] text-text-brand uppercase">Tu ruta de aprendizaje</p>
                </div>
                <h1 class="font-display text-[22px] leading-[30px] font-semibold text-text-primary">Aprende a tomar mejores decisiones</h1>
                <p class="text-sm leading-5 text-text-muted">Temas cortos en el orden sugerido.</p>
            </div>

            <x-education.progress-meter
                :completed="$progress['completed_topics']"
                :total="$progress['total_topics']"
                :percentage="$progress['completion_percentage']"
            />

            <div class="flex flex-col gap-3">
                @forelse ($learningPath as $topic)
                    <x-education.topic-row :topic="$topic" />
                @empty
                    <p class="text-sm leading-5 text-text-muted">No hay temas disponibles.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
