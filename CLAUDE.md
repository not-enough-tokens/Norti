# CLAUDE.md — Banorte-MCP

Contexto persistente para cualquier sesión de Claude Code que trabaje en este repo. Léelo completo antes de tocar código. Refleja decisiones ya cerradas por el equipo — no las reabras salvo instrucción explícita del usuario.

## Qué es este proyecto

Plataforma de Inteligencia Financiera y Educación Financiera vía MCP — HackMTY 2026, reto Banorte x Tec de Monterrey. Flujo conceptual: `Usuario → Agente (LLM) → MCP → A2UI → Componentes`.

No es un chatbot financiero: es una capa de capacidades financieras interoperables que un agente de IA descubre y usa mediante MCP tools explícitas, sin acceso directo a la base de datos. Los tres pilares que deben mantenerse siempre: **MCP + dominio financiero + educación financiera**. Datos 100% sintéticos generados por el equipo (no hay dependencia de APIs bancarias reales); no es banca real, no ejecuta trading real, no da asesoría financiera regulada.

Documentación complementaria de arquitectura/decisiones (agregada por Integrante C): `docs/architecture/*.md`, `docs/decisions/*.md`, `docs/development/roadmap.md`. Setup de Supabase: `docs/deployment/supabase-setup.md`. Guion de demo para M8: `docs/development/demo-script.md`.

## Stack confirmado

- PHP 8.4 (compatible con `^8.3` declarado en `composer.json`), Laravel 13.
- PostgreSQL alojado en **Supabase** — mismo connection string para dev y producción (dominio `.tech` ya adquirido para el deploy).
- `laravel/mcp` (^0.9.5) como paquete oficial de MCP, transporte principal **Streamable HTTP** (no STDIO — el MCP Inspector en Windows rompe rutas con backslash en modo STDIO).
- `laravel/ai` (^0.11) como SDK del agente de IA (M5) — agnóstico de proveedor por diseño, ver ADR 007. Default OpenAI vía `config/ai.php`; Anthropic y otros quedan disponibles como override sin tocar código.
- Auth: **Laravel Sanctum** (sesión web del humano) + **Laravel Passport** (OAuth para el agente de IA contra el MCP server).
- Frontend: **Blade + Vite** — monolito de un solo dominio, sin lógica financiera en la vista. Tailwind 4. Marca del producto: **Norti**. Referencia visual: librería de Figma «Banorte MCP — A2UI Components» (file key `XPC8ZTeOL8dKf8Nl8ZN3fS`); sus variables viven en `resources/css/app.css`: primitivos (`banorte-*`, `ink-*`) en `@theme` y alias semánticos **con los mismos nombres que en Figma** (`--color-text-primary`, `--color-action-primary`, …) en `@theme inline`, para que el código exportado de Figma resuelva contra ellos. Componentes Blade en `resources/views/components/`, nombrados como su componente de Figma cuando existe (`button` = Button 16:106 Primary/Secondary/Ghost, `alert` = Alert 20:42, hoy solo Tone=Highlight, `norti.app-mark` = app-mark de la App Sidebar), más `layouts.base`, `layouts.auth` y `form.text-field` (sin equivalente en Figma; toma la geometría del Intent Composer). Íconos/assets exportados de Figma se commitean en `public/images/norti/` — las URLs de assets de Figma expiran en 7 días, nunca referenciarlas directo.
- Proveedor de datos de mercado real: **twelvedata.com** (M4, en curso, dueño Integrante C) — detrás de una interfaz, nunca acoplado directo.
- Git/GitHub, Pest/PHPUnit. Redis/Docker/CI-CD son opcionales post-MVP, no bloqueantes.

## Arquitectura obligatoria

```
MCP Tool → Application Service → Domain/Business Logic → Models → DB / Proveedor externo
```

Las MCP Tools **nunca** contienen lógica financiera directamente — solo reciben el request, validan scope, delegan a un Service (inyectado por constructor vía el contenedor de Laravel) y devuelven la respuesta en forma `Response::structured(['component' => ..., 'props' => ...])` para que el frontend Blade decida el layout (patrón A2UI: el LLM elige componente semántico y llena props, nunca diseña el layout).

