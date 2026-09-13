<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Banorte MCP — Tester local</title>
<style>
    :root { color-scheme: light dark; }
    body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
    h1 { font-size: 1.3rem; }
    .hint { color: #888; font-size: 0.85rem; margin-top: -0.5rem; }
    label { display: block; margin-top: 1rem; font-weight: 600; font-size: 0.9rem; }
    input, select, textarea { width: 100%; box-sizing: border-box; padding: 0.5rem; font-family: monospace; font-size: 0.9rem; margin-top: 0.25rem; }
    textarea { height: 6rem; }
    button { margin-top: 1rem; padding: 0.6rem 1.2rem; font-size: 0.95rem; cursor: pointer; }
    .result { margin-top: 1.5rem; border-radius: 8px; padding: 1rem; border: 1px solid #444; }
    .result.ok { border-color: #2e7d32; }
    .result.error { border-color: #c62828; }
    .badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
    .badge.ok { background: #2e7d32; color: white; }
    .badge.error { background: #c62828; color: white; }
    pre { white-space: pre-wrap; word-break: break-word; background: rgba(128,128,128,0.1); padding: 0.75rem; border-radius: 6px; }
    .session { font-size: 0.8rem; color: #888; margin-top: 0.5rem; }
    .row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
    .row button { margin-top: 0; }
    .secondary { background: transparent; border: 1px solid #666; }
    hr { border: none; border-top: 1px solid #333; margin: 1.5rem 0; }
</style>
</head>
<body>
    <h1>Banorte MCP — Tester local</h1>
    <p class="hint">Solo para desarrollo. Llama cualquiera de las 6 tools contra <code>/mcp/banorte</code> en este mismo origen (sin CORS, sin proxies externos).</p>

    <div class="row">
        <button id="seed" class="secondary" type="button">Sembrar datos de prueba + generar token</button>
        <span class="hint" id="seed-info"></span>
    </div>

    <label for="token">Bearer token</label>
    <input id="token" type="text" placeholder="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...">

    <hr>

    <label for="tool">Tool</label>
    <select id="tool"></select>

    <label for="args">Argumentos (JSON)</label>
    <textarea id="args"></textarea>

    <div class="row">
        <button id="run" type="button">Ejecutar</button>
        <button id="run-all" class="secondary" type="button">Ejecutar las 6 tools</button>
    </div>
    <div class="session" id="session-info"></div>

    <div id="results"></div>

<script>
const TOOLS = {
    get_financial_profile: { detail: "summary" },
    get_portfolio: {},
    analyze_portfolio: {},
    get_asset_information: { symbol: "AAPL" },
    get_market_snapshot: { symbols: ["AAPL", "MSFT"] },
    simulate_investment: { amount: 10000, months: 12, risk_profile: "moderate" },
};

const toolSelect = document.getElementById('tool');
const argsBox = document.getElementById('args');
const tokenInput = document.getElementById('token');
const results = document.getElementById('results');
const sessionInfo = document.getElementById('session-info');

for (const name of Object.keys(TOOLS)) {
    const opt = document.createElement('option');
    opt.value = name;
    opt.textContent = name;
    toolSelect.appendChild(opt);
}

function fillArgs() {
    argsBox.value = JSON.stringify(TOOLS[toolSelect.value], null, 2);
}
toolSelect.addEventListener('change', fillArgs);
fillArgs();

tokenInput.value = localStorage.getItem('banorte_mcp_token') || '';

let sessionId = null;

async function mcpRequest(token, body) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json, text/event-stream',
        'Authorization': 'Bearer ' + token,
    };
    if (sessionId) headers['Mcp-Session-Id'] = sessionId;

    const resp = await fetch('/mcp/banorte', { method: 'POST', headers, body: JSON.stringify(body) });
    const newSession = resp.headers.get('Mcp-Session-Id');
    if (newSession) sessionId = newSession;

    const text = await resp.text();
    let json;
    try { json = JSON.parse(text); } catch { json = { raw: text }; }
    return { status: resp.status, json };
}

async function ensureSession(token) {
    if (sessionId) return;
    await mcpRequest(token, {
        jsonrpc: '2.0', id: 'init', method: 'initialize',
        params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'banorte-debug-tester', version: '1.0' } },
    });
    sessionInfo.textContent = 'Sesión: ' + sessionId;
}

async function runOne(token, toolName, args) {
    const box = document.createElement('div');
    box.className = 'result';
    box.innerHTML = '<strong>' + toolName + '</strong> — ejecutando...';
    results.prepend(box);

    try {
        await ensureSession(token);
        const { status, json } = await mcpRequest(token, {
            jsonrpc: '2.0', id: Date.now() + '-' + toolName, method: 'tools/call',
            params: { name: toolName, arguments: args },
        });

        const isError = json?.result?.isError ?? (status >= 400);
        box.className = 'result ' + (isError ? 'error' : 'ok');
        const badge = '<span class="badge ' + (isError ? 'error' : 'ok') + '">' + (isError ? 'ERROR' : 'OK') + '</span>';
        const structured = json?.result?.structuredContent;
        const text = json?.result?.content?.[0]?.text ?? JSON.stringify(json, null, 2);

        box.innerHTML = '<strong>' + toolName + '</strong> ' + badge + ' <span class="hint">HTTP ' + status + '</span>'
            + '<pre>' + escapeHtml(structured ? JSON.stringify(structured, null, 2) : text) + '</pre>';
    } catch (e) {
        box.className = 'result error';
        box.innerHTML = '<strong>' + toolName + '</strong> <span class="badge error">ERROR</span><pre>' + escapeHtml(String(e)) + '</pre>';
    }
}

document.getElementById('run').addEventListener('click', () => {
    const token = tokenInput.value.trim();
    if (!token) { alert('Pega un token primero (o usa "Sembrar datos de prueba + generar token").'); return; }
    localStorage.setItem('banorte_mcp_token', token);

    let args;
    try { args = JSON.parse(argsBox.value || '{}'); } catch { alert('El JSON de argumentos no es válido.'); return; }

    runOne(token, toolSelect.value, args);
});

document.getElementById('run-all').addEventListener('click', async () => {
    const token = tokenInput.value.trim();
    if (!token) { alert('Pega un token primero (o usa "Sembrar datos de prueba + generar token").'); return; }
    localStorage.setItem('banorte_mcp_token', token);

    for (const [toolName, args] of Object.entries(TOOLS)) {
        await runOne(token, toolName, args);
    }
});

document.getElementById('seed').addEventListener('click', async () => {
    const seedInfo = document.getElementById('seed-info');
    seedInfo.textContent = 'Sembrando...';
    try {
        const resp = await fetch('/mcp-test/seed');
        const data = await resp.json();
        tokenInput.value = data.token;
        localStorage.setItem('banorte_mcp_token', data.token);
        seedInfo.textContent = 'Listo: usuario #' + data.user_id + ' (' + data.email + ') con perfil financiero, portafolio y 3 holdings (AAPL, MSFT, CETES28).';
    } catch (e) {
        seedInfo.textContent = 'Error al sembrar: ' + e;
    }
});

function escapeHtml(str) {
    return str.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
}
</script>
</body>
</html>
