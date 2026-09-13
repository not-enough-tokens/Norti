@props(['action', 'value', 'label'])

{{-- Option Chip (nodo 16:150), estado Default: elegir de inmediato avanza la
     conversación, así que no necesita un estado "Selected" que sobreviva al
     redirect. --}}
<form method="POST" action="{{ $action }}">
    @csrf
    <input type="hidden" name="message" value="{{ $value }}">
    <button
        type="submit"
        class="rounded-full border border-border-default bg-bg-surface px-4 py-2 text-sm leading-5 font-semibold text-text-primary transition hover:border-border-strong"
    >
        {{ $label }}
    </button>
</form>
