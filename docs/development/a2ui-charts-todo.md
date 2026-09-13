# TODO — Gráficas A2UI

Lo que falta para que las gráficas diseñadas en Figma (página [Charts](https://www.figma.com/design/XPC8ZTeOL8dKf8Nl8ZN3fS?node-id=58-10)) lleguen a la app. Reglas, contrato y brechas: [`docs/architecture/a2ui-components.md`](../architecture/a2ui-components.md#gráficas).

**Estado:** diseño listo en Figma (6 gráficas integradas en su organismo) · contrato `props.chart` implementado y las 6 gráficas armadas en backend (PRs #35/#36/#38) · Blade sin empezar.

## 1. Backend — `props.chart`

Regla: las series se arman en el Service. La tool solo delega.

- [x] **Brecha 13 · helper compartido** (M3) — `App\Mcp\Support\ChartData` (no `app/Support/Charts/ChartBuilder.php` como se proponía aquí -- vive junto a `ToolAction`, mismo patrón). Test unitario: `tests/Unit/Mcp/Support/ChartDataTest.php`.
- [x] **Brecha 14 · `simulation_result_card`** (M2 · Integrante A) — `InvestmentSimulationServiceAdapter::simulate()` agrupa la salida de `project()` en ≤ 10 periodos → `stacked_column`. Test: `tests/Feature/Mcp/SimulateInvestmentToolTest.php`.
- [x] **Brecha 15 · `asset_info_card`** (M4 · Integrante C) — `GetAssetInformation` agrega la serie de cierres (`timeSeries($symbol, '1day', ['outputsize' => 30])`); `timeSeries()` ahora cachea en `TwelveDataMarketDataProvider` como `quote()`. Si falla, `chart = null` y la ficha responde igual. Test con `MockMarketDataProvider` y `Http::fake`.
- [x] **Brecha 16 · `market_snapshot_grid`** (M4 · Integrante C) — `percent_change` normalizado a float en `quote()` y agregado al Mock → `column`.
- [x] **Brecha 17 · `portfolio_summary`** (M2 · Integrante A) — `unrealized_gain_pct` por holding, solo `valuation_source = market` → `bar` con `excluded[]`.
- [x] **Brecha 18 · `financial_profile_card`** (M2 · Integrante A) — `monthly_savings_capacity` solo con `detail = exact` → `waterfall` con `start` / `end`. El test de `summary` confirma que sigue sin montos ni `chart`.
- [x] **Brecha 19 · `risk_analysis_panel`** (decisión M2, parcial) — `scatter` con los 3 perfiles (calibrados en `investment_rules.php`) y `reference_x = allocation_by_asset_type.accion`. El rendimiento esperado *del portafolio real* por `asset_type` sigue pendiente de esa decisión de M2.
- [x] **Brecha 8** (M2) — `App\Services\Support\CurrencyConverter` + `config/currency.php`: moneda base antes de sumar USD + MXN, en `RiskAnalysisServiceAdapter` y `EloquentPortfolioService`.
- [x] **Tests por tool** (M3) — `props.chart` presente con datos válidos, `null` sin datos/cuando el proveedor falla, y el audit log sigue sin registrar montos de la serie (ver `LogsToolInvocation` -- no cambió, los charts no se loggean).

## 2. Frontend Blade (M8 · Integrante D)

- [ ] Agregar los tokens `--color-chart-*` al `@theme inline` de `resources/css/app.css` (bloque completo en el doc).
- [ ] `resources/views/components/chart/`: `column`, `bar`, `waterfall` y `stacked-column` en HTML + CSS; `line` y `scatter` en SVG inline; `legend-item` y `state` (Loading / Empty).
- [ ] Un solo helper de escala (`(value - min) / (max - min) * 100`). Nada de sumas, promedios ni agrupaciones en la vista.
- [ ] Accesibilidad: `role="img"` + `aria-label` con el resumen y `<details>` con la tabla de datos; etiquetas con signo.
- [ ] Integrar cada gráfica en su organismo según Figma y mostrar `Chart State` Loading mientras corre la tool.
- [ ] Probar con los payloads reales de `/mcp-test`: nombres largos, `null`, error por cuota, efectivo a valor facial.
- [ ] Revisar a 375 px (las gráficas se diseñaron a 512 px).

## 3. Figma

- [ ] `learning_path`: mostrar `recommended_topic` y `recommended_reason` (llegaron a `master` con M6).
- [ ] `learning_progress`: mostrar `category_gaps`.
- [ ] Variante móvil (343 px) de las 6 gráficas.
- [ ] Actualizar Getting Started y los `_Contract` cuando cambie el estado de las brechas 13–19.

## 4. QA antes de la demo

- [ ] Ejes: barras, columnas, apiladas y cascada inician en 0; la línea rotula su mínimo; ninguna gráfica tiene doble eje Y ni mezcla monedas.
- [ ] Menos es más: una gráfica por organismo, ≤ 10 categorías, un solo color de énfasis.
- [ ] Sin 3D, sombras ni degradados.
- [ ] Contraste: marcas ≥ 3:1 y texto ≥ 4.5:1.
- [ ] Ganancias nunca en rojo; pérdidas con signo.
