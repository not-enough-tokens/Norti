<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Banorte MCP — Prototipo A2UI en el chat</title>
<style>
    :root { color-scheme: light dark; }
    body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; }
    h1 { font-size: 1.3rem; }
    .hint { color: #888; font-size: 0.85rem; }
    label { display: block; margin-top: 1rem; font-weight: 600; font-size: 0.9rem; }
    input, textarea { width: 100%; box-sizing: border-box; padding: 0.5rem; font-size: 0.95rem; margin-top: 0.25rem; }
    button { margin-top: 1rem; padding: 0.6rem 1.2rem; font-size: 0.95rem; cursor: pointer; }
    .answer { margin-top: 1.5rem; border: 1px solid #2e7d32; border-radius: 8px; padding: 1rem; }
    .invocation { margin-top: 1rem; border: 1px solid #555; border-radius: 8px; padding: 1rem; }
    .invocation .tag { font-size: 0.75rem; color: #888; }
    .invocation .missing { font-size: 0.8rem; color: #b26a00; margin-bottom: 0.5rem; }
    pre { white-space: pre-wrap; word-break: break-word; background: rgba(128,128,128,0.1); padding: 0.75rem; border-radius: 6px; }
    table { border-collapse: collapse; width: 100%; font-size: 0.85rem; }
    th, td { text-align: left; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(128,128,128,0.25); }
    .stat { display: inline-block; margin: 0.25rem 1rem 0.25rem 0; }
    .stat .label { font-size: 0.75rem; color: #888; display: block; }
    .stat .value { font-size: 1.05rem; font-weight: 600; }
    .badge { display: inline-block; padding: 0.1rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; background: rgba(128,128,128,0.2); }
    .badge.positive { background: #2e7d32; color: white; }
    .badge.negative { background: #c62828; color: white; }
    .progress { background: rgba(128,128,128,0.2); border-radius: 999px; height: 0.5rem; overflow: hidden; margin-top: 0.35rem; }
    .progress > div { background: #1565c0; height: 100%; }
</style>
</head>
<body>
    <h1>Banorte MCP — Prototipo A2UI en el chat</h1>
    <p class="hint">
        Solo para desarrollo. Corre <code>BanorteMcpAgent</code> de verdad y muestra, además de su respuesta en
        texto, el <code>component</code>/<code>props</code> de cada tool que invocó -- capturado vía
        <code>App\Ai\Listeners\CaptureStructuredToolResults</code> (evento <code>Laravel\Ai\Events\ToolInvoked</code>
        del SDK), sin tocar el agente. Solo <code>financial_profile_card</code> y <code>portfolio_summary</code>
        tienen una vista real; el resto cae en un volcado JSON.
    </p>

    <form method="POST" action="{{ route('mcp.debug-chat.submit') }}">
        @csrf
        <label for="email">Usuario (email con token Passport)</label>
        <input id="email" name="email" type="text" value="{{ $email }}">

        <label for="question">Pregunta para el agente</label>
        <textarea id="question" name="question" rows="2" placeholder="¿Cuál es mi perfil de riesgo?">{{ $question }}</textarea>

        <button type="submit">Preguntar</button>
    </form>

    @if ($question !== null && $answer !== null)
        <div class="answer">
            <strong>Respuesta del agente:</strong>
            <p>{{ $answer }}</p>
        </div>

        @forelse ($invocations as $invocation)
            @php $viewName = 'components.a2ui.'.$invocation['component']; @endphp
            <div class="invocation">
                <span class="tag">{{ $invocation['tool'] }} → component: <code>{{ $invocation['component'] }}</code></span>
                @if (\Illuminate\Support\Facades\View::exists($viewName))
                    <x-dynamic-component :component="'a2ui.'.$invocation['component']" :props="$invocation['props']" />
                @else
                    <p class="missing">Sin vista Blade todavía para este componente -- volcado crudo de <code>props</code>:</p>
                    <pre>{{ json_encode($invocation['props'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        @empty
            <p class="hint">El agente no invocó ninguna tool para esta pregunta.</p>
        @endforelse
    @endif
</body>
</html>
