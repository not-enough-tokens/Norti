# CLAUDE.md — Banorte-MCP

Contexto persistente para cualquier sesión de Claude Code que trabaje en este repo. Léelo completo antes de tocar código. Refleja decisiones ya cerradas por el equipo — no las reabras salvo instrucción explícita del usuario.

## Qué es este proyecto

Plataforma de Inteligencia Financiera y Educación Financiera vía MCP — HackMTY 2026, reto Banorte x Tec de Monterrey. Flujo conceptual: `Usuario → Agente (LLM) → MCP → A2UI → Componentes`.

No es un chatbot financiero: es una capa de capacidades financieras interoperables que un agente de IA descubre y usa mediante MCP tools explícitas, sin acceso directo a la base de datos. Los tres pilares que deben mantenerse siempre: **MCP + dominio financiero + educación financiera**. Datos 100% sintéticos generados por el equipo (no hay dependencia de APIs bancarias reales); no es banca real, no ejecuta trading real, no da asesoría financiera regulada.

Documentación complementaria de arquitectura/decisiones (agregada por Integrante C): `docs/architecture/*.md`, `docs/decisions/*.md`, `docs/development/roadmap.md`.

## Stack confirmado

- PHP 8.4 (compatible con `^8.3` declarado en `composer.json`), Laravel 13.
- PostgreSQL alojado en **Supabase** — mismo connection string para dev y producción (dominio `.tech` ya adquirido para el deploy).
- `laravel/mcp` (^0.9.5) como paquete oficial de MCP, transporte principal **Streamable HTTP** (no STDIO — el MCP Inspector en Windows rompe rutas con backslash en modo STDIO).
- Auth: **Laravel Sanctum** (sesión web del humano) + **Laravel Passport** (OAuth para el agente de IA contra el MCP server).
- Frontend: **Blade + Vite** — monolito de un solo dominio, sin lógica financiera en la vista. Tailwind 4.
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

## Catálogo de MCP Tools (6, M3)

| Tool | Scope requerido | Clasificación de riesgo |
|---|---|---|
| `get_financial_profile` | `mcp:read` | read-only (con matiz de sensibilidad, ver abajo) |
| `get_portfolio` | `mcp:read` | read-only |
| `analyze_portfolio` | `mcp:read` | read-only |
| `get_asset_information` | `mcp:read` | read-only |
| `get_market_snapshot` | `mcp:read` | read-only |
| `simulate_investment` | `mcp:simulate` | categoría propia (simulate) |

**Fuera del MVP, deliberadamente:** `execute_trade`, `transfer_money`, `withdraw_funds` (high-risk, no se construyen). Post-MVP si sobra tiempo: `create_financial_goal`, `update_financial_profile` (mutating, requieren autorización adicional).

MVP mínimo demostrable si el tiempo se reduce: `get_financial_profile`, `get_portfolio`, `analyze_portfolio`; cuarta prioritaria `simulate_investment`.

## Auth y seguridad (decidido, no reabrir)

- **Humano ↔ app Blade:** sesión estándar de Laravel (login, cookie, CSRF). Sin tokens — no es SPA.
- **Agente de IA ↔ MCP server:** Laravel Passport. Se emite un Personal Access Token atado al usuario autenticado en el momento en que inicia la conversación (`$user->createToken('mcp-session', ['mcp:read', 'mcp:simulate'])->accessToken`), nunca un token genérico de la app. Ruta MCP protegida con middleware `auth:api`.
- **Scopes:** `mcp:read` (4 read-only + get_market_snapshot), `mcp:simulate` (simulate_investment). Cada tool valida su propio scope con `tokenCan()` dentro de `handle()` antes de ejecutar lógica.
- **Rate limiting:** `RateLimiter::for('mcp', ...)` por usuario/IP, aplicado como `throttle:mcp` en la ruta de `routes/ai.php`.
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
# Passport
composer require laravel/passport   # si no está ya
php artisan migrate                 # tablas de Passport, usa el connection string de Supabase en .env
php artisan passport:install        # claves de encriptación + clientes personal-access/password-grant

# Scaffolding MCP
php artisan make:mcp-server <Nombre>
php artisan make:mcp-tool <Nombre>

