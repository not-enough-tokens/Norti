{{-- Agent Message (nodo 39:37): turno de Norti. El agente responde en Markdown
     (negritas, listas) -- se convierte a HTML aquí en vez de mostrar el
     ** y - crudos. html_input=escape y allow_unsafe_links=false a propósito:
     el texto viene de un LLM cuyo prompt el usuario influye, así que se trata
     como contenido no confiable, nunca como HTML literal. --}}
@php
    $html = (new League\CommonMark\CommonMarkConverter([
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
    ]))->convert((string) $slot)->getContent();
@endphp
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
        <div
            class="text-base leading-6 text-text-primary [&_a]:text-text-brand [&_a]:underline [&_code]:rounded [&_code]:bg-bg-subtle [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-sm [&_li]:mt-1 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-2 [&_strong]:font-semibold [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5"
        >{!! $html !!}</div>
    </div>
</div>
