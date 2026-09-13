# TODO — Gráficas A2UI

Lo que falta para que las gráficas diseñadas en Figma (página [Charts](https://www.figma.com/design/XPC8ZTeOL8dKf8Nl8ZN3fS?node-id=58-10)) lleguen a la app. Reglas, contrato y brechas: [`docs/architecture/a2ui-components.md`](../architecture/a2ui-components.md#gráficas).

**Estado:** diseño listo en Figma (6 gráficas integradas en su organismo) · contrato `props.chart` propuesto · backend y Blade sin empezar.

## 1. Backend — `props.chart`

Regla: las series se arman en el Service. La tool solo delega.

- [ ] **Brecha 13 · helper compartido** (M3) — p. ej. `app/Support/Charts/ChartBuilder.php`: dominio con holgura, marcas, orden y `excluded[]`. Test unitario del dominio (barras siempre desde 0).
- [ ] **Brecha 14 · `simulation_result_card`** (M2 · Integrante A) — `InvestmentSimulationServiceAdapter::simulate()` agrupa la salida de `project()` en ≤ 10 periodos → `stacked_column`. Test: `principal + gain = total` y el último `total` = `projected_value`.
- [ ] **Brecha 15 · `asset_info_card`** (M4 · Integrante C) — `GetAssetInformation` agrega la serie de cierres (`timeSeries($symbol, '1day', ['outputsize' => 22])`); cachear `timeSeries()` en `TwelveDataMarketDataProvider` como `quote()`. Si falla, `chart = null` y la ficha responde igual. Test con `MockMarketDataProvider`.
- [ ] **Brecha 16 · `market_snapshot_grid`** (M4 · Integrante C) — normalizar `percent_change` a float en `quote()` y agregarlo al Mock → `column`.
- [ ] **Brecha 17 · `portfolio_summary`** (M2 · Integrante A) — `unrealized_gain_pct` por holding, solo `valuation_source = market` → `bar` con `excluded[]`.
- [ ] **Brecha 18 · `financial_profile_card`** (M2 · Integrante A) — `monthly_savings_capacity` solo con `detail = exact` → `waterfall` con `start` / `end`. Confirmar que el test de `summary` sigue sin montos.
- [ ] **Brecha 19 · `risk_analysis_panel`** (decisión M2) — rendimiento esperado por `asset_type`. Mientras tanto, `scatter` con los 3 perfiles y `reference_x = allocation_by_asset_type.accion`.
- [ ] **Brecha 8** (M2) — moneda base antes de sumar USD + MXN; afecta la dispersión y cualquier gráfica futura con montos.
- [ ] **Tests por tool** (M3) — `props.chart` presente con datos válidos, `null` sin datos, y el audit log sigue sin registrar montos de la serie.

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