Namespace de MCP: `App\Mcp` (carpeta `app/Mcp`, todo minúsculas salvo la M — coincide con disco; ya se corrigió un bug de mayúsculas `app/MCP`, no repetirlo).

### Contracts primero (para no bloquear M3 con M1/M2/M4 de otros integrantes)

```
app/Services/Contracts/FinancialProfileServiceContract.php
app/Services/Contracts/PortfolioServiceContract.php
app/Services/Contracts/RiskAnalysisServiceContract.php
app/Services/Contracts/InvestmentSimulationServiceContract.php
app/Services/Contracts/MarketDataProviderContract.php
```

Bindear en el Service Container apuntando primero a `Fake*`/`Mock*` en `app/Services/Fakes/` (o `app/Services/MarketData/MockMarketDataProvider.php`). Cuando Integrante A (M1/M2) o Integrante C (M4) entreguen sus implementaciones reales, solo se cambia el binding — ninguna tool debería necesitar reescritura.

## Catálogo de MCP Tools (11: 7 de M2/M3 + 4 de M6)

| Tool | Scope requerido | Clasificación de riesgo |
|---|---|---|
| `get_financial_profile` | `mcp:read` | read-only (con matiz de sensibilidad, ver abajo) |
| `get_financial_goals` | `mcp:read` | read-only (con matiz de sensibilidad, ver abajo — ADR 006) |
| `get_portfolio` | `mcp:read` | read-only |
| `analyze_portfolio` | `mcp:read` | read-only |
| `get_asset_information` | `mcp:read` | read-only |
| `get_market_snapshot` | `mcp:read` | read-only |
| `simulate_investment` | `mcp:simulate` | categoría propia (simulate) |
| `get_educational_topic` | `mcp:read` | read-only (M6) |
| `get_learning_path` | `mcp:read` | read-only (M6) |
| `get_learning_progress` | `mcp:read` | read-only (M6) |
| `mark_topic_completed` | `mcp:write` | mutating, categoría propia (M6) — la única tool de escritura del catálogo |

Las 4 de M6 llegaron vía `feature/education-mcp` (mergeado a `master`), junto con el scope `mcp:write`. Los 3 flujos reales que emiten tokens (`/mcp/token`, y los comandos `mcp:demo-agent`/`mcp:client-tools`/`mcp:client-call` de M5) ya otorgan `mcp:write` — sin eso `mark_topic_completed` sería inalcanzable en la práctica (fue exactamente ese bug, ya corregido).

`get_financial_goals` (ADR 006, aceptado) cierra el hueco de que `InvestmentSimulationService::projectForGoal()`/`evaluateGoal()` (M2) estaban construidos, corregidos y probados pero inalcanzables — sin MCP tool, ruta ni vista que los llamara. Respeta la misma política de datos sensibles que `get_financial_profile`: `target_amount`/`current_amount`/`shortfall` (montos) solo aparecen con `detail: "exact"`; el default (`summary`) solo trae `progress_percentage`, `months_remaining`, `reaches_goal` e `is_overdue`. **No** incluye `create_financial_goal` (mutating, sigue en post-MVP).

**Fuera del MVP, deliberadamente:** `execute_trade`, `transfer_money`, `withdraw_funds` (high-risk, no se construyen). Post-MVP si sobra tiempo: `create_financial_goal`, `update_financial_profile` (mutating, requieren autorización adicional).

MVP mínimo demostrable si el tiempo se reduce: `get_financial_profile`, `get_portfolio`, `analyze_portfolio`; cuarta prioritaria `simulate_investment`.

## Auth y seguridad (decidido, no reabrir)