# Testing manual del server (Streamable HTTP — NO usar STDIO en Windows)
php artisan serve
php artisan mcp:inspector /mcp/banorte
```

Tests: usar los helpers de `Laravel\Mcp\Server\Testing\*` para cubrir — cada tool responde con el schema esperado dado un input válido; cada tool rechaza si el token no tiene el scope requerido; `get_financial_profile` regresa `summary` por defecto y `exact` solo con `detail: "exact"`.

## Modelo de dominio (M1, referencia — dueño Integrante A)

`User`, `FinancialProfile` (ingresos, gastos, ahorro, tolerancia al riesgo, horizonte de inversión), `FinancialGoal` (nombre, cantidad objetivo, cantidad actual, fecha objetivo, prioridad, categoría), `Portfolio`, `Holding` (posición — separa el activo de que el usuario lo mantenga), `Asset` (acciones, ETFs, renta fija, efectivo), perfil de riesgo enum (Conservative/Moderate/Aggressive). M3 solo necesita el *shape* de la respuesta de estos Services, no su fórmula interna (algoritmo de riesgo, métricas de diversificación y metodología de simulación son decisión de Integrante A/M2).

## Roadmap y ownership (M0–M8)

- M0 Project Foundation — Laravel + Git + DB + Auth + MCP ✅ completado (Passport incluido)
- M1 Financial Domain (Integrante A) — modelos, migraciones y factories ✅ completo (mergeado a `master` vía PR #10)
- M2 Financial Services (Integrante A) — ✅ integrado: `RiskAnalysisServiceAdapter`/`InvestmentSimulationServiceAdapter` (`app/Services/Financial/`) envuelven el `RiskAnalysisService`/`InvestmentSimulationService` reales; ya no hay placeholders
- **M3 MCP Server — BanorteServer + Tools (Felix / Integrante B)** ✅ las 6 tools implementadas, registradas y probadas
- M4 External Data — MarketDataProvider Mock→Real (Integrante C, twelvedata.com) — ✅ completo: la abstracción paralela `MarketDataProvider`/`TwelveDataProvider` que Integrante C tenía en desarrollo se retiró; su normalización de `getHistoricalPrices()`/`getAssetProfile()` (antes stub en ambos lados) se trasplantó a `TwelveDataMarketDataProvider::timeSeries()`, que es ahora el único proveedor de market data (`quote()`/`profile()` pasan el payload de Twelve Data casi sin tocar; `timeSeries()` normaliza a barras tipadas). `MockMarketDataProvider` (`app/Services/MarketData/`) implementa el mismo `MarketDataProviderContract` para tests deterministas. Pendiente: no se ha probado nada contra la Supabase real, solo sqlite local/CI
- M5 AI/Agents (Integrante C)
- M6 Financial Education (Integrante D)
- **M7 Security & Hardening (Felix / Integrante B)** ✅ audit log + rate limiting activos
- M8 Product & Demonstration (Integrante D)

Ownership = responsabilidad principal, no exclusividad.

## Regla de auditoría para features nuevas

Toda funcionalidad nueva se evalúa con 3 preguntas: ¿aporta directamente al objetivo (análisis, educación, interoperabilidad MCP, UX, seguridad)? ¿introduce una dependencia innecesaria? ¿desplaza el núcleo del proyecto (MCP + dominio financiero + educación)?

## Estado de implementación M3/M7 y cómo probar manualmente

Las 6 tools (`get_financial_profile`, `get_portfolio`, `analyze_portfolio`, `get_asset_information`, `get_market_snapshot`, `simulate_investment`) están implementadas, registradas en `BanorteServer` y activas en `/mcp/banorte`. `FinancialProfileServiceContract` y `PortfolioServiceContract` corren contra Eloquent real; `RiskAnalysisServiceContract` e `InvestmentSimulationServiceContract` ya corren contra el algoritmo real de Integrante A/M2 vía `RiskAnalysisServiceAdapter`/`InvestmentSimulationServiceAdapter` (`app/Services/Financial/`) — no quedan placeholders. `MarketDataProviderContract` corre contra `TwelveDataMarketDataProvider` (`app/Services/MarketData/`, sobre `TwelveDataClient`) — único proveedor de market data del proyecto, con `timeSeries()` normalizado a barras tipadas; ver nota en `DomainServiceProvider`.

Cada entorno (incluida Supabase) necesita correr su propio `php artisan passport:install` (o al menos `php artisan passport:keys`) — las llaves de encriptación (`storage/oauth-*.key`) y el cliente personal-access viven en ese entorno, no se comparten vía git. Sin esto, cualquier test o request que pase por Passport truena con `LogicException: Invalid key supplied`. En Windows con Herd/PHP sin la extensión `sodium`, `composer install`/`update` puede rechazar el lock file por `lcobucci/jwt` (dependencia de Passport) — Passport no usa sodium en la práctica (firma con RSA vía `openssl`), así que `composer install --ignore-platform-req=ext-sodium` es seguro.

Para emitir un token de prueba y llamar al server manualmente:

```bash
php artisan tinker
```
```php
$user = App\Models\User::first(); // o crear uno con User::factory()->create()
$token = $user->createToken('mcp-session', ['mcp:read', 'mcp:simulate'])->accessToken;
```

Luego, con `php artisan serve` corriendo, un POST JSON-RPC a `/mcp/banorte` con `Authorization: Bearer <token>` (primero `initialize`, después `tools/call` con el `Mcp-Session-Id` que regresa el header de la respuesta de `initialize`) — o usar `php artisan mcp:inspector /mcp/banorte` para una UI interactiva. En tests, usar `Laravel\Passport\Passport::actingAs($user, ['mcp:read'])` + `BanorteServer::tool(NombreTool::class, [...])->assertOk()` (ver `tests/Feature/Mcp/*Test.php` para el patrón).

## Fuera de scope (explícito)

Banca real (cuentas, transferencias, depósitos, pagos), trading real (ejecutar órdenes, brokers), asesoría financiera profesional/regulada, sistema financiero universal (todos los mercados/instrumentos/divisas), banco digital completo.
