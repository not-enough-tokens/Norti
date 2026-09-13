# Auditoría del código existente

Bitácora de la auditoría del código ya escrito. No es un backlog de features
nuevas: aquí solo van defectos encontrados en lo que ya está construido, con
evidencia de cómo se verificaron y en qué estado quedaron.

**Convención:** todo hallazgo se verifica empíricamente antes de darlo por
cierto (test que falla, salida real, `route:list`). Los que resultan falsos
también se registran — saber que algo *no* está roto tiene valor.

Última actualización: PR [#18](https://github.com/not-enough-tokens/Banorte-MCP/pull/18).

---

## Resumen

| # | Hallazgo | Área | Severidad | Estado |
|---|---|---|---|---|
| 1 | `risk_tolerance` no reconocido rompía `analyze_portfolio` | M2 | Alta | ✅ Arreglado |
| 2 | Vocabulario de `asset_type` partido (inglés vs español) | M2 | Alta | ✅ Arreglado |
| 3 | `PortfolioService::createForUser()` nunca encontraba activos | M2 | Alta | ✅ Arreglado |
| 4 | Audit log registraba `ok` antes de correr el servicio | M7 | Media | ✅ Arreglado |
| 5 | Lookups de config fallaban con `TypeError` opaco | M2 | Baja | ✅ Arreglado |
| 6 | Meta vencida se reportaba como proyección válida | M2 | Alta | ✅ Arreglado |
| 7 | `FinancialGoalFactory` no existía | M1 | Media | ✅ Arreglado |
| 8 | Se podía cruzar meta de un usuario con perfil de otro | M2 | Alta | ✅ Arreglado |
| 9 | `get_market_snapshot` auditaba `ok` con todos los símbolos fallidos | M7 | Media | ✅ Arreglado |
| 10 | Símbolos repetidos gastaban cuota de TwelveData | M3 | Baja | ✅ Arreglado |
| 11 | Búsqueda de `Asset` sensible a mayúsculas | M3 | Media | ✅ Arreglado |
| 12 | `/api/market-data/*` sin auth ni rate limiting | M4/M7 | **Crítica** | ✅ Arreglado |
| 13 | Llamada muerta a `getFinancialContext()` en `/education` | M6 | Media | ✅ Arreglado |
| 14 | `efectivo` sin cubeta en las distribuciones recomendadas | M2 | Media | ✅ Arreglado (ADR 005, opción B) |
| — | Fuga del `apikey` de TwelveData al LLM | M4 | — | ❌ Falso positivo |
| 15 | Techo de TwelveData es por cuenta, no por usuario | M4 | Media | ⚠️ Limitación conocida |
| 16 | `efectivo` se intenta cotizar como símbolo de mercado | M2/M4 | Baja | 🔲 Pendiente |
| 17 | Metas financieras sin cablear a ninguna tool ni vista | M2/M3 | Media | 🔲 Pendiente |

---

## Arreglados

### 1. Un `risk_tolerance` no reconocido rompía `analyze_portfolio`

`RiskAnalysisService::suggestRiskProfile()` devolvía el valor guardado tal cual,
sin validarlo, y `assetAllocation()` era quien validaba y lanzaba. Como
`financial_profiles.risk_tolerance` es un `string` libre (sin enum ni check) y
`ProfileService::createOrUpdate()` no valida, cualquier valor era alcanzable.

**Verificado:** con `'Moderate'` guardado, la tool devolvía error y filtraba la
excepción interna al LLM (`"Perfil de riesgo inválido: Moderate"`).

**Arreglo:** la validación se movió a la entrada (`normalizeRiskLevel()`).
Un valor no reconocible ahora degrada al horizonte de inversión.

**Por qué importaba:** el flujo de onboarding va a ser el primero en escribir
ese campo.

### 2. Vocabulario de `asset_type` partido

`config/investment_rules.php` usaba llaves en inglés (`bond`/`fund`/`stock`)
mientras que `assets.asset_type` usa español (`accion`/`bono`/`fondo`/`efectivo`).
`RiskAnalysisServiceAdapter` traducía; los demás consumidores no.

**Arreglo:** se unificó todo en español en el config. El mapa
`ASSET_TYPE_TRANSLATIONS` del adapter se eliminó.

### 3. `PortfolioService::createForUser()` nunca encontraba activos

Consecuencia directa del #2: `Asset::where('asset_type', 'bond')` nunca empataba
contra una BD en español, caía en `continue` y creaba un portafolio **vacío sin
error**.

**Arreglo:** resuelto por el #2. Se agregó `tests/Feature/Services/PortfolioServiceTest.php`.

### 4. Audit log registraba `ok` antes de correr el servicio

En `AnalyzePortfolio`, `GetPortfolio` y `GetFinancialProfile`, la llamada al
servicio vivía **dentro del argumento** de `Response::structured()`, así que
`logToolCall(success: true)` corría primero. Si el servicio lanzaba, quedaba un
registro de auditoría diciendo `ok` para una llamada fallida.

**Verificado:** se revirtió el orden a propósito y el test de regresión falla.

### 5. Lookups de config con `TypeError` opaco

`expectedAnnualReturn(): float` leía `config()` sin verificar. Si `risk_levels`
y los mapas se desincronizaban, reventaba con un `TypeError` sin pistas.

**Arreglo:** ahora lanza una excepción que nombra la llave faltante.

### 6. Meta vencida se reportaba como proyección válida

El `max(1, ...)` de `projectForGoal()` convertía un plazo negativo en una
proyección de un mes **hacia el futuro**, indistinguible de una meta vigente.
`evaluateGoal()` devolvía entonces un `shortfall` como si aún se pudiera ahorrar.

**No era un bug de valor absoluto de Carbon** (hipótesis inicial): Carbon 3
devuelve diferencias con signo y el diff negativo llegaba bien. El defecto
estaba en el clamp.

**Arreglo:** regresa `is_overdue => true` con `months => 0` y sin proyección.

### 7. `FinancialGoalFactory` no existía

El modelo declara `HasFactory` pero la factory nunca se escribió, así que
`FinancialGoal::factory()` moría con `Class not found`. **Esto explica por qué
el módulo de metas no tenía un solo test: no se podía escribir uno.**

### 8. Se podía cruzar meta de un usuario con perfil de otro

`projectForGoal(FinancialGoal $goal, FinancialProfile $profile)` recibe dos
argumentos independientes y nada verificaba que fueran del mismo usuario.

**Arreglo:** lanza excepción si los `user_id` no coinciden.

### 9. `get_market_snapshot` auditaba `ok` con todos los símbolos fallidos

Los errores por símbolo se devuelven en el payload en vez de abortar, así que el
audit log nunca reflejaba el fallo.

**Arreglo:** distingue `ok` / `partial_market_data` / `market_data_unavailable`.

### 10. Símbolos repetidos gastaban cuota

`["AAPL","aapl","AAPL"]` hacía 3 llamadas contra el techo de 8 req/min.

### 11. Búsqueda de `Asset` sensible a mayúsculas

El catálogo guarda tickers en mayúsculas y el `=` de Postgres distingue, así que
`aapl` devolvía `local_asset => null` para un activo que sí estaba sembrado.

### 12. `/api/market-data/*` sin auth ni rate limiting — **crítico**

Las tres rutas estaban completamente abiertas. En Laravel 11+ el grupo `api`
**ya no incluye `throttle:api` por defecto**, cosa fácil de asumir mal.

No era solo lectura: son un proxy directo a TwelveData, así que un anónimo podía
agotar los 8 req/min del plan gratuito y tumbar el market data de toda la app.

**Verificado:** `route:list -v` mostraba solo el grupo `api`; al agregar el
middleware, los 9 tests existentes pasaron de verde a 401 de golpe.

**Arreglo:** `auth:api` + `throttle:market-data`.

### 13. Llamada muerta a `getFinancialContext()` en `/education`

`EducationalTopicController::index()` construía `$financialContext` (perfil +
metas + portafolios con `holdings.asset` eager-loaded) y lo pasaba a la vista,
que **nunca lo usaba**. Tres queries por carga de página cuyo resultado se
descartaba, y un `FinancialProfile` crudo con ingresos y gastos exactos sentado
en el scope de una vista Blade.

**Arreglo:** se quitó la llamada. `FinancialEducationIntegrationService` se
conserva intacto — la integración es intención real de M6, solo que todavía no
está construida y no debe costar queries mientras tanto.

### 14. `efectivo` sin cubeta en las distribuciones recomendadas

Ver [ADR 005](../decisions/005-cash-in-recommended-allocation.md). Resuelto con
la **opción B**: la recomendación conserva la calibración de Integrante A y se
rellena con `0` para los tipos de activo que no menciona, de modo que ambas
distribuciones tengan siempre las mismas llaves y sean comparables.

---

## Falsos positivos

### Fuga del `apikey` de TwelveData al LLM

**Hipótesis:** el mensaje de `ConnectionException` se propaga hasta el payload de
la tool, y los errores de cURL suelen incluir la URL completa — que lleva
`?apikey=...`.

**Resultado: no se filtra.** Guzzle recorta la query string del mensaje de error.
Verificado apuntando el cliente a `127.0.0.1:9` con una llave conocida y
buscándola en el mensaje resultante. No se cambió nada.

---

## Pendientes y limitaciones conocidas

### 15. El techo de TwelveData es por cuenta, no por usuario

`throttle:market-data` limita a cada cliente, pero el límite real de TwelveData
aplica a **toda la cuenta**. Ocho usuarios autenticados a 8 req/min siguen
agotando la cuota. Un throttle de Laravel no puede resolver esto; haría falta un
limitador global (o de plano un plan de pago).

### 16. `efectivo` se intenta cotizar como símbolo de mercado

`EloquentPortfolioService` pide precio para el `symbol` de todo holding,
incluido el de efectivo. Contra TwelveData real eso va a fallar siempre y marcar
el holding como no valuado. `MockMarketDataProvider` lo esconde porque cotiza
cualquier símbolo. Relacionado con el ADR 005, opción C.

### 17. Metas financieras sin cablear

`FinancialGoal`, `projectForGoal()` y `evaluateGoal()` funcionan y ahora tienen
tests, pero **nada los llama**: no hay MCP tool, ni ruta, ni vista. Es capacidad
construida y no expuesta.
