# Plan de implementación — M0 → M3 → M7 (Banorte-MCP)

Este plan es para el trabajo de **Felix (Integrante B — MCP / Backend Infrastructure)** dentro del repo `Banorte-MCP`, en una rama nueva creada desde `main` (o la rama base que el equipo use). Está pensado para ejecutarse con Claude Code directamente sobre este repo.

## Contexto que ya no hay que redecidir

- Stack: PHP 8.4 (compatible con `^8.3` de composer.json), Laravel 13, PostgreSQL alojado en Supabase (mismo connection string para dev y producción, dominio .tech ya adquirido).
- Namespace de MCP ya corregido a `App\Mcp` (coincide con la carpeta `app/Mcp` en disco — no repetir el bug de mayúsculas).
- Frontend: Blade + Vite, monolito, mismo dominio — el login humano usa sesión estándar de Laravel, sin tokens.
- Auth del agente de IA contra el MCP: **Laravel Passport**, con Personal Access Tokens atados al usuario autenticado (no un token genérico de la app).
- `MarketDataProvider` (M4, dueño Integrante C) usará **twelvedata.com** como proveedor real; la integración está en curso por separado. M3 debe programar contra una interfaz, nunca contra la API directamente.
- Principio de diseño no negociable: **MCP Tool → Application Service → Domain/Business Logic → Models → DB/Proveedor externo**. Las tools nunca contienen lógica financiera.
- Catálogo de 6 tools: `get_financial_profile`, `get_portfolio`, `analyze_portfolio`, `simulate_investment`, `get_asset_information`, `get_market_snapshot`.
- Clasificación de riesgo / scopes de Passport: `mcp:read` (las 4 de solo lectura + get_market_snapshot), `mcp:simulate` (simulate_investment). Nada de mutating/high-risk en el MVP.
- Política de datos sensibles: `get_financial_profile` nunca regresa montos exactos por defecto — parámetro `detail: "summary"|"exact"` en el schema, con instrucción explícita al LLM de usar `exact` solo si el usuario lo pidió directamente.

## Dependencias y qué hacer si no están listas todavía

- M1 (modelos: `FinancialProfile`, `Portfolio`, `Holding`, `Asset`, `FinancialGoal`) y M2 (Services: `PortfolioService`, `RiskAnalysisService`, `InvestmentSimulationService`, `FinancialProfileService`) son de Integrante A. **No bloquean el arranque**: definir primero los *contracts* (interfaces) de estos Services en `app/Contracts/` o `app/Services/Contracts/`, e implementar una versión `Fake`/`Mock` en memoria si A todavía no entregó la real. Cuando A entregue su implementación, solo se cambia el binding en el Service Container.
- M4 (`MarketDataProvider` real con twelvedata.com) es de Integrante C. Definir la interfaz `MarketDataProviderContract` ahora mismo y usar un `MockMarketDataProvider` mientras tanto.

## Fase 1 — Passport

1. `composer require laravel/passport` (si no está ya en `composer.json` — verificar primero).
2. `php artisan migrate` (crea las tablas de Passport — usa el connection string de Supabase configurado en `.env`).
3. `php artisan passport:install` (genera las claves de encriptación y los clientes personal-access / password-grant).
4. En `App\Models\User`: agregar el trait `Laravel\Passport\HasApiTokens`.
5. En `bootstrap/app.php` (o `AuthServiceProvider` si el proyecto sigue ese patrón en Laravel 13): registrar el guard `api` con `driver: passport`.
6. Definir los scopes en el `AuthServiceProvider` (o donde Passport lo pida en Laravel 13): `Passport::tokensCan(['mcp:read' => 'Leer datos financieros del usuario', 'mcp:simulate' => 'Ejecutar simulaciones de inversión']);`
7. Middleware `auth:api` (Passport) en la ruta MCP — ver Fase 3.
8. Emisión del token: cuando el usuario autenticado (sesión Blade) inicia una conversación con el agente, el backend emite un Personal Access Token con los scopes correspondientes vía `$user->createToken('mcp-session', ['mcp:read', 'mcp:simulate'])->accessToken` y lo usa como Bearer al llamar internamente al endpoint MCP.

## Fase 2 — Contracts (desbloquea M3 sin esperar a A/C)

Crear (interfaces, sin implementación de negocio real todavía si A/C no la tienen lista):

