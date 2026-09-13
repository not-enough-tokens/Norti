# A2UI Components — contrato de UI

Documento de referencia para quien implemente el renderer Blade de los componentes A2UI (M5, M8). El diseño vive en Figma; este documento fija el contrato entre las tools MCP y la interfaz.

- **Archivo Figma:** [Banorte MCP — A2UI Components](https://www.figma.com/design/XPC8ZTeOL8dKf8Nl8ZN3fS)
- **Páginas:** Cover · Getting Started · Foundations · Icons · Atoms (4 páginas) · [Charts](https://www.figma.com/design/XPC8ZTeOL8dKf8Nl8ZN3fS?node-id=58-10) · A2UI Card · `A2UI / <component>` (una por organismo) · Shell · Education · Playground
- **Fuentes de diseño:** presentación oficial *Reto UI Generativa Banorte × Tec* (colores extraídos por conteo de uso) + imagen de referencia de dashboard, con el naranja cambiado por rojo Banorte.
- **Pendientes:** [`docs/development/a2ui-charts-todo.md`](../development/a2ui-charts-todo.md)

## Principio

Cada tool responde `Response::structured(['component' => ..., 'props' => ...])`. El agente elige el componente; Blade solo pinta los props.

1. **El componente no calcula.** Sumas, porcentajes, proyecciones y series llegan en props. Blade solo formatea (moneda, porcentaje, fechas) y convierte valores en píxeles.
2. **El nombre es el contrato.** El component set de Figma se llama igual que la llave `component`; cada capa se llama como su prop.
3. **Los estados son diseño.** Vacío, cargando, error y parcial existen en cada organismo.
4. **Ganancias ≠ rojo.** El rojo Banorte es identidad e interacción; ganancias usan `feedback-positive`, pérdidas llevan signo e ícono.
5. **Una gráfica solo si responde mejor que un número.** Máximo una por organismo; ver [Gráficas](#gráficas).

## Ciclo de interacción (`user_action`)

La regla 3 del reto exige al menos un flujo accionable y que la interacción regrese al agente. Toda acción de un componente emite este sobre (**propuesto**, ver brecha 1):

```json
{
  "type": "user_action",
  "component": "educational_topic",
  "action": "mark_completed",
  "params": { "topic_id": 4 }
}
```

El agente decide la siguiente tool; el componente nunca calcula el resultado por su cuenta.

**Flujos accionables ya soportados por el backend:**

- **Simulación:** `simulation_result_card` → chip de plazo u opción de perfil → `simulate_investment` con otros params → la tarjeta cambia (`State=Recalculating` → `Result`).
- **Cambio real:** `educational_topic` → «Marcar como completado» → `mark_topic_completed` (escribe `educational_topic_user.completed_at`) → `learning_topic_completed` → «Ver mi progreso» → `learning_progress`.

## Catálogo

| `component` | Tool · scope | Variantes en Figma | Gráfica | Acciones (`user_action`) |
|---|---|---|---|---|
| `risk_analysis_panel` | `analyze_portfolio` · `mcp:read` | `State=With Holdings \| No Holdings` | Dispersión (With Holdings) | Simular con perfil recomendado · Aprender a diversificar |
| `portfolio_summary` | `get_portfolio` · `mcp:read` | `State=Default \| Empty` | Barras horizontales (Default) | Tocar Holding Row → `get_asset_information` · Analizar riesgo |
| `financial_profile_card` | `get_financial_profile` · `mcp:read` | `Detail=Summary \| Exact \| No Profile` | Cascada (solo Exact) | Simular con mi perfil · Ver cifras exactas (post-MVP) |
| `simulation_result_card` | `simulate_investment` · `mcp:simulate` | `State=Result \| Recalculating` | Columnas apiladas (Result) · Chart State Loading (Recalculating) | Option Chip (plazo) y Option Row (perfil) → `resimulate` |
| `asset_info_card` | `get_asset_information` · `mcp:read` | `Catalog=In Catalog \| Not In Catalog` | Línea (ambas) | Simular inversión en `{symbol}` |
| `market_snapshot_grid` | `get_market_snapshot` · `mcp:read` | `State=Default \| All Failed` | Columnas (Default) | Tocar Quote Tile → `get_asset_information` · Reintentar |
| `learning_path` | `get_learning_path` · `mcp:read` | `State=Default \| Empty` | — | Tocar Topic Row → `get_educational_topic` · Ver mi progreso |
| `educational_topic` | `get_educational_topic` · `mcp:read` | `Context=General \| Recommended` | — | Marcar como completado → `mark_topic_completed` · Preguntar al asistente |
| `learning_progress` | `get_learning_progress` · `mcp:read` | `State=In Progress \| Not Started \| Complete` | — (Progress Fill basta) | Continuar con el siguiente tema → `get_learning_path` |
| `learning_topic_completed` | `mark_topic_completed` · `mcp:write` | `State=Saving \| Completed` | — | Ver mi progreso → `get_learning_progress` · Siguiente tema |
| `tool_error` | cualquier `Response::error` | `Kind=Scope Denied \| Market Data \| Generic` | — | Iniciar nueva sesión (`POST /mcp/token`) · Reintentar |

Cada página `A2UI / <component>` en Figma trae un frame `_Contract` con el mapeo props → capas, un JSON de ejemplo, la gráfica que le toca y las brechas que le aplican.

### Mapeo de props relevantes

- **`risk_analysis_panel`:** `allocation_by_asset_type[k]` → Allocation Bar Row (valor + ancho de barra); `recommended_allocation_by_asset_type[k]` → marcador (null → ocultar); `diversification_score` → Score Meter; `concentration_warning` → Alert Warning; `priced_with_live_market_data = false` → Alert Neutral; `chart` → Chart / Scatter.
- **`portfolio_summary`:** una Holding Row por posición; `valuation_source` `market` → Gain/Loss/Flat según signo de `unrealized_gain`, `face_value` → Face Value, `null` → Unpriced; `chart` → Chart / Bar.
- **`financial_profile_card`:** `detail = summary` solo categorías (`risk_tolerance`, `investment_horizon_months`, `savings_rate_category`); `detail = exact` → Stat `savings` + Chart / Waterfall (`monthly_income` → `monthly_expenses` → `monthly_savings_capacity`).
- **`simulation_result_card`:** `projected_value` / `projected_gain` → Stat; `assumed_annual_rate` es fracción (0.09 → «9.0%»); `chart` → Chart / Stacked Column.
- **`asset_info_card`:** `quote.*` → Stat y franja de cotización; `chart` → Chart / Line.
- **`market_snapshot_grid`:** `quotes[SYMBOL]` con datos → Quote Tile OK; `quotes[SYMBOL].error` → Quote Tile Error; `chart` → Chart / Column.
- **`learning_path`:** `topics[]` → una Topic Row por tema; `is_completed` → `Status=Completed`, `category` → ícono + etiqueta. `recommended_topic` (con `recommended_reason`) → fila recomendada; el agente narra el porqué (pendiente en Figma).
- **`learning_progress`:** `completion_percentage` → Stat + Progress Fill (color `action-primary`: progreso no es ganancia); `completed_topics` / `total_topics` → «1 de 5»; `category_gaps[]` → categorías sin avance (pendiente en Figma).

## Gráficas

Página **Charts** en Figma: frame `_Guidelines`, átomos (`Chart Legend Item`, `Chart State`) y seis componentes `Chart / *`. Una gráfica es un componente A2UI más: recibe en `props.chart` series ya calculadas por el backend y solo las dibuja.

### Reglas

**1. Menos es más**

- Una pregunta por gráfica; si un número la responde mejor, usa un Stat.
- Etiquetas directas en lugar de leyenda y eje cuando hay pocas marcas. Leyenda solo con 2 o más series.
- Sin rejilla en barras, columnas, apiladas y cascada; rejilla tenue solo en línea y dispersión.
- Máximo 10 categorías o periodos y 3 segmentos apilados.
- Un solo color de énfasis por gráfica.

**2. Elige el eje correcto**

- Barras, columnas, apiladas y cascada empiezan en 0: se lee longitud.
- La línea puede usar un rango ajustado: se lee pendiente. Se rotula el mínimo y no se dibuja línea base.
- El tiempo va en X, de izquierda a derecha.
- Barras horizontales para etiquetas largas o rankings.
- Nunca dos ejes Y ni monedas mezcladas en un eje: se convierte o se grafica %.
- Dominio y marcas los decide el backend.

**3. Evita gráficos 3D**

- Todo plano: sin perspectiva, biseles, sombras ni degradados.
- El 3D deforma las longitudes y áreas que el ojo compara.
- Nada de pastel o dona 3D; para partes de un todo, barras o columnas apiladas.
- Sin animación decorativa: la transición solo acompaña un cambio de datos (`Recalculating` → `Result`).

**Color y accesibilidad**

- El color nunca es la única señal: toda marca lleva etiqueta con signo (+/−) y valor. En gráficas, el signo sustituye al ícono de pérdida.
- `chart/highlight` (rojo Banorte) resalta un solo elemento; las ganancias siempre van en `chart/positive`.
- Marcas con contraste ≥ 3:1 contra el fondo (`chart/muted` = ink-400, 3.3:1, es el mínimo); texto con los tokens `text/*`.

### Catálogo de gráficas

| Tipo | Componente Figma | Organismo | Pregunta que responde | Eje | Datos hoy |
|---|---|---|---|---|---|
| Columnas | `Chart / Column` | `market_snapshot_grid` | ¿Qué subió y qué bajó hoy? | Y desde 0, divergente | `percent_change` llega crudo de Twelve Data; el Mock no lo trae (brecha 16) |
| Barras horizontales | `Chart / Bar` | `portfolio_summary` | ¿Qué posición rinde más? | X desde 0 | `unrealized_gain` y `cost_basis` existen; falta el % (brecha 17) |
| Línea | `Chart / Line` | `asset_info_card` | ¿Hacia dónde va el precio? | Y ajustado y rotulado, tiempo en X | `timeSeries()` existe en el proveedor, ninguna tool lo expone (brecha 15) |
| Cascada | `Chart / Waterfall` | `financial_profile_card` (exact) | ¿Cuánto me queda al mes? | Y desde 0 | ingresos y gastos existen; falta `monthly_savings_capacity` (brecha 18) |
| Columnas apiladas | `Chart / Stacked Column` | `simulation_result_card` | ¿Cuánto es mío y cuánto es rendimiento? | Y desde 0 | `project()` calcula la serie y el adapter la descarta (brecha 14) |
| Dispersión | `Chart / Scatter` | `risk_analysis_panel` | ¿Más riesgo me da más rendimiento? | X e Y rotulados, un eje Y | perfiles reales en `config/investment_rules.php`; falta el rendimiento del portafolio (brecha 19) |

**Sin gráfica, a propósito:** `tool_error`, `learning_topic_completed`, `educational_topic`, `financial_profile_card` en `summary` (nunca muestra montos) y `learning_progress` (un Progress Fill basta).

**Estados:** `Chart State` — `Loading` mientras corre la tool (ya usado en `simulation_result_card` `Recalculating`) y `Empty` cuando `props.chart` no trae datos suficientes.

### Contrato `props.chart` (propuesto, brecha 13)

```json
{
  "chart": {
    "type": "column | bar | line | waterfall | stacked_column | scatter",
    "unit": "percent | currency | count",
    "currency": "MXN",
    "x_axis": { "type": "category | time | linear", "domain": [0, 100], "ticks": [0, 50, 100] },
    "y_axis": { "domain": [0, 12000], "ticks": [0, 6000, 12000] },
    "series": [{ "key": "gain", "role": "positive | negative | primary | muted | highlight | total" }],
    "data": [],
    "excluded": [{ "key": "CETES28", "reason": "unpriced" }]
  }
}
```

- `chart: null` (o ausente) → no se dibuja nada.
- `label` solo cuando el texto es dato (nombre de activo, fecha). Los textos de interfaz («Ingresos», «Mes 3», «Moderado») los pone Blade a partir de `key`, igual que `asset_type` → «Acción».
- `role` / `kind` eligen el token de color; el backend nunca manda colores.

**Forma de `data` por tipo** (datos de ejemplo del seed de `/mcp-test`):

```jsonc
// column — market_snapshot_grid
{ "type": "column", "unit": "percent", "y_axis": { "domain": [-2, 2] },
  "data": [{ "key": "AAPL", "value": 1.24 }, { "key": "MSFT", "value": -0.82 }, { "key": "CETES28", "value": null }] }

// bar — portfolio_summary
{ "type": "bar", "unit": "percent", "x_axis": { "domain": [0, 40] },
  "data": [{ "key": "MSFT", "label": "Microsoft Corp.", "value": 38.5 }, { "key": "AAPL", "label": "Apple Inc.", "value": 5.69 }],
  "excluded": [{ "key": "CETES28", "reason": "unpriced" }] }

// line — asset_info_card (22 puntos en orden cronológico)
{ "type": "line", "unit": "currency", "currency": "USD",
  "x_axis": { "type": "time", "ticks": ["2026-08-13", "2026-08-27", "2026-09-11"] },
  "y_axis": { "domain": [175, 195], "ticks": [175, 185, 195] },
  "data": [{ "x": "2026-08-13", "y": 181.20 }, { "x": "2026-09-11", "y": 190.24 }] }

// waterfall — financial_profile_card (detail = exact)
{ "type": "waterfall", "unit": "currency", "currency": "MXN", "y_axis": { "domain": [0, 40000] },
  "data": [
    { "key": "monthly_income", "kind": "total", "value": 35000, "start": 0, "end": 35000 },
    { "key": "monthly_expenses", "kind": "decrease", "value": -22000, "start": 35000, "end": 13000 },
    { "key": "monthly_savings_capacity", "kind": "total", "value": 13000, "start": 0, "end": 13000 }
  ] }

// stacked_column — simulation_result_card ($10,000 · 12 meses · moderado)
{ "type": "stacked_column", "unit": "currency", "currency": "MXN", "y_axis": { "domain": [0, 12000] },
  "series": [{ "key": "principal", "role": "muted" }, { "key": "gain", "role": "positive" }],
  "data": [
    { "key": 3, "values": { "principal": 10000, "gain": 226.69 }, "total": 10226.69 },
    { "key": 12, "values": { "principal": 10000, "gain": 938.07 }, "total": 10938.07 }
  ] }

// scatter — risk_analysis_panel
{ "type": "scatter",
  "x_axis": { "unit": "percent", "domain": [0, 100], "ticks": [0, 50, 100] },
  "y_axis": { "unit": "percent", "domain": [0, 15], "ticks": [0, 5, 10, 15] },
  "data": [
    { "key": "conservative", "x": 0, "y": 6.5 },
    { "key": "moderate", "x": 20, "y": 9.0, "emphasis": "user_profile" },
    { "key": "aggressive", "x": 60, "y": 12.5 }
  ],
  "reference_x": { "key": "portfolio", "value": 28.47 } }
```

### Qué decide cada capa

| Backend (Service, nunca la tool) | Blade |
|---|---|
| Si hay gráfica y de qué tipo (`chart` o `null`) | Color por `role` / `kind` con tokens `chart/*` |
| Dominio y marcas de los ejes, con holgura | Formato: moneda, %, fechas cortas, signo |
| Orden y exclusiones (`excluded[]` con motivo) | Geometría: valor → píxel con escala lineal |
| Agregados: totales, `start` / `end`, periodos, porcentajes | Textos de interfaz a partir de `key` |
| Agrupar series largas (máx. 10 categorías o periodos) | Estados: `Chart State` Loading / Empty |

La única operación permitida en la vista es la escala: `pct = (value - domain[0]) / (domain[1] - domain[0]) * 100`.

### Implementación en Blade

- **Sin librería JS de gráficas:** no agrega dependencia y el render sigue siendo del servidor, como el resto de A2UI.
- **Barras, columnas, apiladas y cascada:** HTML + CSS (flex con `style="height: {{ $pct }}%"`), el mismo patrón que Allocation Bar Row.
- **Línea y dispersión:** SVG inline con `viewBox`, `vector-effect="non-scaling-stroke"` en trazos y textos fuera de la parte que escala.
- **Accesibilidad:** `role="img"` con `aria-label` que resume la gráfica y `<details>` con la tabla de datos.
- **Clases:** Tailwind genera utilidades desde los tokens (`bg-chart-positive`, `fill-chart-primary`, `stroke-chart-grid`).
- **Tamaño:** diseñadas a 512 px, el ancho del cuerpo de A2UI Card; en Figma tienen geometría de ejemplo porque Figma no permite redimensionar capas dentro de instancias, así que sus valores no se editan desde la instancia.

## Átomos, marco y shell

- **Marco:** `A2UI Card` — eyebrow «Componente generado · …», badge «vía `tool`», título, `SLOT` Content, acciones (1 Primary + 1 Secondary), pie «Datos sintéticos · no es asesoría financiera».
- **Texto:** Eyebrow, Badge (6 tonos).
- **Interacción:** Button (Primary / Secondary / Ghost × Default / Hover / Disabled), Option Row (patrón de la lámina 1), Option Chip.
- **Datos:** Stat, Key Value Row, Asset Type Tag, Risk Level Badge, Allocation Bar Row, Score Meter, Progress Fill, Target Marker, Holding Row, Quote Tile.
- **Gráficas:** Chart Legend Item, Chart State (Loading / Empty), Chart / Column, Chart / Bar, Chart / Line, Chart / Waterfall, Chart / Stacked Column, Chart / Scatter.
- **Feedback:** Alert (Highlight / Warning / Error / Neutral), Empty State, Skeleton.
- **Shell:** App Sidebar + Nav Item, Top Bar, Intent Composer, User Intent Bubble, Agent Message, Tool Call Status.
- **Educación (M6):** Topic Row, Topic Detail Panel, Learning Path — los usan las vistas web y los organismos `learning_*` / `educational_topic`.
- **Íconos:** Feather (MIT), trazo 2px; en Blade usar el SVG inline con `stroke="currentColor"`.

### Notas de implementación en Blade

- Las listas (holdings, quotes, temas) son un `@foreach` sobre el prop con el sub-componente de fila.
- Ancho de barras: `style="width: {{ $pct }}%"` con el valor exacto. En Figma, Progress Fill y Target Marker están cuantizados al 5% porque Figma no permite cambiar tamaños dentro de instancias.
- Skeleton = `bg-bg-subtle animate-pulse`.
- Placeholder del Intent Composer usa `text-muted` (5.6:1), no `text-disabled`.

## Tokens

Colores extraídos de la presentación oficial por conteo de uso (`#EB0029` aparece 106 veces). Tipografía: **Montserrat** (plantilla corporativa Banorte) para títulos, cifras y eyebrows; **Instrument Sans** (ya en `app.css`) para cuerpo y tablas. Solo modo claro.

Bloque para `resources/css/app.css` (los alias semánticos usan `@theme inline` porque referencian otras variables):

```css
@theme {
    --font-display: 'Montserrat', ui-sans-serif, system-ui, sans-serif;

    --color-banorte-50: #FDECEF;
    --color-banorte-100: #F6C9D2;
    --color-banorte-200: #E8A6B3;
    --color-banorte-300: #D4677C;
    --color-banorte-500: #EB0029;
    --color-banorte-700: #B4001F;
    --color-banorte-900: #6E0014;
    --color-coral-400: #FF4A64;

    --color-ink-50: #F4F1F1;
    --color-ink-100: #F6EDEF;
    --color-ink-200: #E9DFE1;
    --color-ink-400: #9A8B8F;
    --color-ink-600: #7A6165;
    --color-ink-700: #53565A;
    --color-ink-800: #2A0710;
    --color-ink-900: #1A0F12;
    --color-ink-950: #150609;

    --shadow-card: 0 1px 2px rgb(26 15 18 / 6%), 0 2px 8px rgb(26 15 18 / 4%);
    --shadow-panel: 0 12px 32px -8px rgb(26 15 18 / 8%);
}

@theme inline {
    --color-bg-canvas: var(--color-ink-50);
    --color-bg-surface: var(--color-white);
    --color-bg-subtle: var(--color-ink-100);
    --color-bg-inverse: var(--color-ink-900);
    --color-bg-inverse-raised: var(--color-ink-800);
    --color-bg-tint: var(--color-banorte-50);
    --color-bg-disabled: var(--color-ink-100);
    --color-bg-brand-deep: var(--color-banorte-900);

    --color-text-primary: var(--color-ink-900);
    --color-text-body: var(--color-ink-700);
    --color-text-muted: var(--color-ink-600);
    --color-text-disabled: var(--color-ink-400);
    --color-text-inverse: var(--color-white);
    --color-text-brand: var(--color-banorte-500);
    --color-text-on-tint: var(--color-banorte-700);
    --color-text-on-brand: var(--color-white);
    --color-text-accent-on-dark: var(--color-coral-400);

    --color-border-default: var(--color-ink-200);
    --color-border-strong: var(--color-ink-900);
    --color-border-selected: var(--color-banorte-500);
    --color-border-highlight: var(--color-banorte-100);
    --color-border-warning: var(--color-amber-200);
    --color-border-on-dark: var(--color-banorte-900);

    --color-action-primary: var(--color-banorte-500);
    --color-action-primary-hover: var(--color-banorte-700);
    --color-action-disabled: var(--color-ink-100);

    --color-feedback-positive-bg: var(--color-emerald-50);
    --color-feedback-positive-text: var(--color-emerald-700);
    --color-feedback-negative-bg: var(--color-banorte-50);
    --color-feedback-negative-text: var(--color-banorte-700);
    --color-feedback-warning-bg: var(--color-amber-50);
    --color-feedback-warning-text: var(--color-amber-800);

    --color-icon-default: var(--color-ink-700);
    --color-icon-muted: var(--color-ink-600);
    --color-icon-brand: var(--color-banorte-500);
    --color-icon-inverse: var(--color-white);
    --color-icon-positive: var(--color-emerald-700);
    --color-icon-negative: var(--color-banorte-700);
    --color-icon-warning: var(--color-amber-800);
    --color-icon-disabled: var(--color-ink-400);
    --color-icon-on-dark-muted: var(--color-ink-400);
    --color-icon-accent-on-dark: var(--color-coral-400);

    --color-chart-asset-accion: var(--color-banorte-500);
    --color-chart-asset-fondo: var(--color-banorte-700);
    --color-chart-asset-bono: var(--color-banorte-300);
    --color-chart-asset-efectivo: var(--color-banorte-200);
    --color-chart-track: var(--color-ink-100);
    --color-chart-marker: var(--color-ink-900);
    --color-chart-positive: var(--color-emerald-700);
    --color-chart-negative: var(--color-banorte-700);
    --color-chart-primary: var(--color-ink-900);
    --color-chart-muted: var(--color-ink-400);
    --color-chart-highlight: var(--color-banorte-500);
    --color-chart-total: var(--color-ink-700);
    --color-chart-grid: var(--color-ink-200);
    --color-chart-baseline: var(--color-ink-600);
}
```

Spacing y radius usan la escala por defecto de Tailwind 4 (`spacing/4` = `p-4`, `radius/2xl` = `rounded-2xl`).

**Contraste (sobre blanco):** `banorte-500` 4.6:1 · `banorte-700` 7.1:1 · `ink-700` 7.4:1 · `ink-600` 5.6:1 · `ink-400` 3.3:1 (solo placeholder / disabled y marcas de gráfica de contexto).

## Brechas de contrato

| # | Estado | Brecha | Afecta | Propuesta |
|---|---|---|---|---|
| 1 | Abierta | Ninguna tool devuelve `actions` ni existe endpoint que regrese la interacción al agente | todos | `props.actions[] { id, label, tool, params }` + endpoint de `user_action` (M5). **Crítico para la regla 3 del reto** |
| 2 | **Resuelta** | No había «cambio real» | education | `mark_topic_completed` (`mcp:write`) escribe `completed_at` |
| 3 | Abierta | `portfolio_summary` sin totales | portfolio | `totals` en `EloquentPortfolioService` |
| 4 | Abierta | `risk_tolerance` crudo en el perfil | profile | Normalizar con `RiskAnalysisService::suggestRiskProfile()` |
| 5 | Reemplazada | Quotes sin `percent_change` ni series | asset, snapshot | Se divide en las brechas 15 (series) y 16 (`percent_change`) |
| 6 | Documentada | Unidades mixtas (allocation 0–100, score 0–1, tasa fracción) | risk, simulation | Helper de formato en Blade |
| 7 | **Resuelta** | Progreso educativo requería contar en el front | education | `get_learning_progress` entrega los conteos (ver brecha 12) |
| 8 | Abierta | La distribución suma USD y MXN sin conversión (el seed mezcla AAPL/MSFT en USD con CETES28 en MXN) | risk, gráficas | Normalizar a una moneda base en `RiskAnalysisServiceAdapter`; también afecta el eje X de la dispersión |
| 9 | Abierta | `Response::error` solo trae texto | tool_error | Error estructurado `{ code, message }` con los mismos `result_summary` del audit log |
| 10 | Abierta | `learning_path` ya trae `topics` y `recommended_topic`, pero siguen siendo modelos Eloquent crudos con `content` y timestamps | education | `props: { topics: [ { id, title, category, estimated_minutes, is_completed } ], recommended_topic: { id, title, recommended_reason } }` |
| 11 | Abierta | `educational_topic` no trae `is_completed` | education | Incluirlo para poder mostrar el estado del tema |
| 12 | Abierta | `GetLearningProgress` calcula conteos y porcentaje dentro de la tool | education | Mover a `FinancialEducationService` (las tools solo delegan, per `CLAUDE.md`) |
| 13 | Abierta | Ninguna tool devuelve series para gráficas | todas las gráficas | `props.chart` (ver [contrato](#contrato-propschart-propuesto-brecha-13)) armado en el Service con un helper compartido; `null` si no aplica |
| 14 | Abierta | `InvestmentSimulationServiceAdapter` descarta la serie de `project()` (solo usa `end()`) | simulation | Agrupar en ≤ 10 periodos `{ key, values: { principal, gain }, total }` |
| 15 | Abierta | Ninguna tool expone `timeSeries()` y no tiene caché (cuota de 8 req/min) | asset | Serie de cierres en `get_asset_information` con caché como `quote()`; si falla, `chart: null` sin tumbar la ficha |
| 16 | Abierta | `percent_change` llega como string crudo de Twelve Data y el Mock no lo trae | snapshot | Normalizar a float en `quote()` y agregarlo a `MockMarketDataProvider` |
| 17 | Abierta | Holdings sin `unrealized_gain_pct` | portfolio | Calcularlo en `EloquentPortfolioService` solo para `valuation_source = market` |
| 18 | Abierta | El perfil exacto no trae `monthly_savings_capacity` | profile | Exponer `ProfileService::monthlySavingsCapacity()` solo con `detail = exact`; el resumen sigue en categorías |
| 19 | Abierta | No hay rendimiento esperado para un portafolio real, solo por perfil | risk | Decisión de M2: `expected_annual_return` por `asset_type` en `investment_rules`; mientras, «Tu portafolio» es una referencia en X |

## Decisiones

- **Code Connect no se usó:** requiere plan Organization/Enterprise (la cuenta es tier student) y Blade no es un framework label soportado. Cada página de organismo documenta el contrato en su frame `_Contract`.
- **`simulation_result_card` usa `State=Result | Recalculating`** en lugar de variantes por perfil de riesgo: muestra el flujo accionable (tocar un chip → la tool corre → la tarjeta cambia). El perfil se elige con Option Rows.
- **`asset_info_card` sin pestañas:** el contrato solo trae una sección de contenido.
- **Íconos locales:** Simple Design System trae íconos Feather, pero enlazados a sus propios tokens; se reconstruyeron con los mismos SVG y los tokens `icon/*` del proyecto.
- **Una gráfica por organismo como máximo,** y solo donde responde una pregunta que un número no responde. Los organismos de error, confirmación, lectura y progreso no llevan gráfica.
- **`financial_profile_card` Exact:** la cascada reemplaza los Stats `monthly_income` y `monthly_expenses`, que repetían los mismos montos; `savings` es un saldo, no un flujo, y se queda como Stat.
- **El portafolio grafica % y no montos:** mezcla USD y MXN (brecha 8) y un porcentaje sobre costo es comparable sin conversión.
- **La línea de precio es neutra (`chart/primary`):** roja se leería como pérdida y verde como ganancia del usuario.
- **Dispersión con referencia en X:** «Tu portafolio» se dibuja donde hay dato real (% en acciones) en lugar de inventar su rendimiento esperado.