- **Humano ↔ app Blade:** sesión estándar de Laravel (login, cookie, CSRF). Sin tokens — no es SPA. Implementado en `app/Http/Controllers/Auth/` (`AuthenticatedSessionController`, `RegisteredUserController`) + rutas `login`/`register`/`logout` en `routes/web.php`, sin paquete externo (Breeze/Fortify) para no agregar una dependencia innecesaria.
- **Flujo post-login/registro:** el registro **no** inicia sesión: crea la cuenta y regresa a `route('login')` con flash `status` y el correo prellenado. El login redirige a `route('onboarding.index')` (`OnboardingController`) con el flash `Sesión iniciada de forma correcta`, nunca directo a `/education`. Un usuario ya autenticado que abre `/login` o `/register` también cae en `onboarding.index` (`redirectUsersTo` en `bootstrap/app.php`). Los mensajes de validación de auth están en español inline en los controllers (no hay `lang/es`; `APP_LOCALE=en`). Hoy `onboarding.index` es una pantalla de bienvenida placeholder (`resources/views/onboarding/index.blade.php`) con un link a continuar — el flujo real de onboarding (preguntas para detectar la intención del usuario: aprender, invertir, dar seguimiento a una meta) se construye después, en este mismo punto de entrada, sin tocar el flujo de auth de nuevo.
- **Agente de IA ↔ MCP server:** Laravel Passport. Se emite un Personal Access Token atado al usuario autenticado en el momento en que inicia la conversación (`$user->createToken('mcp-session', ['mcp:read', 'mcp:simulate', 'mcp:write'])->accessToken`), nunca un token genérico de la app. Ruta MCP protegida con middleware `auth:api`.
- **Scopes:** `mcp:read` (9 tools read-only), `mcp:simulate` (simulate_investment), `mcp:write` (mark_topic_completed). Cada tool valida su propio scope con `tokenCan()` dentro de `handle()` antes de ejecutar lógica.
- **Rate limiting:** `RateLimiter::for('mcp', ...)` por usuario/IP, aplicado como `throttle:mcp` en la ruta de `routes/ai.php`.
- **`/api/market-data/*`:** protegidas con `auth:api` (Passport, el mismo guard que el MCP server, porque son rutas stateless) + `throttle:market-data`, cuyo límite sale de `services.twelvedata.rate_limit_per_minute` (default 8, el techo del plan gratuito). Estaban completamente abiertas: sin auth cualquiera podía agotar la cuota de TwelveData y tumbar el market data de toda la app. Ojo: el techo real de TwelveData es **por cuenta**, no por usuario, así que este throttle limita a cada cliente pero no garantiza el techo global.
- **Audit log:** tabla `audit_logs` (`user_id`, `tool_name`, `input` jsonb sin campos sensibles, `result_summary`, `ip_address`, `created_at`). Registrar cada `tools/call`. **Nunca** loggear montos exactos ni el `FinancialProfile` completo — solo metadata (qué tool, cuándo, resultado resumido/booleano).

### Política de datos sensibles al modelo

`FinancialProfile` nunca se envía completo al LLM. Por defecto el LLM solo ve un resumen/generalización (categorías, no montos exactos). Mecanismo: `get_financial_profile` recibe parámetro de schema `detail: "summary" | "exact"`, default `"summary"`; la descripción del parámetro instruye al LLM a usar `"exact"` solo si el usuario lo pidió explícitamente. Limitación reconocida: el único enforcement es que el LLM siga la instrucción del schema (no hay barrera dura todavía). Mejora post-MVP si sobra tiempo: la tool siempre regresa `summary`, y un botón "Ver cifra exacta" en el componente A2UI dispara —con confirmación explícita del usuario, no del LLM— una llamada aparte que sí regresa el dato exacto (Intent-Token Handshake, mismo patrón que las acciones mutables).

## Convenciones de código para las Tools

- `schema(JsonSchema $schema): array` — usar `$schema->number()`, `->integer()`, `->string()->enum([...])`, siempre con `->description()` y `->required()` cuando aplique.
- `handle(Request $request): Response|ResponseFactory` — **type hint obligatorio con la unión**: `Response::structured()` regresa `ResponseFactory`, no `Response`. Declarar solo `: Response` truena en runtime. Importar `Laravel\Mcp\ResponseFactory`.
- Validar scope antes de ejecutar: `if (! $request->user()->tokenCan('mcp:read')) { return Response::error('...'); }`.
- Registrar el server en `routes/ai.php`:
  ```php
  Mcp::web('/mcp/banorte', \App\Mcp\Servers\BanorteServer::class)
      ->middleware(['auth:api', 'throttle:mcp']);
  ```

## Comandos

