@props(['placeholder' => '¿Qué quieres lograr con tu dinero?'])

{{-- Intent Composer (nodo 38:79): campo pill con botón de enviar circular. --}}
<div class="flex w-full items-center gap-3 rounded-full border border-border-default bg-bg-surface py-2 pr-2 pl-6 focus-within:border-border-selected">
    <input
        type="text"
        name="message"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        autofocus
        required
        class="min-w-0 flex-1 border-none bg-transparent text-base leading-6 text-text-primary outline-none placeholder:text-text-muted"
    >

    <button type="submit" aria-label="Enviar" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-action-primary transition hover:bg-action-primary-hover">
        <span class="relative size-[18px]">
            <span class="absolute inset-[8.33%]">
                <span class="absolute inset-[-6.67%]">
                    <img src="{{ asset('images/norti/send.svg') }}" alt="" class="block size-full max-w-none">
                </span>
            </span>
        </span>
    </button>
</div>
