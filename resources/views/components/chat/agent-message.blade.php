{{-- Agent Message (nodo 39:37): turno de Norti. --}}
<div class="flex w-full items-start gap-3">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-bg-inverse" aria-hidden="true">
        <span class="relative size-4">
            <span class="absolute inset-[12.5%]">
                <span class="absolute inset-[-8.33%]">
                    <img src="{{ asset('images/norti/message-circle.svg') }}" alt="" class="block size-full max-w-none">
                </span>
            </span>
        </span>
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <p class="text-xs leading-4 font-medium text-text-muted">Norti</p>
        <p class="text-base leading-6 whitespace-pre-line text-text-primary">{{ $slot }}</p>
    </div>
</div>