```bash
# Passport (NO usar `passport:install`: republica las migrations de OAuth con timestamp
# nuevo y truena con `relation already exists`, porque este proyecto ya las tiene
# versionadas en database/migrations/2026_09_12_1929*)
composer require laravel/passport   # si no está ya
php artisan migrate                 # tablas de Passport, usa el connection string de Supabase en .env
php artisan passport:keys           # solo si storage/oauth-*.key no existen todavía
php artisan passport:client --personal --name="..."   # cliente personal-access que createToken() necesita

# Scaffolding MCP
php artisan make:mcp-server <Nombre>
php artisan make:mcp-tool <Nombre>

# Testing manual del server (Streamable HTTP — NO usar STDIO en Windows)
# `php artisan serve` normal atiende UNA request a la vez. /chat (ChatController)
# y los comandos mcp:client-*/mcp:demo-agent se auto-llaman por HTTP a este mismo
# server (ver nota en routes/web.php) -- con un solo worker eso es un auto-deadlock:
# la request externa (POST /chat) nunca libera el proceso para atender la interna
# (POST /mcp/banorte), que truena con "HTTP request failed" y /chat cae al mensaje
# de error genérico. Arreglo: correr el server con varios workers.
PHP_CLI_SERVER_WORKERS=4 php artisan serve
php artisan mcp:inspector /mcp/banorte

# M5 paso 1 (verificado): solo list_tools(), sin LLM ni agente
php artisan mcp:client-tools

# M5 paso 2 (verificado, con la API real de Twelve Data): call_tool() manual
php artisan mcp:client-call get_market_snapshot --arguments='{"symbols":["AAPL"]}'

# M5 paso 3 (verificado end-to-end con OpenAI real): agente real vía Laravel AI SDK,
# decoupled del proveedor (default OpenAI) -- requiere `php artisan serve` corriendo
# y OPENAI_API_KEY en .env; --provider=anthropic --model=... para cambiar de proveedor
php artisan mcp:demo-agent "¿Cuál es la cotización de AAPL?"
```

Tests: usar los helpers de `Laravel\Mcp\Server\Testing\*` para cubrir — cada tool responde con el schema esperado dado un input válido; cada tool rechaza si el token no tiene el scope requerido; `get_financial_profile` regresa `summary` por defecto y `exact` solo con `detail: "exact"`. `tests/TestCase.php` llama `withoutVite()` en `setUp()` porque CI no compila assets — sin eso, cualquier test que renderice una vista con `@vite` truena por falta de `public/build/manifest.json`.

## Modelo de dominio (M1, referencia — dueño Integrante A)

`User`, `FinancialProfile` (ingresos, gastos, ahorro, tolerancia al riesgo, horizonte de inversión), `FinancialGoal` (`name`, `target_amount`, `current_amount`, `target_day`, `goal_type` — no hay columna de prioridad), `Portfolio`, `Holding` (posición — separa el activo de que el usuario lo mantenga), `Asset` (acciones, ETFs, renta fija, efectivo). `risk_tolerance` es un string libre, sin enum ni check en la BD — nunca pasarlo crudo a la lógica de riesgo, ver `RiskAnalysisService::suggestRiskProfile()` en M2. M3 solo necesita el *shape* de la respuesta de estos Services, no su fórmula interna (algoritmo de riesgo, métricas de diversificación y metodología de simulación son decisión de Integrante A/M2).

## Roadmap y ownership (M0–M8)

