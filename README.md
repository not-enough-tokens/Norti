# Banorte MCP — Norti

Plataforma de **Inteligencia Financiera y Educación Financiera vía MCP** — HackMTY 2026, reto Banorte × Tec de Monterrey.

No es un chatbot financiero: es una capa de capacidades financieras interoperables que un agente de IA descubre y usa mediante *tools* MCP explícitas, sin acceso directo a la base de datos. La interfaz se genera con el patrón **A2UI**: el agente elige un componente semántico y llena sus props; la vista Blade decide el layout.

```
Usuario → Agente (LLM) → MCP → A2UI → Componentes
```

Los tres pilares: **MCP + dominio financiero + educación financiera**. Todos los datos de usuario son sintéticos; no es banca real, no ejecuta trading y no da asesoría financiera regulada.

## Estado (checkpoint)

| Hito | Descripción | Estado |
|---|---|---|
| M0 | Fundación (Laravel, DB, auth, MCP) | ✅ |
| M1 | Dominio financiero (modelos, migraciones, factories) | ✅ |
| M2 | Servicios financieros (riesgo, simulación, metas) | ✅ |
| M3 | MCP Server + 11 tools | ✅ |
| M4 | Datos de mercado reales (Twelve Data) | ✅ |
| M5 | Agente de IA (Laravel AI SDK, agnóstico de proveedor) | ✅ |
| M6 | Educación financiera (temas, ruta, progreso, recomendaciones) | ✅ |
| M7 | Seguridad (scopes, audit log, rate limiting) | ✅ |
| M8 | Producto y demo (auth, onboarding, chat con A2UI, educación) | 🔶 en progreso |

Detalle en [`docs/development/roadmap.md`](docs/development/roadmap.md).

## Stack

- PHP 8.4 (`^8.3`), Laravel 13
- PostgreSQL en Supabase (SQLite para desarrollo local rápido)
- [`laravel/mcp`](https://github.com/laravel/mcp) — server MCP por **Streamable HTTP**
- [`laravel/ai`](https://github.com/laravel/ai) — agente; OpenAI por defecto, Anthropic u otros por override ([ADR 007](docs/decisions/007-ai-agent-provider-decoupling.md))
- Laravel Passport (token del agente hacia el MCP) + sesión estándar de Laravel (humano en la app Blade)
- Blade + Vite + Tailwind 4; marca **Norti**, tokens de la librería de Figma «Banorte MCP — A2UI Components»
- Twelve Data como proveedor de market data, detrás de `MarketDataProviderContract`
- PHPUnit + GitHub Actions (Pint + tests)

## Arquitectura

```
MCP Tool → Application Service → Domain/Business Logic → Models → DB / Proveedor externo
```

Las tools **nunca** contienen lógica financiera: validan scope, delegan a un servicio (contratos en `app/Services/Contracts/`, bindings en `DomainServiceProvider`) y responden `Response::structured(['component' => ..., 'props' => ...])`.

```
app/Mcp/                 Servidor y tools MCP
app/Services/            Contratos, servicios de dominio, adaptadores, market data
app/Ai/                  Agente (BanorteMcpAgent) y captura de resultados de tools
app/Http/Controllers/    Auth, onboarding, chat, educación, tokens MCP
resources/views/components/a2ui/   Componentes A2UI (uno por `component` que emiten las tools)
docs/                    Arquitectura, decisiones (ADR), deployment, roadmap
```

### Catálogo de tools

| Tool | Scope |
|---|---|
| `get_financial_profile`, `get_financial_goals`, `get_portfolio`, `analyze_portfolio`, `get_asset_information`, `get_market_snapshot` | `mcp:read` |
| `get_educational_topic`, `get_learning_path`, `get_learning_progress` | `mcp:read` |
| `simulate_investment` | `mcp:simulate` |
| `mark_topic_completed` | `mcp:write` |

Fuera del MVP a propósito: `execute_trade`, `transfer_money`, `withdraw_funds`.

### Seguridad

- Cada tool valida su scope con `tokenCan()`; la ruta `/mcp/banorte` va con `auth:api` + `throttle:mcp`.
- `get_financial_profile` y `get_financial_goals` devuelven un resumen por defecto; los montos exactos solo con `detail: "exact"`.
- Cada `tools/call` queda en `audit_logs` (solo metadata, nunca montos exactos).
- `/api/market-data/*` protegido con `auth:api` y `throttle:market-data`.

## Puesta en marcha

Requisitos: PHP 8.3+, Composer, Node 20+.

```bash
composer setup                       # instala dependencias, crea .env, key, migra y compila assets
php artisan passport:keys            # llaves de Passport (no se versionan)
php artisan passport:client --personal --name="Norti"
```

> No uses `passport:install`: republica migrations de OAuth que este proyecto ya tiene.
> En Windows sin la extensión `sodium`: `composer install --ignore-platform-req=ext-sodium`.

Variables relevantes en `.env` (ver `.env.example`):

| Variable | Uso |
|---|---|
| `DB_*` | SQLite por defecto; connection string de Supabase para Postgres ([guía](docs/deployment/supabase-setup.md)) |
| `TWELVE_DATA_API_KEY` | Datos de mercado reales |
| `OPENAI_API_KEY` / `ANTHROPIC_API_KEY` | Proveedor del agente |
| `MCP_RATE_LIMIT_PER_MINUTE` | Límite del MCP |
| `MCP_LOOPBACK_URL` | Ver nota de `/chat` abajo |

### Ejecutar

```bash
composer dev                         # servidor + assets
php artisan db:seed --class=Database\\Seeders\\DemoSeeder   # usuario demo@banorte.local / password
```

**Nota sobre `/chat`:** hace un *self-loopback* HTTP a `/mcp/banorte` dentro de la misma request. Un `php artisan serve` de un solo hilo (siempre en Windows) se bloquea a sí mismo. Solución: levantar un segundo proceso dedicado al MCP y apuntar a él.

```bash
php artisan serve --port=8001                               # solo /mcp/banorte
MCP_LOOPBACK_URL=http://localhost:8001 php artisan serve    # la app en :8000
```

### Probar el MCP sin frontend

```bash
php artisan mcp:inspector /mcp/banorte                      # UI interactiva
php artisan mcp:client-tools                                # list_tools()
php artisan mcp:client-call get_market_snapshot --arguments='{"symbols":["AAPL"]}'
php artisan mcp:demo-agent "¿Cuál es la cotización de AAPL?"   # agente real
```

Los comandos `mcp:*` requieren `php artisan serve` corriendo. Guion completo de demo: [`docs/development/demo-script.md`](docs/development/demo-script.md).

### Tests

```bash
composer test
vendor/bin/pint --test
```

Los tests usan `RefreshDatabase`: no los corras contra una base con datos reales.

## Documentación

- [`docs/architecture/`](docs/architecture) — alcance, arquitectura, modelo de dominio, contrato A2UI
- [`docs/decisions/`](docs/decisions) — ADRs (Laravel, Twelve Data, MCP como capa de interfaz, `get_financial_goals`, desacople del proveedor de IA, …)
- [`docs/development/`](docs/development) — roadmap, demo, auditoría, pendientes de A2UI
- [`CLAUDE.md`](CLAUDE.md) / [`AGENTS.md`](AGENTS.md) — contexto para agentes de código

## Fuera de alcance

Banca real (cuentas, transferencias, pagos), trading real, asesoría financiera regulada, cobertura universal de mercados.

## Equipo

HackMTY 2026 — Integrantes A (dominio y servicios), B (MCP e infraestructura), C (datos de mercado y agente), D (educación y producto).