```
app/Services/Contracts/FinancialProfileServiceContract.php
app/Services/Contracts/PortfolioServiceContract.php
app/Services/Contracts/RiskAnalysisServiceContract.php
app/Services/Contracts/InvestmentSimulationServiceContract.php
app/Services/Contracts/MarketDataProviderContract.php
```

Cada contract define los métodos que las MCP Tools van a invocar (ej. `InvestmentSimulationServiceContract::simulate(float $amount, int $months, string $riskProfile): array`). Registrar bindings en un `AppServiceProvider` (o uno dedicado `DomainServiceProvider`), apuntando primero a implementaciones `Fake*`/`Mock*` en `app/Services/Fakes/` o `app/Services/MarketData/MockMarketDataProvider.php`.

## Fase 3 — MCP Server + Tools

1. Confirmar `app/Mcp/Servers/BanorteServer.php` (ya existe, namespace correcto) y registrarlo en `routes/ai.php`:
   ```php
   Mcp::web('/mcp/banorte', \App\Mcp\Servers\BanorteServer::class)
       ->middleware(['auth:api']);
   ```
2. Para cada una de las 6 tools, `php artisan make:mcp-tool <Nombre>` (ya existen los 4 stubs vacíos — completar; crear `GetAssetInformation` y `GetMarketSnapshot` si no están, revisar nombres exactos en `app/MCP/Tools` — **ojo**: la carpeta legada tenía mayúsculas `MCP`, confirmar que quedó migrada a `app/Mcp/Tools` consistente con el namespace corregido).
3. Cada tool:
   - Recibe sus dependencias (el Service contract correspondiente) por constructor (Laravel las resuelve vía el container al invocar la tool).
   - `schema(JsonSchema $schema): array` define los parámetros de entrada (recordar: `$schema->number()`, `->integer()`, `->string()->enum([...])`, todos con `->description()` y `->required()` según aplique).
   - `handle(Request $request): Response|ResponseFactory` — **ojo con el tipo de retorno**: `Response::structured()` regresa `ResponseFactory`, no `Response`; si el método se declara solo `: Response` va a tronar en runtime.
   - Antes de ejecutar lógica, valida el scope: `if (! $request->user()->tokenCan('mcp:read')) { return Response::error('...'); }` (ajustar `mcp:read` / `mcp:simulate` según la tool).
   - Regresa `Response::structured([...])` con forma `{component, props}` para que el frontend Blade decida el layout (patrón A2UI ya validado en el sandbox).
4. `get_financial_profile`: implementar el parámetro `detail` (`summary` por defecto, `exact` solo si el LLM lo pide explícitamente según la instrucción del schema).

## Fase 4 — Seguridad transversal

1. Rate limiting: `RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));` y aplicar el limiter `throttle:mcp` en la misma ruta de `routes/ai.php`.
2. Audit log: crear migración `audit_logs` (columnas: `user_id`, `tool_name`, `input` (jsonb, sin campos sensibles), `result_summary`, `ip_address`, `created_at`). Registrar cada `tools/call` — puede ser un middleware alrededor de la ruta MCP, o un listener del evento que el paquete `laravel/mcp` dispare (revisar `Laravel\Mcp\Events\SessionInitialized` y ver si hay evento por-tool-call; si no, loggear dentro de cada tool con un trait común `LogsToolInvocation`).
3. Confirmar que ninguna tool loggea montos exactos ni el `FinancialProfile` completo en el audit log — solo metadata (qué tool, cuándo, resultado resumido/booleano de éxito).

## Fase 5 — Tests

Usar los helpers de testing que trae `laravel/mcp` (`Laravel\Mcp\Server\Testing\*`) para al menos:
- Cada tool responde con el schema esperado dado un input válido.
- Cada tool rechaza cuando el token no tiene el scope requerido.
- `get_financial_profile` regresa `summary` por defecto y `exact` solo cuando se pasa `detail: "exact"`.

## Fase 6 — Handoff al equipo

- Actualizar `routes/ai.php` y confirmar con Integrante A/C el momento en que sus Services/Provider reales reemplazan a los Fakes/Mocks (solo cambia el binding en el Service Container, ninguna tool debería necesitar reescritura).
- Dejar una nota corta en el README o en `CLAUDE.md` del repo real explicando los scopes de Passport y cómo se emite el token para pruebas manuales (ej. vía Tinker o una ruta de debug protegida).
