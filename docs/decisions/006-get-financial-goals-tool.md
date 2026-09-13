# ADR 006: Tool MCP `get_financial_goals` (7ª tool)

## Status

**Propuesto** — guardado para revisión futura. No implementado.

Requiere visto bueno explícito porque CLAUDE.md fija el catálogo de 6 tools como
decisión cerrada.

## Contexto

`FinancialGoal`, `InvestmentSimulationService::projectForGoal()` y
`evaluateGoal()` están construidos, corregidos y cubiertos por tests
(`tests/Feature/Services/InvestmentSimulationServiceTest.php`), pero **nada los
llama**: no hay MCP tool, ni ruta, ni vista. Es capacidad terminada e inalcanzable.

Al mismo tiempo, la arquitectura de onboarding ya declara *"dar seguimiento a una
meta"* como una de las tres intenciones a detectar tras el login. Hoy esa
intención no tiene nada detrás.

## El caso a favor

1. **Cierra un hueco que abrimos nosotros.** El onboarding promete detectar una
   intención que el sistema no puede atender.
2. **Costo marginal real.** Cero migraciones, cero dependencias, cero cambios a
   código de otros integrantes. El cálculo ya existe y está probado.
3. **Es la única tool que responde una pregunta personal.** Las otras seis
   responden "qué tengo" y "qué pasaría si"; esta responde *"¿voy a alcanzar mi
   meta?"* — el caso de uso con el que un juez se identifica directamente.
4. **Pasa la regla de auditoría de CLAUDE.md:** aporta a dominio financiero e
   interoperabilidad MCP; no introduce dependencias; no desplaza el núcleo.

## El caso en contra (honesto)

- **No es necesaria.** El MVP mínimo demostrable son 3–4 tools y ya hay 6. El
  proyecto se demuestra completo sin metas.
- **Reabre una decisión cerrada.** El catálogo de 6 fue una decisión de control
  de alcance, y aceptar una excepción vuelve más fácil aceptar la siguiente.
- **Toca datos sensibles.** Ver abajo — es la implicación de más peso.

## Implicación crítica: montos exactos

`FinancialGoal` expone `target_amount` y `current_amount`. La política de
CLAUDE.md es que el LLM **no** ve montos exactos por defecto (motivo por el que
`get_financial_profile` tiene `detail: "summary" | "exact"`).

Una tool de metas sin ese cuidado violaría una decisión de privacidad ya tomada.
El diseño propuesto la respeta en vez de abrir una excepción:

- **Default (`summary`):** progreso en porcentaje, meses restantes,
  `reaches_goal`, `is_overdue`. Sin montos.
- **`detail: "exact"`:** montos, solo si el usuario lo pidió explícitamente.
- **Audit log:** nunca montos, solo cuántas metas y el `detail` usado.

## Alcance propuesto

Read-only, scope `mcp:read`. **No** incluye `create_financial_goal`: es mutating
y CLAUDE.md ya lo colocó en post-MVP con autorización adicional — ahí sí habría
una razón de seguridad detrás de la decisión cerrada.

## Integración

| Archivo | Cambio |
|---|---|
| `app/Services/Contracts/FinancialGoalServiceContract.php` | nuevo |
| `app/Services/Financial/FinancialGoalServiceAdapter.php` | nuevo, envuelve `projectForGoal()`/`evaluateGoal()` |
| `app/Providers/DomainServiceProvider.php` | +1 binding |
| `app/Mcp/Tools/GetFinancialGoals.php` | nuevo |
| `app/Mcp/Servers/BanorteServer.php` | +1 en `$tools`, actualizar `#[Instructions]` |
| `CLAUDE.md` | +1 fila en la tabla del catálogo |

Estimado: ~4 tests nuevos (scope denegado, default `summary`, `exact`, auditoría).

## Alternativa si se rechaza

Quitar *"dar seguimiento a una meta"* de las intenciones del onboarding, para que
el diseño no prometa una capacidad que no existe. El hueco se cierra por el otro
lado.