- M0 Project Foundation — Laravel + Git + DB + Auth + MCP ✅ completado (Passport incluido)
- M1 Financial Domain (Integrante A) — modelos, migraciones y factories ✅ completo (mergeado a `master` vía PR #10)
- M2 Financial Services (Integrante A) — ✅ integrado: `RiskAnalysisServiceAdapter`/`InvestmentSimulationServiceAdapter`/`FinancialGoalServiceAdapter` (`app/Services/Financial/`) envuelven el `RiskAnalysisService`/`InvestmentSimulationService` reales; ya no hay placeholders ni capacidad inalcanzable (`get_financial_goals`, ADR 006, cerró el hueco de `projectForGoal()`/`evaluateGoal()`). **Vocabulario de `asset_type` unificado en español** (`accion`/`bono`/`fondo`/`efectivo`): `config/investment_rules.php` usa las mismas llaves que la columna `assets.asset_type`, así que ya no hay traducción en los consumidores — si agregas un tipo de activo, agrégalo en ambos lados. **`risk_tolerance` es un string libre** (sin enum ni check en la BD): nunca lo pases crudo a `assetAllocation()`/`expectedAnnualReturn()`, pásalo por `RiskAnalysisService::suggestRiskProfile()`, que normaliza y cae al horizonte de inversión cuando el valor no es reconocible
- **M3 MCP Server — BanorteServer + Tools (Felix / Integrante B)** ✅ las 6 tools implementadas, registradas y probadas
- M4 External Data — MarketDataProvider Mock→Real (Integrante C, twelvedata.com) — ✅ completo: la abstracción paralela `MarketDataProvider`/`TwelveDataProvider` que Integrante C tenía en desarrollo se retiró; su normalización de `getHistoricalPrices()`/`getAssetProfile()` (antes stub en ambos lados) se trasplantó a `TwelveDataMarketDataProvider::timeSeries()`, que es ahora el único proveedor de market data (`quote()`/`profile()` pasan el payload de Twelve Data casi sin tocar; `timeSeries()` normaliza a barras tipadas). `MockMarketDataProvider` (`app/Services/MarketData/`) implementa el mismo `MarketDataProviderContract` para tests deterministas. **Conexión a Supabase real verificada** (2026-09-13): `migrate` corre limpio, Passport emite tokens (requirió crear el *personal access client* con `passport:client --personal` — `passport:install` republica migrations de OAuth duplicadas si el proyecto ya tiene las suyas propias, no usarlo), y `audit_logs.input` confirmado como `jsonb` real. Usado con el Transaction pooler (puerto 6543) en vez del Session pooler (5432) que recomienda `docs/deployment/supabase-setup.md`; funcionó para esta verificación pero vigilar errores de "prepared statement already exists" bajo concurrencia. No se corrió la suite de tests contra esta base (usa `RefreshDatabase`, borraría los datos reales que ya tiene)
- **M5 AI/Agents (Integrante C)** ✅ completo, construido y verificado como vertical slice (mergeado a `master` desde la rama `Market-Data`): (1) `php artisan mcp:client-tools` ✅ verificado end-to-end — cliente MCP puro (`Laravel\Mcp\Client`, sin SDK de IA) que se autentica con Passport y hace `list_tools()`, sin LLM ni agente; (2) `php artisan mcp:client-call <tool> --arguments=<json>` ✅ verificado end-to-end contra la API real de Twelve Data (`get_market_snapshot` con AAPL) — la llamada atraviesa sin cambios `MCP Tool → MarketDataProviderContract → TwelveDataMarketDataProvider → TwelveDataClient → Twelve Data`; (3) `php artisan mcp:demo-agent` ✅ reescrito sobre **Laravel AI SDK** (`laravel/ai`, ver ADR 007) — `App\Ai\Agents\BanorteMcpAgent` (`Agent`+`HasTools`) consume el cliente MCP directamente, sin código específico de proveedor; default OpenAI vía `#[Provider(Lab::OpenAI)]`, override con `--provider`/`--model`. **Verificado end-to-end con OpenAI real**: el modelo eligió la tool de mercado por sí mismo y respondió con la cotización real de AAPL en lenguaje natural. La implementación anterior (HTTP manual a Anthropic) fue retirada por acoplar el agente a un proveedor específico. Todos requieren `php artisan serve` corriendo (hacen HTTP real, no usan el kernel de test). Pendiente (fuera de alcance de M5): UI de chat / integración con el frontend Blade — ver M8. **Sigue faltando A2UI** (ninguna rama la construye todavía): sin eso, el agente puede invocar tools pero no hay generación dinámica de interfaz, que es la otra pieza que más pesa en la rúbrica del reto
- **M6 Financial Education (Integrante D)** ✅ completo (con salvedades explícitas al final de esta línea): `EducationalTopic` + pivote `educational_topic_user` (con `completed_at`), `EducationalTopicController`, seeder y tests. Expuesto vía MCP con 4 tools propias (`get_educational_topic`, `get_learning_path`, `get_learning_progress`, `mark_topic_completed`, scope `mcp:write` para la última — ver Catálogo de MCP Tools arriba). **Recomendaciones conectadas al contexto financiero**: `FinancialEducationService::getRecommendedTopic()` usa `FinancialEducationIntegrationService::getFinancialContext()` (antes código muerto) para priorizar, entre los temas sin completar, el más relevante — sin metas registradas → "Ahorro vs inversión"; portafolio concentrado en un solo activo → "Diversificación"; `risk_tolerance` conservador (normalizado, nunca la columna cruda) con acciones en el portafolio → "Riesgo de inversión"; si nada aplica, el primero sin completar. Cada recomendación trae `recommended_reason` (`no_goals`/`concentrated_portfolio`/`conservative_profile_with_stocks`/`default`) — el backend solo da la señal, nunca el texto explicativo; narrar el "por qué" es trabajo del agente/LLM. `get_learning_progress` agrega `category_gaps`: categorías donde el usuario tiene 0 temas completados (detección simple, no es un algoritmo general). Dejado fuera a propósito: refactorizar las 3 reglas de slug a categoría, superficie en Blade/A2UI (no existe esa infraestructura en todo el proyecto todavía), y conectar el flujo de onboarding hacia `/education`
- **M7 Security & Hardening (Felix / Integrante B)** ✅ audit log + rate limiting activos

- M8 Product & Demonstration (Integrante D) — 🔶 en progreso: pantallas de auth (login, registro, bienvenida) con la marca Norti y los tokens de la librería de Figma

Ownership = responsabilidad principal, no exclusividad.

## Regla de auditoría para features nuevas

Toda funcionalidad nueva se evalúa con 3 preguntas: ¿aporta directamente al objetivo (análisis, educación, interoperabilidad MCP, UX, seguridad)? ¿introduce una dependencia innecesaria? ¿desplaza el núcleo del proyecto (MCP + dominio financiero + educación)?

## Estado de implementación M3/M7 y cómo probar manualmente

Las 11 tools (ver Catálogo de MCP Tools arriba) están implementadas, registradas en `BanorteServer` y activas en `/mcp/banorte`. `FinancialProfileServiceContract`, `FinancialGoalServiceContract` y `PortfolioServiceContract` corren contra Eloquent real; `RiskAnalysisServiceContract` e `InvestmentSimulationServiceContract` ya corren contra el algoritmo real de Integrante A/M2 vía `RiskAnalysisServiceAdapter`/`InvestmentSimulationServiceAdapter` (`app/Services/Financial/`) — no quedan placeholders. `MarketDataProviderContract` corre contra `TwelveDataMarketDataProvider` (`app/Services/MarketData/`, sobre `TwelveDataClient`) — único proveedor de market data del proyecto, con `timeSeries()` normalizado a barras tipadas; ver nota en `DomainServiceProvider`.

Cada entorno (incluida Supabase) necesita correr su propio `php artisan passport:install` (o al menos `php artisan passport:keys`) — las llaves de encriptación (`storage/oauth-*.key`) y el cliente personal-access viven en ese entorno, no se comparten vía git. Sin esto, cualquier test o request que pase por Passport truena con `LogicException: Invalid key supplied`. En Windows con Herd/PHP sin la extensión `sodium`, `composer install`/`update` puede rechazar el lock file por `lcobucci/jwt` (dependencia de Passport) — Passport no usa sodium en la práctica (firma con RSA vía `openssl`), así que `composer install --ignore-platform-req=ext-sodium` es seguro.

Para emitir un token de prueba y llamar al server manualmente:

```bash
php artisan tinker
```
```php
$user = App\Models\User::first(); // o crear uno con User::factory()->create()
$token = $user->createToken('mcp-session', ['mcp:read', 'mcp:simulate', 'mcp:write'])->accessToken;
```

Luego, con `php artisan serve` corriendo, un POST JSON-RPC a `/mcp/banorte` con `Authorization: Bearer <token>` (primero `initialize`, después `tools/call` con el `Mcp-Session-Id` que regresa el header de la respuesta de `initialize`) — o usar `php artisan mcp:inspector /mcp/banorte` para una UI interactiva. En tests, usar `Laravel\Passport\Passport::actingAs($user, ['mcp:read'])` + `BanorteServer::tool(NombreTool::class, [...])->assertOk()` (ver `tests/Feature/Mcp/*Test.php` para el patrón).

## Fuera de scope (explícito)

Banca real (cuentas, transferencias, depósitos, pagos), trading real (ejecutar órdenes, brokers), asesoría financiera profesional/regulada, sistema financiero universal (todos los mercados/instrumentos/divisas), banco digital completo.
