# Guion de demo (M8)

Preparado por el equipo de backend/MCP mientras el equipo de Figma/frontend arma la librería de componentes A2UI y la arquitectura del sitio (home, auth, chat). Nada de esto depende de que ese trabajo termine: usa `php artisan mcp:demo-agent` como interfaz temporal, y las mismas 10 tools son las que consumirá el chat real en cuanto exista.

## Setup

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DemoSeeder
php artisan serve
```

`DemoSeeder` (`database/seeders/DemoSeeder.php`) crea `demo@banorte.local` (password `password`) con:

- Perfil financiero: ingreso $35,000/mes, gasto $22,000/mes, ahorro $80,000, riesgo `moderate`, horizonte 60 meses.
- Portafolio con 3 activos (`AAPL`, `MSFT` -- acciones; `CETES28` -- bono): diversificado a propósito, para que la recomendación de educación no se dispare por concentración.
- **Sin metas financieras registradas** -- a propósito, para que `get_learning_path` recomiende por la regla `no_goals`, el punto de entrada más natural para narrar el "por qué" de una recomendación.
- Un tema de educación ya completado (`interes-compuesto`), para que el progreso no aparezca en 0%.

Es idempotente: correrlo varias veces no duplica nada.

Para obtener un token de agente real:

```bash
php artisan mcp:demo-agent "<pregunta>" --user=demo@banorte.local
```

(requiere `OPENAI_API_KEY` en `.env`, u otro proveedor vía `--provider`/`--model`; ver `CLAUDE.md`).

## Flujo de preguntas

Sigue los 8 pasos de "Demo Requirements" en `docs/development/roadmap.md` (§11, M8): pregunta en lenguaje natural → descubrimiento de la tool → invocación → lógica de negocio → datos → resultado estructurado → respuesta entendible → contexto educativo. Cada pregunta ejercita al menos una de las 10 tools del catálogo (`CLAUDE.md`, "Catálogo de MCP Tools").

| # | Pregunta sugerida | Tool(s) que dispara | Qué debe mostrar la respuesta |
|---|---|---|---|
| 1 | "¿Cuál es mi perfil de riesgo y mi capacidad de ahorro?" | `get_financial_profile` | Resumen categórico (`detail=summary` por default) -- nunca montos exactos a menos que se pidan explícitamente |
| 2 | "Muéstrame mi portafolio actual" | `get_portfolio` | Los 3 holdings con su distribución |
| 3 | "¿Qué tan diversificado está mi portafolio?" | `analyze_portfolio` | Score de diversificación y allocation recomendado para perfil `moderate` |
| 4 | "¿Qué información tienes de Apple?" | `get_asset_information` | Datos del catálogo local + cotización/perfil en vivo de Twelve Data |
| 5 | "¿Cuál es la cotización de AAPL y MSFT ahorita?" | `get_market_snapshot` | Precio y variación del día para ambos símbolos |
| 6 | "Si invierto $50,000 a 5 años con mi perfil de riesgo, ¿cuánto tendría?" | `simulate_investment` | Proyección mes a mes con interés compuesto |
| 7 | "¿Qué temas de educación financiera hay disponibles?" | `get_educational_topic` / `get_learning_path` | Lista de temas con estado de progreso |
| 8 | **"¿Qué debería aprender primero?"** | `get_learning_path` | El agente debe narrar `recommended_reason=no_goals` en sus propias palabras (ej. "no tienes metas registradas todavía, por eso te recomiendo empezar por..."), nunca mostrar el código crudo -- este es el punto que más vale demostrar: conecta contexto financiero real con educación |
| 9 | "¿Cómo voy con mi progreso de educación financiera?" | `get_learning_progress` | Porcentaje completado + `category_gaps` narrado como oportunidad, no como carencia |
| 10 | **"Ya terminé de leer sobre ahorro vs inversión, márcalo como completado"** | `mark_topic_completed` | La única **acción** del catálogo (requiere `mcp:write`) -- confirma que se guardó, y una repregunta del punto 9 debe reflejar el nuevo porcentaje |

Los puntos 8 y 10 son los que más pesan frente al deck del reto: el 8 muestra que el agente no solo lee datos sino que los conecta para dar una recomendación explicable; el 10 es la única acción real que dispara un cambio persistente en el sistema (el "action" del flujo intención → UI generada → interacción → acción que pide el entregable #1).

## Lo que este guion NO cubre (fuera de nuestro control)

- La interfaz generada dinámicamente (A2UI) -- hoy la respuesta de cada tool ya trae `component`/`props`, pero no hay ningún renderer construido en el proyecto todavía; eso es responsabilidad del equipo de Figma/frontend.
- Onboarding conectado a `/education` -- deliberadamente fuera de alcance (ver `CLAUDE.md`, sección de auth).
